<?php
// Script to rebuild mechanic reports

use App\Models\Service;
use App\Models\Mechanic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Enable query logging for debugging
DB::enableQueryLog();

Log::info("Starting mechanic reports rebuild script");

// Step 1: Truncate the mechanic_reports table
Log::info("Truncating mechanic_reports table");
DB::table('mechanic_reports')->truncate();
Log::info("mechanic_reports table truncated");

// Step 2: Get all completed services with mechanics
Log::info("Fetching completed services with mechanics");
$completedServices = Service::where('status', 'completed')
    ->whereHas('mechanics')
    ->with('mechanics')
    ->get();

Log::info("Found {$completedServices->count()} completed services with mechanics");

// Step 3: Process each service
$count = 0;
foreach ($completedServices as $service) {
    try {
        Log::info("Processing service #{$service->id}");
        
        // Process each mechanic for this service
        foreach ($service->mechanics as $mechanic) {
            try {
                // Check if mechanic is valid
                if (!$mechanic || !$mechanic->id) {
                    Log::error("Invalid mechanic for service #{$service->id}");
                    continue;
                }
                
                // Check if pivot exists
                if (!isset($mechanic->pivot)) {
                    Log::error("Pivot data missing for mechanic #{$mechanic->id} on service #{$service->id}");
                    continue;
                }
                
                // Set week dates if not set
                $weekStart = null;
                $weekEnd = null;
                
                if (empty($mechanic->pivot->week_start) || empty($mechanic->pivot->week_end)) {
                    // Use the service completion date to determine the week
                    if ($service->completed_at) {
                        $completionDate = Carbon::parse($service->completed_at);
                    } else {
                        $completionDate = Carbon::now();
                    }
                    
                    $weekStart = $completionDate->copy()->startOfWeek()->format('Y-m-d');
                    $weekEnd = $completionDate->copy()->endOfWeek()->format('Y-m-d');
                    
                    Log::info("Setting week dates for mechanic #{$mechanic->id} on service #{$service->id}");
                    
                    $service->mechanics()->updateExistingPivot($mechanic->id, [
                        'week_start' => $weekStart,
                        'week_end' => $weekEnd,
                    ]);
                } else {
                    $weekStart = $mechanic->pivot->week_start;
                    $weekEnd = $mechanic->pivot->week_end;
                }
                
                // Set labor_cost if not set
                $laborCost = $mechanic->pivot->labor_cost ?? 0;
                if (empty($laborCost) || $laborCost == 0) {
                    // Use the service labor_cost or a default value
                    $defaultLaborCost = $service->labor_cost > 0 ? $service->labor_cost : 50000;
                    
                    Log::info("Setting default labor cost for mechanic #{$mechanic->id} on service #{$service->id}");
                    
                    $service->mechanics()->updateExistingPivot($mechanic->id, [
                        'labor_cost' => $defaultLaborCost,
                    ]);
                    
                    $laborCost = $defaultLaborCost;
                }
                
                // Update mechanic report for this week
                $report = DB::table('mechanic_reports')
                    ->where('mechanic_id', $mechanic->id)
                    ->where('week_start', $weekStart)
                    ->where('week_end', $weekEnd)
                    ->first();
                
                if ($report) {
                    // Update existing report
                    $newServicesCount = $report->services_count + 1;
                    $newTotalLaborCost = $report->total_labor_cost + $laborCost;
                    
                    DB::table('mechanic_reports')
                        ->where('id', $report->id)
                        ->update([
                            'services_count' => $newServicesCount,
                            'total_labor_cost' => $newTotalLaborCost,
                            'updated_at' => now(),
                        ]);
                    
                    Log::info("Updated report #{$report->id} for mechanic #{$mechanic->id}", [
                        'services_count' => $newServicesCount,
                        'total_labor_cost' => $newTotalLaborCost,
                    ]);
                } else {
                    // Create new report
                    $reportId = DB::table('mechanic_reports')->insertGetId([
                        'mechanic_id' => $mechanic->id,
                        'week_start' => $weekStart,
                        'week_end' => $weekEnd,
                        'services_count' => 1,
                        'total_labor_cost' => $laborCost,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info("Created new report #{$reportId} for mechanic #{$mechanic->id}", [
                        'services_count' => 1,
                        'total_labor_cost' => $laborCost,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Error processing mechanic #{$mechanic->id} on service #{$service->id}: " . $e->getMessage());
            }
        }
        
        $count++;
    } catch (\Exception $e) {
        Log::error("Error processing service #{$service->id}: " . $e->getMessage());
    }
}

// Step 4: Validate the mechanic reports
$mechanicReports = DB::table('mechanic_reports')->get();
Log::info("Created {$mechanicReports->count()} mechanic reports");

// Log the queries executed
$queries = DB::getQueryLog();
Log::info("Total queries executed: " . count($queries));

Log::info("Mechanic reports rebuild completed");
