<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class File extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'original_name',
        'storage_path',
        'mime_type',
        'size',
        'expiration_type',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'size' => 'integer',
    ];

    protected $appends = ['download_url', 'formatted_size'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public static function calculateExpiresAt(string $expirationType): ?Carbon
    {
        return match ($expirationType) {
            '1_week' => Carbon::now()->addWeek(),
            '1_month' => Carbon::now()->addMonth(),
            '1_year' => Carbon::now()->addYear(),
            'never' => null,
            default => Carbon::now()->addWeek(),
        };
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    public function getDownloadUrlAttribute(): string
    {
        return url("/d/{$this->token}");
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now());
    }
}
