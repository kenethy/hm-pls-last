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
     * Validate all cumulative reports.
     */
    private function validateAllCumulativeReports()
    {
        // Get all cumulative reports
        $reports = MechanicReport::where('is_cumulative', true)->get();

        $this->info("Found {$reports->count()} cumulative reports to validate");
        Log::info("SyncMechanicReports: Found {$reports->count()} cumulative reports to validate");

        if ($reports->count() === 0) {
            $this->info("No cumulative reports found. Creating reports for all active mechanics...");
            $this->rebuildAllCumulativeReports();
            return;
        }

        $bar = $this->output->createProgressBar($reports->count());
        $bar->start();

        $updated = 0;
        $errors = 0;

        foreach ($reports as $report) {
            try {
                // Store current values for comparison
                $currentServicesCount = $report->services_count;
                $currentTotalLaborCost = $report->total_labor_cost;

                // Recalculate the report
                $report->recalculateCumulative();

                // Check if values changed
                if ($report->services_count != $currentServicesCount || $report->total_labor_cost != $currentTotalLaborCost) {
                    $updated++;
                    Log::info("SyncMechanicReports: Updated cumulative report #{$report->id} for mechanic #{$report->mechanic_id}", [
                        'old_services_count' => $currentServicesCount,
                        'new_services_count' => $report->services_count,
                        'old_total_labor_cost' => $currentTotalLaborCost,
                        'new_total_labor_cost' => $report->total_labor_cost,
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error("SyncMechanicReports: Error validating cumulative report #{$report->id}: " . $e->getMessage(), [
                    'mechanic_id' => $report->mechanic_id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Validation completed: {$updated} updated, {$errors} errors");
        Log::info("SyncMechanicReports: Validation completed: {$updated} updated, {$errors} errors");

        // Check for mechanics without cumulative reports
        $mechanicsWithoutReports = Mechanic::where('is_active', true)
            ->whereDoesntHave('reports', function ($query) {
                $query->where('is_cumulative', true);
            })
            ->get();

        if ($mechanicsWithoutReports->count() > 0) {
            $this->info("Found {$mechanicsWithoutReports->count()} mechanics without cumulative reports. Creating them...");

            foreach ($mechanicsWithoutReports as $mechanic) {
                try {
                    $mechanic->getOrCreateCumulativeReport();
                    Log::info("SyncMechanicReports: Created missing cumulative report for mechanic #{$mechanic->id}");
                } catch (\Exception $e) {
                    Log::error("SyncMechanicReports: Error creating cumulative report for mechanic #{$mechanic->id}: " . $e->getMessage());
                }
            }
        }
    }
}
