<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lab extends Model
{
    use HasFactory, SoftDeletes;

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
        return $this->hasMany(Equipment::class, 'lab_id');
    }

    /**
     * Get only operational equipment for this lab.
     */
    public function operationalEquipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'lab_id')->where('is_operational', true);
    }

    /**
     * Scope to get only active labs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
