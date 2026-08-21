<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasUuids;

    /** @see the add_role_to_team_members_table migration for why three. */
    public const OWNER = 'OWNER';

    public const MANAGER = 'MANAGER';

    public const MEMBER = 'MEMBER';

    /** Roles that may rename the team and add/remove its members. */
    public const MANAGING_ROLES = [self::OWNER, self::MANAGER];

    public $timestamps = false;

    protected $fillable = ['team_id', 'user_id', 'role'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
