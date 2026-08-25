<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\AuditLog;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Spec §12 & §13 — master lokasi with the Leaflet point picker. */
class LocationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->wantsData($request)) {
            return view('locations.index', [
                'defaultRadius' => config('parkops.default_radius_meter'),
                'defaultAccuracy' => config('parkops.default_gps_accuracy_limit'),
            ]);
        }

        $locations = Location::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search')->trim().'%';

                $query->where(fn ($q) => $q
                    ->where('location_code', 'like', $term)
                    ->orWhere('location_name', 'like', $term)
                    ->orWhere('address', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->tap(fn ($q) => $this->applySort($q, $request, [
                'location_code' => 'location_code',
                'location_name' => 'location_name',
                'address' => 'address',
                'radius_meter' => 'radius_meter',
                'gps_accuracy_limit' => 'gps_accuracy_limit',
                'status' => 'status',
            ], 'location_name'))
            ->paginate($this->perPage($request));

        return $this->paginated($locations, fn (Location $l) => $this->transform($l));
    }

    public function store(LocationRequest $request): JsonResponse
    {
        $location = Location::create($request->validated());

        AuditLog::record('location.created', $location, 'Lokasi '.$location->location_name.' dibuat');

        return $this->ok($this->transform($location), 'Lokasi berhasil disimpan.', 201);
    }

    public function show(Location $location): JsonResponse
    {
        return $this->ok($this->transform($location));
    }

    public function update(LocationRequest $request, Location $location): JsonResponse
    {
        $location->update($request->validated());

        AuditLog::record('location.updated', $location, 'Lokasi '.$location->location_name.' diperbarui');

        return $this->ok($this->transform($location), 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Location $location): JsonResponse
    {
        // Rosters and attendances point at this row; deleting it would orphan
        // the coordinates a past check-in was measured against.
        if ($location->attendances()->exists() || $location->rosters()->exists() || $location->assignments()->exists()) {
            $location->update(['status' => Location::INACTIVE]);

            AuditLog::record('location.deactivated', $location, 'Lokasi dinonaktifkan (masih dipakai)');

            return $this->ok(
                $this->transform($location),
                'Lokasi masih dipakai roster/absensi, sehingga dinonaktifkan alih-alih dihapus.',
            );
        }

        $name = $location->location_name;
        $location->delete();

        AuditLog::record('location.deleted', null, 'Lokasi '.$name.' dihapus');

        return $this->ok(message: 'Lokasi berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Location $location): array
    {
        return [
            'id' => $location->id,
            'location_code' => $location->location_code,
            'location_name' => $location->location_name,
            'address' => $location->address,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'radius_meter' => $location->radius_meter,
            'gps_accuracy_limit' => $location->gps_accuracy_limit,
            'status' => $location->status,
        ];
    }
}
