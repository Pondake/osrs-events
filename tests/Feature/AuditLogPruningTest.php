<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Audit rows were kept forever — nothing pruned them, and nothing scheduled
 * anything that would. One afternoon of a tester clicking "create invite
 * link" is enough to show why that is a problem, but the retention itself
 * matters more: these rows deliberately keep a user's display name after the
 * account is deleted, so "indefinitely" is the wrong answer for a reason
 * that outlives any one table's size.
 */
class AuditLogPruningTest extends TestCase
{
    use RefreshDatabase;

    private function logAged(int $daysOld): AuditLog
    {
        $log = AuditLog::create([
            'action' => 'invite.created',
            'actor_label' => 'Pondake',
        ]);

        // forceFill + save, because created_at is not fillable and the point
        // of the row is its age.
        $log->forceFill(['created_at' => now()->subDays($daysOld)])->save();

        return $log;
    }

    #[Test]
    public function rows_past_the_retention_window_are_pruned(): void
    {
        config()->set('audit.retention_days', 90);

        $old = $this->logAged(91);
        $recent = $this->logAged(89);

        $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

        $this->assertNull(AuditLog::find($old->id), 'a row past the window survived');
        $this->assertNotNull(AuditLog::find($recent->id), 'a row inside the window was deleted');
    }

    /** The window is a policy setting, so it has to actually be honoured. */
    #[Test]
    public function the_retention_window_is_configurable(): void
    {
        config()->set('audit.retention_days', 30);

        $old = $this->logAged(31);
        $recent = $this->logAged(29);

        $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

        $this->assertNull(AuditLog::find($old->id));
        $this->assertNotNull(AuditLog::find($recent->id));
    }

    /**
     * The audit trail is append-only and read by admins; pruning must not
     * take anything else with it. Mass pruning issues a plain delete against
     * this table alone, and this is the guard on that staying true.
     */
    #[Test]
    public function pruning_touches_nothing_but_audit_rows(): void
    {
        config()->set('audit.retention_days', 1);

        $user = User::factory()->create();
        $this->logAged(400);

        $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

        $this->assertSame(0, AuditLog::count());
        $this->assertNotNull(User::find($user->id), 'pruning deleted a user');
    }
}
