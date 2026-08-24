<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReferenceRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\ReferenceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The CRUD screen shared by the three Karyawan reference masters.
 *
 * Each master keeps its own table, route, menu and URL — a subclass supplies
 * the model and the wording, everything else (listing, guards, audit trail)
 * is identical and lives here once.
 *
 * Two guards are what keep a master list from breaking the data it describes:
 *
 *  - a row still used by an employee is deactivated instead of deleted, the
 *    same bargain Shift and Employee already make, because deleting it would
 *    leave employees holding a code nothing can explain;
 *  - a row flagged is_system cannot be deleted or have its code changed, since
 *    the application compares against those literals in PHP.
 */
abstract class ReferenceController extends Controller
{
    /** @return class-string<ReferenceModel> */
    abstract protected function modelClass(): string;

    /** Route name prefix, e.g. "employment-statuses". */
    abstract protected function routeName(): string;

    /** Audit action prefix, e.g. "employment_status". */
    abstract protected function auditKey(): string;

    /**
     * Page wording. Kept as data so the shared Blade view needs no conditionals.
     *
     * @return array{title: string, subtitle: string, entity: string, note: string}
     */
    abstract protected function wording(): array;

    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('references.index', ['ref' => $this->viewData()]);
        }

        $rows = $this->modelClass()::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'code' => 'code',
                'name' => 'name',
                'sort_order' => ['sort_order', 'code'],
                'status' => 'status',
            ], 'sort_order'))
            ->paginate($this->perPage($request));

        // One grouped count for the whole page instead of a COUNT per row.
        $usage = $this->usageMap(collect($rows->items())->pluck('code')->all());

        return $this->paginated($rows, fn (ReferenceModel $row) => $this->transform($row, $usage));
    }

    /**
     * The concrete store()/update() live in the subclasses, type-hinting their
     * own FormRequest: the container cannot instantiate the abstract one, and
     * each master validates against its own table for uniqueness.
     */
    protected function persist(ReferenceRequest $request): JsonResponse
    {
        $row = $this->modelClass()::create($this->payload($request));

        AuditLog::record($this->auditKey().'.created', $row, $this->wording()['title'].' '.$row->code.' dibuat');

        return $this->ok($this->transform($row), 'Data berhasil disimpan.', 201);
    }

    public function show(string $reference): JsonResponse
    {
        return $this->ok($this->transform($this->find($reference)));
    }

    protected function modify(ReferenceRequest $request, string $reference): JsonResponse
    {
        $row = $this->find($reference);
        $data = $this->payload($request);

        // A system row is referenced by literal in PHP, so its code is frozen
        // and it must stay selectable. Everything descriptive stays editable.
        if ($row->is_system) {
            if ($data['code'] !== $row->code) {
                return $this->fail('Kode "'.$row->code.'" dipakai langsung oleh sistem dan tidak dapat diubah.');
            }

            if ($data['status'] !== ReferenceModel::ACTIVE) {
                return $this->fail('Baris sistem "'.$row->code.'" tidak dapat dinonaktifkan.');
            }
        }

        $oldCode = $row->code;

        // Renaming a code that employees already carry would orphan them, so
        // the rows move with it — and both writes have to land or neither.
        DB::transaction(function () use ($row, $data, $oldCode) {
            $row->update($data);

            if ($oldCode !== $row->code) {
                $column = ($this->modelClass())::EMPLOYEE_COLUMN;

                Employee::where($column, $oldCode)->update([$column => $row->code]);
            }
        });

        AuditLog::record($this->auditKey().'.updated', $row, $this->wording()['title'].' '.$row->code.' diperbarui');

        return $this->ok($this->transform($row), 'Data berhasil diperbarui.');
    }

    public function destroy(string $reference): JsonResponse
    {
        $row = $this->find($reference);

        if ($row->is_system) {
            return $this->fail('Baris sistem "'.$row->code.'" tidak dapat dihapus.');
        }

        if (($used = $row->usageCount()) > 0) {
            $row->update(['status' => ReferenceModel::INACTIVE]);

            AuditLog::record(
                $this->auditKey().'.deactivated',
                $row,
                $this->wording()['title'].' '.$row->code.' dinonaktifkan (masih dipakai)',
            );

            return $this->ok(
                $this->transform($row),
                'Masih dipakai '.$used.' karyawan, sehingga dinonaktifkan alih-alih dihapus.',
            );
        }

        $code = $row->code;
        $row->delete();

        AuditLog::record($this->auditKey().'.deleted', null, $this->wording()['title'].' '.$code.' dihapus');

        return $this->ok(message: 'Data berhasil dihapus.');
    }

    private function find(string $reference): ReferenceModel
    {
        return $this->modelClass()::findOrFail($reference);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ReferenceRequest $request): array
    {
        $data = $request->validated();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(ReferenceModel $row, ?array $usage = null): array
    {
        return [
            'id' => $row->id,
            'code' => $row->code,
            'name' => $row->name,
            'description' => $row->description,
            'sort_order' => $row->sort_order,
            'is_system' => $row->is_system,
            'used_by' => $usage === null ? $row->usageCount() : ($usage[$row->code] ?? 0),
            'status' => $row->status,
        ];
    }

    /**
     * How many employees carry each of the given codes.
     *
     * @param  array<int, string>  $codes
     * @return array<string, int>
     */
    private function usageMap(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $column = ($this->modelClass())::EMPLOYEE_COLUMN;

        return Employee::whereIn($column, $codes)
            ->groupBy($column)
            ->selectRaw($column.' as ref_code, count(*) as total')
            ->pluck('total', 'ref_code')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Everything the shared view and its script need to render this master.
     *
     * @return array<string, mixed>
     */
    private function viewData(): array
    {
        return $this->wording() + ['base' => route($this->routeName().'.index')];
    }
}
