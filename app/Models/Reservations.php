<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class reservations extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'equipment_id',
        'start_time',
        'end_time',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the user that made this reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the equipment for this reservation.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(equipment::class);
    }

    /**
     * Scope to get confirmed reservations.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get cancelled reservations.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get completed reservations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get reservations for a specific date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_time', [$startDate, $endDate])
                  ->orWhereBetween('end_time', [$startDate, $endDate])
                  ->orWhere(function ($query) use ($startDate, $endDate) {
                      $query->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                  });
        });
    }

    /**
     * Scope to get reservations for a specific equipment.
     */
    public function scopeForEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * Scope to get reservations for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if the reservation is currently active.
     */
    public function getIsActiveAttribute(): bool
    {
        $now = Carbon::now();
        return $this->status === 'confirmed'
            && $this->start_time <= $now
            && $this->end_time >= $now;
    }

    /**
     * Check if the reservation has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_time < Carbon::now();
    }

    /**
     * Get the duration of the reservation in minutes.
     */
    public function getDurationInMinutesAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    /**
     * Get a human readable duration.
     */
    public function getHumanDurationAttribute(): string
    {
        $hours = floor($this->duration_in_minutes / 60);
        $minutes = $this->duration_in_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }
}
