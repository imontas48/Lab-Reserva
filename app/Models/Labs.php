<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Labs extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all equipment belonging to this lab.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(equipment::class, 'lab_id');
    }

    /**
     * Get only operational equipment for this lab.
     */
    public function operationalEquipment(): HasMany
    {
        return $this->hasMany(equipment::class, 'lab_id')->where('is_operational', true);
    }

    /**
     * Scope to get only active labs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
