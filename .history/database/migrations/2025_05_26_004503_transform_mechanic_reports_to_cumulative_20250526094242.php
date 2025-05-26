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

        // Step 2: Clear existing reports
        DB::table('mechanic_reports')->truncate();

        // Step 3: Modify the mechanic_reports table structure safely
        // First, add new columns without touching existing constraints
        Schema::table('mechanic_reports', function (Blueprint $table) {
            // Add new columns for cumulative tracking
            $table->boolean('is_cumulative')->default(true)->after('total_labor_cost');
            $table->timestamp('last_calculated_at')->nullable()->after('is_cumulative');
            $table->timestamp('period_reset_at')->nullable()->after('last_calculated_at');

            // Add new period columns (will replace week columns later)
            $table->date('period_start')->nullable()->after('period_reset_at');
            $table->date('period_end')->nullable()->after('period_start');
        });

        // Step 4: Copy data from week columns to period columns
        DB::statement('UPDATE mechanic_reports SET period_start = week_start, period_end = week_end');

        // Step 5: Drop the old unique constraint and columns safely
        Schema::table('mechanic_reports', function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique('mechanic_reports_mechanic_id_week_start_week_end_unique');
        });

        // Step 6: Drop the old week columns
        Schema::table('mechanic_reports', function (Blueprint $table) {
            $table->dropColumn(['week_start', 'week_end']);
        });

        // Step 7: Add new unique constraint for cumulative reports
        Schema::table('mechanic_reports', function (Blueprint $table) {
            // Add unique constraint for one cumulative report per mechanic
            $table->unique(['mechanic_id', 'is_cumulative'], 'mechanic_reports_mechanic_cumulative_unique');
        });

        // Step 4: Generate initial cumulative reports for each mechanic
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
        // Step 1: Restore original structure
        Schema::table('mechanic_reports', function (Blueprint $table) {
            // Remove cumulative fields
            $table->dropUnique(['mechanic_id', 'is_cumulative']);
            $table->dropColumn(['is_cumulative', 'last_calculated_at', 'period_reset_at']);

            // Rename back to week fields
            $table->renameColumn('period_start', 'week_start');
            $table->renameColumn('period_end', 'week_end');

            // Make week dates required again
            $table->date('week_start')->nullable(false)->change();
            $table->date('week_end')->nullable(false)->change();

            // Restore unique constraint
            $table->unique(['mechanic_id', 'week_start', 'week_end']);
        });

        // Step 2: Clear cumulative reports
        DB::table('mechanic_reports')->truncate();

        // Step 3: Restore archived weekly reports
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
