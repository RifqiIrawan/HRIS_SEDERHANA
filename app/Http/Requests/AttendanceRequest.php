<?php

namespace App\Http\Requests;

/**
 * Spec §21, §26 — what the browser is allowed to send for a check-in or
 * check-out.
 *
 * Note what is absent: distance and any notion of validity. The frontend
 * reports raw sensor output only; the verdict is the backend's (spec §21).
 * A `distance` field arriving here would simply be ignored.
 */
class AttendanceRequest extends BaseRequest
{
    public function rules(): array
    {
        $photo = config('parkops.photo');

        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            // Some desktop browsers report an absurd accuracy; anything over
            // the limit is rejected downstream anyway, but a sane ceiling here
            // keeps nonsense out of the database.
            'accuracy' => ['required', 'numeric', 'min:0', 'max:100000'],
            'photo' => [
                'required',
                'file',
                'image',
                'mimetypes:'.implode(',', $photo['mimes']),
                'mimes:'.implode(',', $photo['extensions']),
                'max:'.$photo['max_kb'],
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Koordinat GPS belum tersedia. Aktifkan lokasi dan coba lagi.',
            'longitude.required' => 'Koordinat GPS belum tersedia. Aktifkan lokasi dan coba lagi.',
            'accuracy.required' => 'Akurasi GPS belum tersedia. Tunggu sinyal GPS stabil.',
            'photo.required' => 'Foto absensi wajib diambil.',
            'photo.image' => 'Berkas yang diunggah bukan gambar.',
            'photo.mimetypes' => 'Format foto harus JPEG, PNG, atau WEBP.',
            'photo.mimes' => 'Format foto harus JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'accuracy' => 'Akurasi GPS',
            'photo' => 'Foto',
        ];
    }
}
