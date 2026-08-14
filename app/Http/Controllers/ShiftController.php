<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftRequest;
use App\Models\AuditLog;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Spec §14. */
class ShiftController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('shifts.index');
        }

        $shifts = Shift::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('shift_code', 'like', $term)
                    ->orWhere('shift_name', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'shift_code' => 'shift_code',
                'shift_name' => 'shift_name',
                'start_time' => 'start_time',
                'end_time' => 'end_time',
                'late_tolerance_minutes' => 'late_tolerance_minutes',
                'status' => 'status',
            ], 'start_time'))
            ->paginate($this->perPage($request));

        return $this->paginated($shifts, fn (Shift $s) => $this->transform($s));
    }

    public function store(ShiftRequest $request): JsonResponse
    {
        $shift = Shift::create($request->validated());

        AuditLog::record('shift.created', $shift, 'Shift '.$shift->shift_code.' dibuat');

        return $this->ok($this->transform($shift), 'Shift berhasil disimpan.', 201);
    }

    public function show(Shift $shift): JsonResponse
    {
        return $this->ok($this->transform($shift));
    }

    public function update(ShiftRequest $request, Shift $shift): JsonResponse
    {
        $shift->update($request->validated());

        AuditLog::record('shift.updated', $shift, 'Shift '.$shift->shift_code.' diperbarui');

        return $this->ok($this->transform($shift), 'Shift berhasil diperbarui.');
    }

    public function destroy(Shift $shift): JsonResponse
    {
        if ($shift->rosters()->exists() || $shift->assignments()->exists()) {
            $shift->update(['status' => Shift::INACTIVE]);

            AuditLog::record('shift.deactivated', $shift, 'Shift dinonaktifkan (masih dipakai)');

            return $this->ok(
                $this->transform($shift),
                'Shift masih dipakai roster/assignment, sehingga dinonaktifkan alih-alih dihapus.',
            );
        }

        $code = $shift->shift_code;
        $shift->delete();

        AuditLog::record('shift.deleted', null, 'Shift '.$code.' dihapus');

        return $this->ok(message: 'Shift berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Shift $shift): array
    {
        return [
            'id' => $shift->id,
            'shift_code' => $shift->shift_code,
            'shift_name' => $shift->shift_name,
            'start_time' => substr($shift->start_time, 0, 5),
            'end_time' => substr($shift->end_time, 0, 5),
            'cross_day' => $shift->cross_day,
            'late_tolerance_minutes' => $shift->late_tolerance_minutes,
            'duration_minutes' => $shift->durationMinutes(),
            'status' => $shift->status,
        ];
    }
}
