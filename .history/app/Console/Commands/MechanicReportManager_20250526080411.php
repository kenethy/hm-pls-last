<?php

namespace App\Console\Commands;

use App\Models\Mechanic;
use App\Models\MechanicReport;
use App\Models\MechanicReportArchive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MechanicReportManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mechanic:manage-reports
                            {action : Action to perform (recalculate|reset|migrate|status)}
                            {--mechanic= : Specific mechanic ID to process}
                            {--force : Force operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage cumulative mechanic reports - recalculate, reset, migrate, or check status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $mechanicId = $this->option('mechanic');
        $force = $this->option('force');

        switch ($action) {
            case 'recalculate':
                return $this->recalculateReports($mechanicId, $force);
            case 'reset':
                return $this->resetReports($mechanicId, $force);
            case 'migrate':
                return $this->migrateWeeklyData($force);
            case 'status':
                return $this->showStatus();
            default:
                $this->error("Invalid action. Available actions: recalculate, reset, migrate, status");
                return 1;
        }
    }

    /**
     * Recalculate cumulative reports for all or specific mechanic.
     */
    private function recalculateReports($mechanicId = null, $force = false)
    {
        $this->info('🔄 Recalculating cumulative reports...');

        $query = MechanicReport::where('is_cumulative', true);

        if ($mechanicId) {
            $query->where('mechanic_id', $mechanicId);
        }

        $reports = $query->with('mechanic')->get();

        if ($reports->isEmpty()) {
            $this->warn('No cumulative reports found to recalculate.');
            return 0;
        }

        if (!$force && !$this->confirm("Recalculate {$reports->count()} cumulative reports?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->withProgressBar($reports, function ($report) {
            try {
                $report->recalculateCumulative();
            } catch (\Exception $e) {
                $this->error("Failed to recalculate report for {$report->mechanic->name}: {$e->getMessage()}");
            }
        });

        $this->newLine(2);
        $this->info("✅ Successfully recalculated {$reports->count()} cumulative reports.");
        return 0;
    }

    /**
     * Reset cumulative reports for all or specific mechanic.
     */
    private function resetReports($mechanicId = null, $force = false)
    {
        $this->info('🔄 Resetting cumulative reports...');

        $query = MechanicReport::where('is_cumulative', true);

        if ($mechanicId) {
            $query->where('mechanic_id', $mechanicId);
        }

        $reports = $query->with('mechanic')->get();

        if ($reports->isEmpty()) {
            $this->warn('No cumulative reports found to reset.');
            return 0;
        }

        if (!$force) {
            $this->warn("⚠️  This will reset {$reports->count()} cumulative reports and archive current data.");
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->withProgressBar($reports, function ($report) {
            try {
                $report->resetCumulative('bulk_reset_command');
            } catch (\Exception $e) {
                $this->error("Failed to reset report for {$report->mechanic->name}: {$e->getMessage()}");
            }
        });

        $this->newLine(2);
        $this->info("✅ Successfully reset {$reports->count()} cumulative reports.");
        return 0;
    }

    /**
     * Migrate any remaining weekly data to cumulative system.
     */
    private function migrateWeeklyData($force = false)
    {
        $this->info('🔄 Checking for weekly data to migrate...');

        // Check for any non-cumulative reports
        $weeklyReports = MechanicReport::where('is_cumulative', false)->count();

        if ($weeklyReports === 0) {
            $this->info('✅ No weekly data found. Migration already complete.');
            return 0;
        }

        $this->warn("Found {$weeklyReports} weekly reports that need migration.");

        if (!$force && !$this->confirm('Migrate weekly reports to archive and create cumulative reports?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        DB::transaction(function () use ($weeklyReports) {
            // Archive weekly reports
            $reports = MechanicReport::where('is_cumulative', false)->get();

            $this->withProgressBar($reports, function ($report) {
                // Archive the weekly report
                MechanicReportArchive::create([
                    'mechanic_id' => $report->mechanic_id,
                    'week_start' => $report->period_start,
                    'week_end' => $report->period_end,
                    'services_count' => $report->services_count,
                    'total_labor_cost' => $report->total_labor_cost,
                    'notes' => $report->notes,
                    'is_paid' => $report->is_paid,
                    'paid_at' => $report->paid_at,
                    'archived_at' => now(),
                    'archive_reason' => 'weekly_data_migration',
                ]);

                // Delete the weekly report
                $report->delete();
            });

            // Create cumulative reports for mechanics that don't have them
            $mechanics = Mechanic::where('is_active', true)
                ->whereDoesntHave('cumulativeReport')
                ->get();

            foreach ($mechanics as $mechanic) {
                $mechanic->getOrCreateCumulativeReport();
            }
        });

        $this->newLine(2);
        $this->info("✅ Successfully migrated {$weeklyReports} weekly reports to archive.");
        return 0;
    }

    /**
     * Show system status and statistics.
     */
    private function showStatus()
    {
        $this->info('📊 Mechanic Reports System Status');
        $this->newLine();

        // Cumulative reports
        $cumulativeCount = MechanicReport::where('is_cumulative', true)->count();
        $weeklyCount = MechanicReport::where('is_cumulative', false)->count();
        $archiveCount = MechanicReportArchive::count();
        $activeMechanics = Mechanic::where('is_active', true)->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Active Mechanics', $activeMechanics],
                ['Cumulative Reports', $cumulativeCount],
                ['Weekly Reports (Legacy)', $weeklyCount],
                ['Archived Reports', $archiveCount],
            ]
        );

        // Recent activity
        $this->newLine();
        $this->info('📈 Recent Activity:');

        $recentResets = MechanicReportArchive::where('archive_reason', 'like', '%reset%')
            ->orderBy('archived_at', 'desc')
            ->limit(5)
            ->with('mechanic')
            ->get();

        if ($recentResets->isNotEmpty()) {
            $this->table(
                ['Mechanic', 'Reset Date', 'Services', 'Labor Cost'],
                $recentResets->map(function ($archive) {
                    return [
                        $archive->mechanic->name,
                        $archive->archived_at->format('d M Y H:i'),
                        $archive->services_count,
                        'Rp ' . number_format($archive->total_labor_cost, 0, ',', '.'),
                    ];
                })->toArray()
            );
        } else {
            $this->info('No recent reset activity.');
        }

        // System health
        $this->newLine();
        $this->info('🏥 System Health:');

        $mechanicsWithoutReports = Mechanic::where('is_active', true)
            ->whereDoesntHave('cumulativeReport')
            ->count();

        if ($mechanicsWithoutReports > 0) {
            $this->warn("⚠️  {$mechanicsWithoutReports} active mechanics don't have cumulative reports.");
            $this->info("Run: php artisan mechanic:manage-reports migrate");
        } else {
            $this->info('✅ All active mechanics have cumulative reports.');
        }

        if ($weeklyCount > 0) {
            $this->warn("⚠️  {$weeklyCount} legacy weekly reports need migration.");
            $this->info("Run: php artisan mechanic:manage-reports migrate");
        } else {
            $this->info('✅ No legacy weekly reports found.');
        }

        return 0;
    }
}
