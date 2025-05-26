<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL-safe approach: Create new table, migrate data, then replace
        // This avoids foreign key constraint issues entirely

        // Step 1: Archive all existing weekly reports
        $existingReports = DB::table('mechanic_reports')->get();
        foreach ($existingReports as $report) {
            DB::table('mechanic_report_archives')->insert([
                'mechanic_id' => $report->mechanic_id,
                'week_start' => $report->week_start,
                'week_end' => $report->week_end,
                'services_count' => $report->services_count,
                'total_labor_cost' => $report->total_labor_cost,
                'notes' => $report->notes,
                'is_paid' => $report->is_paid,
                'paid_at' => $report->paid_at,
                'archived_at' => now(),
                'archive_reason' => 'weekly_to_cumulative_migration',
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ]);
        }

        // Step 2: Create new cumulative table structure
        Schema::create('mechanic_reports_cumulative', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mechanic_id')->constrained('mechanics')->onDelete('cascade');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->integer('services_count')->default(0);
            $table->decimal('total_labor_cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->boolean('is_cumulative')->default(true);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamp('period_reset_at')->nullable();
            $table->timestamps();

            // Unique constraint for one cumulative report per mechanic
            $table->unique(['mechanic_id', 'is_cumulative'], 'mechanic_reports_cumulative_unique');
        });

        // Step 3: Disable foreign key checks temporarily for table replacement
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Step 4: Drop the old table (this removes all constraints)
        Schema::dropIfExists('mechanic_reports');

        // Step 5: Rename new table to original name
        Schema::rename('mechanic_reports_cumulative', 'mechanic_reports');

        // Step 6: Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Step 8: Generate initial cumulative reports for each mechanic
        $mechanics = DB::table('mechanics')->where('is_active', true)->get();

        foreach ($mechanics as $mechanic) {
            // Calculate cumulative statistics from all completed services
            $stats = DB::table('mechanic_service')
                ->join('services', 'mechanic_service.service_id', '=', 'services.id')
                ->where('mechanic_service.mechanic_id', $mechanic->id)
                ->where('services.status', 'completed')
                ->selectRaw('
                    COUNT(*) as total_services,
                    COALESCE(SUM(mechanic_service.labor_cost), 0) as total_labor_cost
                ')
                ->first();

            // Create cumulative report
            DB::table('mechanic_reports')->insert([
                'mechanic_id' => $mechanic->id,
                'period_start' => null, // Cumulative has no specific period
                'period_end' => null,
                'services_count' => $stats->total_services ?? 0,
                'total_labor_cost' => $stats->total_labor_cost ?? 0,
                'is_cumulative' => true,
                'last_calculated_at' => now(),
                'period_reset_at' => now(),
                'notes' => 'Laporan kumulatif - total dari semua servis',
                'is_paid' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Remove new unique constraint
        Schema::table('mechanic_reports', function (Blueprint $table) {
            $table->dropUnique('mechanic_reports_mechanic_cumulative_unique');
        });

        // Step 2: Add back week columns
        Schema::table('mechanic_reports', function (Blueprint $table) {
            $table->date('week_start')->nullable()->after('mechanic_id');
            $table->date('week_end')->nullable()->after('week_start');
        });

        // Step 3: Copy data from period columns to week columns
        DB::statement('UPDATE mechanic_reports SET week_start = period_start, week_end = period_end WHERE period_start IS NOT NULL AND period_end IS NOT NULL');

        // Step 4: Remove cumulative columns
        Schema::table('mechanic_reports', function (Blueprint $table) {
            $table->dropColumn(['is_cumulative', 'last_calculated_at', 'period_reset_at', 'period_start', 'period_end']);
        });

        // Step 5: Make week dates required and restore original unique constraint
        Schema::table('mechanic_reports', function (Blueprint $table) {
            $table->date('week_start')->nullable(false)->change();
            $table->date('week_end')->nullable(false)->change();
            $table->unique(['mechanic_id', 'week_start', 'week_end']);
        });

        // Step 6: Clear cumulative reports
        DB::table('mechanic_reports')->truncate();

        // Step 7: Restore archived weekly reports
        $archivedReports = DB::table('mechanic_report_archives')
            ->where('archive_reason', 'weekly_to_cumulative_migration')
            ->get();

        foreach ($archivedReports as $report) {
            DB::table('mechanic_reports')->insert([
                'mechanic_id' => $report->mechanic_id,
                'week_start' => $report->week_start,
                'week_end' => $report->week_end,
                'services_count' => $report->services_count,
                'total_labor_cost' => $report->total_labor_cost,
                'notes' => $report->notes,
                'is_paid' => $report->is_paid,
                'paid_at' => $report->paid_at,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ]);
        }
    }
};
