<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
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
        'avatar_url',
        'banner_url',
        'bio',
        'is_private',
        'preferences',
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
            'is_private' => 'boolean',
            'preferences' => 'array',
        ];
    }

    // Relationships

    public function visits()
    {
        return $this->hasMany(RoomVisit::class);
    }

    public function sentFriendships()
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function receivedFriendships()
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    public function friends()
    {
        // Union of sent and received accepted friendships... complex in Eloquent direct relation.
        // Usually simpler to just use a service or accessor.
        // However, standard many-to-many approach if we treat pivot table differently?
        // Let's stick to explicit relationships for now.
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
            // Note: This only gets friends where THIS user is the sender. 
            // For bidirectional, we need a custom accessor or merge collection.
    }

    /**
     * Get the user's rank.
     */
    public function rank(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function mutes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Mute::class);
    }

    public function bans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ban::class);
    }

    protected $appends = ['rank_data', 'is_banned', 'ban_data', 'mute_data'];

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
            'priority' => $this->rank->priority,
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

        $hasPermission = $this->rank->permissions->contains('slug', $permission);
        self::$permissionsCache[$this->id][$permission] = $hasPermission;

        return $hasPermission;
    }

    public function conversations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)
            ->withTimestamps();
    }

    public function getIsBannedAttribute(): bool
    {
        return $this->bans()->active()->whereNull('room_id')->exists();
    }

    public function getBanDataAttribute(): ?array
    {
        $ban = $this->bans()->active()->whereNull('room_id')->latest()->first();
        if (!$ban) return null;

        return [
            'reason' => $ban->reason,
            'expires_at' => $ban->expires_at?->toIso8601String(),
            'moderator' => $ban->moderator->name,
        ];
    }

    public function getMuteDataAttribute(): ?array
    {
        $mute = $this->mutes()->active()->latest()->first();
        if (!$mute) return null;

        return [
            'reason' => $mute->reason,
            'expires_at' => $mute->expires_at?->toIso8601String(),
            'room_id' => $mute->room_id,
        ];
    }

    /**
     * Check if the user can manage the target user's rank.
     */
    public function canManage(User $targetUser): bool
    {
        if (!$this->hasPermission('manage_user_ranks')) {
            return false;
        }

        // Higher priority can manage lower priority
        $myPriority = $this->rank?->priority ?? 0;
        $targetPriority = $targetUser->rank?->priority ?? 0;

        return $myPriority > $targetPriority;
    }

    /**
     * Check if the user can assign a specific rank.
     */
    public function canAssignRank(Rank $newRank): bool
    {
        if (!$this->hasPermission('manage_user_ranks')) {
            return false;
        }

        // Higher priority can assign lower priority ranks
        $myPriority = $this->rank?->priority ?? 0;
        $newPriority = $newRank->priority;

        return $myPriority > $newPriority;
    }
}
