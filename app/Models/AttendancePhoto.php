<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AttendancePhoto extends Model
{
    use HasFactory;

    public const CHECK_IN = 'CHECK_IN';

    public const CHECK_OUT = 'CHECK_OUT';

    protected $fillable = [
        'attendance_id',
        'photo_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function exists(): bool
    {
        return Storage::disk(config('parkops.photo.disk'))->exists($this->file_path);
    }

    /**
     * Photos are private (see config/filesystems.php); they are only reachable
     * through the authenticated route, never a direct storage URL.
     */
    public function url(): string
    {
        return route('attendance.photo', $this);
    }
}
