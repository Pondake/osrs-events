<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin's own icon for one boss, overriding the committed pet sprite.
 *
 * See the migration for why this is sparse. The resolution order lives in
 * BossIconService, not here — a row is just the override, and something has to
 * own the question of what wins.
 */
#[Fillable(['metric', 'icon_url'])]
class BossIcon extends Model
{
    use HasUuids;
}
