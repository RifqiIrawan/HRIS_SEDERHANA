<?php

namespace App\Http\Controllers;

use App\Models\AttendancePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Attendance photos are face photos tied to GPS coordinates, so they live on a
 * private disk and are streamed through here rather than exposed under
 * public/storage. HR sees any photo; an employee sees only their own.
 */
class AttendancePhotoController extends Controller
{
    public function __invoke(Request $request, AttendancePhoto $photo): StreamedResponse
    {
        $user = $request->user();
        $attendance = $photo->attendance;

        abort_if(
            ! $user->isHr() && $attendance->employee_id !== $user->employee_id,
            403,
            'Anda tidak memiliki akses ke foto absensi ini.',
        );

        $disk = Storage::disk(config('hris.photo.disk'));

        abort_unless($disk->exists($photo->file_path), 404, 'Foto absensi tidak ditemukan.');

        return $disk->response($photo->file_path, $photo->file_name, [
            'Content-Type' => $photo->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
