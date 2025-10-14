<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class equipment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lab_id',
        'identifier',
        'type',
        'specifications',
        'is_operational',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_operational' => 'boolean',
    ];

    /**
     * The table associated with the model.
     */
    protected $table = 'equipment';

    /**
     * Get the lab that owns this equipment.
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(labs::class);
    }

    /**
     * Get all software installed on this equipment.
     */
    public function software(): BelongsToMany
    {
        return $this->belongsToMany(software::class, 'equipment_software');
    }

    /**
     * Get all reservations for this equipment.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(reservations::class);
    }

    /**
     * Get active reservations for this equipment.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(reservations::class)->where('status', 'confirmed');
    }

    /**
     * Scope to get only operational equipment.
     */
    public function scopeOperational($query)
    {
        return $query->where('is_operational', true);
    }

    /**
     * Scope to filter by lab.
     */
    public function scopeInLab($query, $labId)
    {
        return $query->where('lab_id', $labId);
    }

    /**
     * Scope to filter by equipment type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the full identifier including lab name.
     */
    public function getFullIdentifierAttribute(): string
    {
        return "{$this->lab->name} - {$this->identifier}";
    }
}
