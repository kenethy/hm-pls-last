<?php

namespace App\Console\Commands;

use App\Models\Mechanic;
use App\Models\MechanicReport;
use Illuminate\Console\Command;

class RecoverCumulativeReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mechanic:recover-reports {--force : Force recovery without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover lost cumulative mechanic reports and fix the recalculation bug';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚨 Recovering Cumulative Mechanic Reports');
        $this->info('==========================================');
        $this->newLine();

        // Check current state
        $this->info('📊 Checking current system state...');
        $cumulativeCount = MechanicReport::where('is_cumulative', true)->count();
        $totalMechanics = Mechanic::count();
        $activeMechanics = Mechanic::where('is_active', true)->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Mechanics', $totalMechanics],
                ['Active Mechanics', $activeMechanics],
                ['Cumulative Reports', $cumulativeCount],
            ]
        );

        if ($cumulativeCount >= $activeMechanics && $activeMechanics > 0) {
            $this->info('✅ System appears healthy. All active mechanics have cumulative reports.');
            if (!$this->option('force') && !$this->confirm('Do you still want to proceed with recovery?')) {
                $this->info('Recovery cancelled.');
                return 0;
            }
        }

        if (!$this->option('force') && !$this->confirm('Proceed with cumulative reports recovery?')) {
            $this->info('Recovery cancelled.');
            return 0;
        }

        $this->newLine();
        $this->info('🔄 Starting recovery process...');

        $mechanics = Mechanic::all();
        $created = 0;
        $updated = 0;
        $errors = 0;

        $this->withProgressBar($mechanics, function ($mechanic) use (&$created, &$updated, &$errors) {
            try {
                // Check if cumulative report exists
                $existingReport = $mechanic->cumulativeReport()->first();

                if ($existingReport) {
                    // Update existing report
                    $existingReport->recalculateCumulative();
                    $updated++;
                } else {
                    // Create new cumulative report
                    $mechanic->getOrCreateCumulativeReport();
                    $created++;
                }

                // Verify the report exists after operation
                $verifyReport = $mechanic->cumulativeReport()->first();
                if (!$verifyReport) {
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->error("Error processing {$mechanic->name}: " . $e->getMessage());
                $errors++;
            }
        });

        $this->newLine(2);
        $this->info('📊 Recovery Summary:');
        $this->table(
            ['Operation', 'Count'],
            [
                ['Created', $created],
                ['Updated', $updated],
                ['Errors', $errors],
            ]
        );

        // Final verification
        $finalCumulativeCount = MechanicReport::where('is_cumulative', true)->count();
        $this->info("Final cumulative reports count: {$finalCumulativeCount}");

        if ($errors === 0) {
            $this->info('✅ Recovery completed successfully!');
            $this->info('You can now test the "Perbarui" button in the Filament admin interface.');
        } else {
            $this->warn("⚠️  Recovery completed with {$errors} errors. Please check the logs.");
        }

        return 0;
    }
}
