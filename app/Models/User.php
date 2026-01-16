<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rank_id',
        'xp',
        'level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * The permissions cache for the current request.
     *
     * @var array<int, array<string, bool>>
     */
    protected static array $permissionsCache = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'level' => 'integer',
            'xp' => 'integer',
        ];
    }

    /**
     * Get the user's rank.
     */
    public function rank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    protected $appends = ['rank_data'];

    /**
     * Get the user's rank data structure for frontend.
     */
    public function getRankDataAttribute(): array
    {
        if (! $this->rank) {
            return [
                'name' => 'Guest',
                'prefix' => null,
                'color_prefix' => '#cccccc',
                'color_name' => '#cccccc',
                'color_text' => '#333333',
                'effects' => [],
            ];
        }

        return [
            'name' => $this->rank->name,
            'prefix' => $this->rank->prefix,
            'color_prefix' => $this->rank->color_prefix,
            'color_name' => $this->rank->color_name,
            'color_text' => $this->rank->color_text,
            'effects' => $this->rank->effects ?? [],
        ];
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (isset(self::$permissionsCache[$this->id][$permission])) {
            return self::$permissionsCache[$this->id][$permission];
        }

        if (!$this->rank) {
            return false;
        }

        // Preload permissions if not already loaded to avoid N+1 in loops if we access the relation
        // However, for single checks, we might just want to check the collection.
        // Assuming 'permissions' relation is eager loaded or we load it now.
        $hasPermission = $this->rank->permissions->contains('slug', $permission);

        self::$permissionsCache[$this->id][$permission] = $hasPermission;

        return $hasPermission;
    }
}
