<?php

namespace App\Console\Commands;

use App\Models\Mechanic;
use App\Models\MechanicReport;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMechanicReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mechanic:sync-reports {--force : Force rebuild all cumulative reports} {--mechanic_id= : Sync reports for specific mechanic} {--service_id= : Sync reports for specific service}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize cumulative mechanic reports with actual service data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cumulative mechanic reports synchronization...');
        Log::info('SyncMechanicReports: Starting cumulative mechanic reports synchronization');

        $force = $this->option('force');
        $mechanicId = $this->option('mechanic_id');
        $serviceId = $this->option('service_id');

        if ($force) {
            $this->info('Force rebuilding all cumulative reports...');
            Log::info('SyncMechanicReports: Force rebuilding all cumulative reports');

            // Rebuild all cumulative reports safely
            $this->rebuildAllCumulativeReports();
        } elseif ($mechanicId) {
            $this->info("Syncing cumulative report for mechanic #{$mechanicId}...");
            Log::info("SyncMechanicReports: Syncing cumulative report for mechanic #{$mechanicId}");

            // Process specific mechanic
            $this->syncMechanicCumulativeReport(Mechanic::find($mechanicId));
        } elseif ($serviceId) {
            $this->info("Syncing reports affected by service #{$serviceId}...");
            Log::info("SyncMechanicReports: Syncing reports affected by service #{$serviceId}");

            // Process mechanics affected by specific service
            $this->syncServiceAffectedReports(Service::find($serviceId));
        } else {
            $this->info('Validating and updating all cumulative reports...');
            Log::info('SyncMechanicReports: Validating and updating all cumulative reports');

            // Validate all cumulative reports
            $this->validateAllCumulativeReports();
        }

        $this->info('Cumulative mechanic reports synchronization completed!');
        Log::info('SyncMechanicReports: Cumulative mechanic reports synchronization completed');
    }

    /**
     * Rebuild all cumulative reports safely.
     */
    private function rebuildAllCumulativeReports()
    {
        $mechanics = Mechanic::where('is_active', true)->get();

        $this->info("Found {$mechanics->count()} active mechanics");
        Log::info("SyncMechanicReports: Found {$mechanics->count()} active mechanics");

        $bar = $this->output->createProgressBar($mechanics->count());
        $bar->start();

        $created = 0;
        $updated = 0;
        $errors = 0;

        foreach ($mechanics as $mechanic) {
            try {
                // Check if cumulative report exists
                $existingReport = $mechanic->cumulativeReport()->first();

                if ($existingReport) {
                    // Recalculate existing cumulative report
                    $existingReport->recalculateCumulative();
                    $updated++;
                    Log::info("SyncMechanicReports: Updated cumulative report for mechanic #{$mechanic->id}");
                } else {
                    // Create new cumulative report
                    $mechanic->getOrCreateCumulativeReport();
                    $created++;
                    Log::info("SyncMechanicReports: Created cumulative report for mechanic #{$mechanic->id}");
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error("SyncMechanicReports: Error processing mechanic #{$mechanic->id}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Rebuild completed: {$created} created, {$updated} updated, {$errors} errors");
        Log::info("SyncMechanicReports: Rebuild completed: {$created} created, {$updated} updated, {$errors} errors");
    }

    /**
     * Sync cumulative report for a specific mechanic.
     */
    private function syncMechanicCumulativeReport($mechanic)
    {
        if (!$mechanic) {
            $this->error('Mechanic not found');
            Log::error('SyncMechanicReports: Mechanic not found');
            return;
        }

        Log::info("SyncMechanicReports: Syncing cumulative report for mechanic #{$mechanic->id}");

        try {
            // Get or create cumulative report and recalculate
            $report = $mechanic->getOrCreateCumulativeReport();
            $report->recalculateCumulative();

            $this->info("Successfully synced cumulative report for mechanic #{$mechanic->id}");
            Log::info("SyncMechanicReports: Successfully synced cumulative report for mechanic #{$mechanic->id}");
        } catch (\Exception $e) {
            $this->error("Error syncing cumulative report for mechanic #{$mechanic->id}: " . $e->getMessage());
            Log::error("SyncMechanicReports: Error syncing cumulative report for mechanic #{$mechanic->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Sync reports affected by a specific service.
     */
    private function syncServiceAffectedReports($service)
    {
        if (!$service) {
            $this->error('Service not found');
            Log::error('SyncMechanicReports: Service not found');
            return;
        }

        Log::info("SyncMechanicReports: Syncing reports affected by service #{$service->id}");

        // Get all mechanics assigned to this service
        $mechanics = $service->mechanics;

        if ($mechanics->count() === 0) {
            $this->info("Service #{$service->id} has no assigned mechanics");
            Log::info("SyncMechanicReports: Service #{$service->id} has no assigned mechanics");
            return;
        }

        $this->info("Found {$mechanics->count()} mechanics affected by service #{$service->id}");

        foreach ($mechanics as $mechanic) {
            try {
                // Recalculate cumulative report for each affected mechanic
                $report = $mechanic->getOrCreateCumulativeReport();
                $report->recalculateCumulative();

                Log::info("SyncMechanicReports: Updated cumulative report for mechanic #{$mechanic->id} affected by service #{$service->id}");
            } catch (\Exception $e) {
                Log::error("SyncMechanicReports: Error updating report for mechanic #{$mechanic->id} affected by service #{$service->id}: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("Completed syncing reports affected by service #{$service->id}");
    }

    /**
     * Process a specific mechanic.
     */
    private function processMechanic($mechanic)
    {
        if (!$mechanic) {
            $this->error('Mechanic not found');
            Log::error('SyncMechanicReports: Mechanic not found');
            return;
        }

        Log::info("SyncMechanicReports: Processing mechanic #{$mechanic->id}");

        // Get all week periods for this mechanic
        $weekPeriods = DB::table('mechanic_service')
            ->where('mechanic_id', $mechanic->id)
            ->join('services', 'mechanic_service.service_id', '=', 'services.id')
            ->where('services.status', 'completed')
            ->select('mechanic_service.week_start', 'mechanic_service.week_end')
            ->distinct()
            ->get();

        foreach ($weekPeriods as $period) {
            $weekStart = $period->week_start;
            $weekEnd = $period->week_end;

            if (empty($weekStart) || empty($weekEnd)) {
                continue;
            }

            // Update mechanic report for this period
            $this->updateMechanicReport($mechanic, $weekStart, $weekEnd);
        }
    }

    /**
     * Update mechanic report for a specific period.
     */
    private function updateMechanicReport($mechanic, $weekStart, $weekEnd)
    {
        // Calculate total labor cost for completed services
        $totalLaborCost = DB::table('mechanic_service')
            ->join('services', 'mechanic_service.service_id', '=', 'services.id')
            ->where('mechanic_service.mechanic_id', $mechanic->id)
            ->where('mechanic_service.week_start', $weekStart)
            ->where('mechanic_service.week_end', $weekEnd)
            ->where('services.status', 'completed')
            ->sum('mechanic_service.labor_cost');

        // Count completed services
        $servicesCount = DB::table('mechanic_service')
            ->join('services', 'mechanic_service.service_id', '=', 'services.id')
            ->where('mechanic_service.mechanic_id', $mechanic->id)
            ->where('mechanic_service.week_start', $weekStart)
            ->where('mechanic_service.week_end', $weekEnd)
            ->where('services.status', 'completed')
            ->count();

        Log::info("SyncMechanicReports: Calculated for mechanic #{$mechanic->id}: services_count={$servicesCount}, total_labor_cost={$totalLaborCost}");

        // Find or create the report
        $report = DB::table('mechanic_reports')
            ->where('mechanic_id', $mechanic->id)
            ->where('week_start', $weekStart)
            ->where('week_end', $weekEnd)
            ->first();

        if ($report) {
            // Update existing report
            DB::table('mechanic_reports')
                ->where('id', $report->id)
                ->update([
                    'services_count' => $servicesCount,
                    'total_labor_cost' => $totalLaborCost,
                    'updated_at' => now(),
                ]);

            Log::info("SyncMechanicReports: Updated report #{$report->id} for mechanic #{$mechanic->id}");
        } else {
            // Create new report
            $reportId = DB::table('mechanic_reports')->insertGetId([
                'mechanic_id' => $mechanic->id,
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'services_count' => $servicesCount,
                'total_labor_cost' => $totalLaborCost,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("SyncMechanicReports: Created new report #{$reportId} for mechanic #{$mechanic->id}");
        }
    }

    /**
     * Validate all mechanic reports.
     */
    private function validateAllMechanicReports()
    {
        // Get all mechanic reports
        $reports = DB::table('mechanic_reports')->get();

        $this->info("Found {$reports->count()} mechanic reports to validate");
        Log::info("SyncMechanicReports: Found {$reports->count()} mechanic reports to validate");

        $bar = $this->output->createProgressBar($reports->count());
        $bar->start();

        foreach ($reports as $report) {
            // Calculate actual values
            $totalLaborCost = DB::table('mechanic_service')
                ->join('services', 'mechanic_service.service_id', '=', 'services.id')
                ->where('mechanic_service.mechanic_id', $report->mechanic_id)
                ->where('mechanic_service.week_start', $report->week_start)
                ->where('mechanic_service.week_end', $report->week_end)
                ->where('services.status', 'completed')
                ->sum('mechanic_service.labor_cost');

            $servicesCount = DB::table('mechanic_service')
                ->join('services', 'mechanic_service.service_id', '=', 'services.id')
                ->where('mechanic_service.mechanic_id', $report->mechanic_id)
                ->where('mechanic_service.week_start', $report->week_start)
                ->where('mechanic_service.week_end', $report->week_end)
                ->where('services.status', 'completed')
                ->count();

            // Check if values match
            if ($report->services_count != $servicesCount || $report->total_labor_cost != $totalLaborCost) {
                Log::info("SyncMechanicReports: Mismatch found for report #{$report->id}", [
                    'mechanic_id' => $report->mechanic_id,
                    'week_start' => $report->week_start,
                    'week_end' => $report->week_end,
                    'current_services_count' => $report->services_count,
                    'actual_services_count' => $servicesCount,
                    'current_total_labor_cost' => $report->total_labor_cost,
                    'actual_total_labor_cost' => $totalLaborCost,
                ]);

                // Update report with correct values
                DB::table('mechanic_reports')
                    ->where('id', $report->id)
                    ->update([
                        'services_count' => $servicesCount,
                        'total_labor_cost' => $totalLaborCost,
                        'updated_at' => now(),
                    ]);

                Log::info("SyncMechanicReports: Updated report #{$report->id} with correct values");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
