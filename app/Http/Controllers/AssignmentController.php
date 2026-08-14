<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignmentRequest;
use App\Models\Assignment;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Spec §18. */
class AssignmentController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('assignments.index');
        }

        $assignments = Assignment::query()
            ->with(['employee:id,employee_code,full_name', 'location:id,location_name', 'shift:id,shift_code,shift_name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->whereHas('employee', fn ($q) => $q
                    ->where('employee_code', 'like', $term)
                    ->orWhere('full_name', 'like', $term));
            })
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->when($request->filled('shift_id'), fn ($q) => $q->where('shift_id', $request->integer('shift_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'start_date' => 'start_date',
                'end_date' => 'end_date',
                'status' => 'status',
            ], 'start_date', 'desc'))
            ->paginate($this->perPage($request));

        return $this->paginated($assignments, fn (Assignment $a) => $this->transform($a));
    }

    public function store(AssignmentRequest $request): JsonResponse
    {
        $assignment = Assignment::create($request->validated())
            ->load(['employee', 'location', 'shift']);

        AuditLog::record('assignment.created', $assignment, sprintf(
            'Assignment %s → %s (%s)',
            $assignment->employee->employee_code,
            $assignment->location->location_name,
            $assignment->shift->shift_code,
        ));

        return $this->ok($this->transform($assignment), 'Assignment berhasil disimpan.', 201);
    }

    public function show(Assignment $assignment): JsonResponse
    {
        return $this->ok($this->transform($assignment->load(['employee', 'location', 'shift'])));
    }

    public function update(AssignmentRequest $request, Assignment $assignment): JsonResponse
    {
        $assignment->update($request->validated());
        $assignment->load(['employee', 'location', 'shift']);

        AuditLog::record('assignment.updated', $assignment, 'Assignment diperbarui');

        return $this->ok($this->transform($assignment), 'Assignment berhasil diperbarui.');
    }

    public function destroy(Assignment $assignment): JsonResponse
    {
        $assignment->delete();

        AuditLog::record('assignment.deleted', null, 'Assignment #'.$assignment->id.' dihapus');

        return $this->ok(message: 'Assignment berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Assignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'employee_id' => $assignment->employee_id,
            'employee_code' => $assignment->employee->employee_code,
            'employee_name' => $assignment->employee->full_name,
            'location_id' => $assignment->location_id,
            'location_name' => $assignment->location->location_name,
            'shift_id' => $assignment->shift_id,
            'shift_code' => $assignment->shift->shift_code,
            'shift_name' => $assignment->shift->shift_name,
            'start_date' => $assignment->start_date->toDateString(),
            'end_date' => $assignment->end_date?->toDateString(),
            'status' => $assignment->status,
        ];
    }
}
