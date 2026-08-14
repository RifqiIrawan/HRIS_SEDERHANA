<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Validation\Rule;

/**
 * Spec §12. radius_meter is capped at 10 because the whole geofence contract
 * is "maksimal 10 meter" — widening it is a change request, not a form entry.
 */
class LocationRequest extends BaseRequest
{
    protected function nullableFields(): array
    {
        return ['address'];
    }

    public function rules(): array
    {
        $id = $this->route('location')?->id;

        return [
            'location_code' => ['required', 'string', 'max:30', Rule::unique('locations')->ignore($id)],
            'location_name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meter' => ['required', 'integer', 'min:1', 'max:10'],
            'gps_accuracy_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::in([Location::ACTIVE, Location::INACTIVE])],
        ];
    }

    public function messages(): array
    {
        return [
            'radius_meter.max' => 'Radius geofence maksimal 10 meter sesuai spesifikasi sistem.',
            'latitude.required' => 'Tentukan titik lokasi pada peta terlebih dahulu.',
            'longitude.required' => 'Tentukan titik lokasi pada peta terlebih dahulu.',
        ];
    }

    public function attributes(): array
    {
        return [
            'location_code' => 'Kode Lokasi',
            'location_name' => 'Nama Lokasi',
            'address' => 'Alamat',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'radius_meter' => 'Radius',
            'gps_accuracy_limit' => 'Batas Akurasi GPS',
            'status' => 'Status',
        ];
    }
}
