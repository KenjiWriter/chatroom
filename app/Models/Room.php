<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;
    
    // ... fillable and casts remain implicit, targeting start of class to add imports and methods

    protected $fillable = [
        'name',
        'slug',
        'description',
        'min_level',
        'required_rank_id',
        'is_active',
    ];

    protected $casts = [
        'min_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function requiredRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'required_rank_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Check if the given user has access to this room.
     */
    public function checkAccess(User $user): bool
    {
        if ($this->min_level > 0 && $user->level < $this->min_level) {
            return false;
        }

        if ($this->required_rank_id) {
            if (! $user->rank) {
                return false;
            }
            // Strict ID check for now, can be improved to priority check
            if ($user->rank_id !== $this->required_rank_id && $user->rank->priority < $this->requiredRank->priority) {
                 return false;
            }
        }
        
        return true;
    }
}
