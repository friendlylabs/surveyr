<?php

namespace App\Console;

use Aloe\Command;
use App\Models\Report;
use App\Services\ReportSchema;

/**
 * Rewrites report documents into the latest v2 schema (attachments carry
 * their source form, filters are keyed by form). Controllers convert on the
 * fly, so this only persists that conversion; rows already current are skipped.
 *
 *   php leaf reports:migrate --dry-run
 *   php leaf reports:migrate --report=12
 *   php leaf reports:migrate --yes
 */
class MigrateReportSchema extends Command
{
    protected static $defaultName = 'reports:migrate';
    public $description = 'Convert legacy report content/draft JSON to the v2 section schema';
    public $help = 'Backs up every affected row to storage/app/backups before writing. Use --dry-run to preview.';

    protected function config()
    {
        $this
            ->setOption('dry-run', 'd', 'none', 'List the reports that would change without writing')
            ->setOption('report', 'r', 'optional', 'Only migrate this report id')
            ->setOption('yes', 'y', 'none', 'Skip the confirmation prompt');
    }

    protected function handle()
    {
        $query = Report::query();
        if ($id = $this->option('report')) {
            $query->where('id', (int) $id);
        }

        $pending = [];
        foreach ($query->get() as $report) {
            $columns = array_filter(['content', 'draft'], fn ($col) => !ReportSchema::isCurrent($report->{$col}));
            if (!ReportSchema::filtersAreCurrent($report->filters)) $columns[] = 'filters';
            if ($columns) $pending[] = [$report, array_values($columns)];
        }

        if (!$pending) {
            $this->info('Nothing to migrate: every report already uses the latest schema.');
            return 0;
        }

        $this->comment(count($pending) . ' report(s) are not in the latest schema:');
        foreach ($pending as [$report, $columns]) {
            $this->writeln(sprintf('  #%-5d %-40s %s', $report->id, mb_strimwidth($report->title, 0, 40, '…'), implode(', ', $columns)));
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: no changes written.');
            return 0;
        }

        if (!$this->option('yes') && !$this->confirm('Back up and migrate these reports?')) {
            $this->comment('Aborted.');
            return 0;
        }

        $backup = $this->backup($pending);
        $this->info("Backup written to {$backup}");

        foreach ($pending as [$report, $columns]) {
            // the legacy single form stamps attachments and wraps flat filters
            $legacyForm = $report->primaryFormId();

            foreach ($columns as $column) {
                $report->{$column} = $column === 'filters'
                    ? ReportSchema::normalizeFilters($report->filters, $report->formIds(), $legacyForm)
                    : ReportSchema::normalize($report->{$column}, $legacyForm);
            }
            $report->save();
            $this->writeln("  migrated #{$report->id}");
        }

        $this->info('Done.');
        return 0;
    }

    /** Snapshot the affected rows so the migration can be reversed by hand. */
    private function backup(array $pending): string
    {
        $dir = StoragePath('app/backups');
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $file = $dir . '/reports-schema-' . date('Ymd-His') . '.json';
        $rows = array_map(fn ($row) => [
            'id'      => $row[0]->id,
            'content' => $row[0]->content,
            'draft'   => $row[0]->draft,
            'filters' => $row[0]->filters,
        ], $pending);

        file_put_contents($file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $file;
    }
}
