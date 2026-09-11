<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * AuditLog::ACTIONS is the catalogue three places read from: the audit page's
 * filter dropdown, the icon map in resources/js/Support/audit.js, and the
 * `audit.action_<action>` labels in lang/en.json.
 *
 * Ten recorded actions were missing from it — blueprint.*, event.finished and
 * the rest — so the dashboard rendered "audit.action_event_closed" at a
 * reader. Nothing failed; the row just showed its own key. These two tests
 * are what notices next time.
 */
class AuditActionCatalogueTest extends TestCase
{
    #[Test]
    public function every_catalogued_action_has_a_label(): void
    {
        foreach (AuditLog::ACTIONS as $action) {
            $key = 'audit.action_'.str_replace('.', '_', $action);

            $this->assertNotSame($key, __($key), "No label for audit action '{$action}'");
        }
    }

    /**
     * Reads the source rather than the database: an action is only ever
     * logged from a record() call, and the catalogue drifted precisely
     * because adding one of those needs no change anywhere else.
     *
     * Both call shapes are covered — a literal action, and the ternary pair
     * in EventFinishService and BoardController — by scanning every quoted
     * dotted string up to record()'s first argument separator.
     */
    #[Test]
    public function every_recorded_action_is_catalogued(): void
    {
        $recorded = [];

        foreach ($this->phpFilesUnder(app_path()) as $file) {
            $code = file_get_contents($file);

            preg_match_all('/AuditLog::record\(\s*(.{0,120}?)[,)]/s', $code, $calls);

            foreach ($calls[1] as $argument) {
                preg_match_all("/'([a-z]+\.[a-z_]+)'/", $argument, $actions);

                foreach ($actions[1] as $action) {
                    $recorded[$action] ??= $file;
                }
            }
        }

        $this->assertNotEmpty($recorded, 'Found no AuditLog::record() calls — the scan itself is broken.');

        foreach ($recorded as $action => $file) {
            $this->assertContains(
                $action,
                AuditLog::ACTIONS,
                sprintf("'%s' is recorded in %s but missing from AuditLog::ACTIONS", $action, basename($file))
            );
        }
    }

    /** @return list<string> */
    private function phpFilesUnder(string $directory): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
