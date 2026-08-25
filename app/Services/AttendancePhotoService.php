<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendancePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Spec §26 — writes the captured photo to disk and records only its metadata
 * in MySQL. Never base64 in a column.
 */
class AttendancePhotoService
{
    public function store(Attendance $attendance, UploadedFile $file, string $type): AttendancePhoto
    {
        $disk = Storage::disk(config('parkops.photo.disk'));

        // Read the file's own properties before storeAs() moves the temp file
        // out from under us. The MIME the client claims is only a hint;
        // guessExtension() sniffs the real signature, so a .php renamed to .jpg
        // cannot pick its own extension on disk.
        $extension = $file->guessExtension() ?: 'jpg';
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        $directory = sprintf(
            'photos/%s/%s',
            $attendance->attendance_date->format('Y'),
            $attendance->attendance_date->format('m'),
        );

        $fileName = sprintf(
            '%s_%s_%s_%s.%s',
            $attendance->employee->employee_code ?? $attendance->employee_id,
            $attendance->attendance_date->format('Ymd'),
            strtolower($type),
            Str::random(8),
            $extension,
        );

        $path = $file->storeAs($directory, $fileName, ['disk' => config('parkops.photo.disk')]);

        // Replacing an existing photo of the same type (only reachable via an
        // HR correction) must not orphan the previous file.
        $existing = $attendance->photos()->where('photo_type', $type)->first();

        if ($existing) {
            $disk->delete($existing->file_path);
            $existing->delete();
        }

        return $attendance->photos()->create([
            'photo_type' => $type,
            'file_path' => $path,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
        ]);
    }
}
