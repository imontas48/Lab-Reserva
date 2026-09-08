<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Software extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'version',
    ];

    /**
     * The table associated with the model.
     */
    protected $table = 'software';

    /**
     * Get all equipment that has this software installed.
     */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'equipment_software');
    }

    /**
     * Scope to search software by name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
