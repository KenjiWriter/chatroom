<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

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

    public function requiredRank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rank::class, 'required_rank_id');
    }
}
