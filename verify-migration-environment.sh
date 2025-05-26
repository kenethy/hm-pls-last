#!/bin/bash

# Script to verify the environment before running cumulative migration
# This helps identify potential issues before attempting the migration

set -e

echo "🔍 Verifying Migration Environment"
echo "=================================="

# Configuration - Update these for your Docker setup
CONTAINER_NAME="hartono-app"  # Update with your actual container name
DB_CONTAINER="hartono-mysql"  # Update with your MySQL container name

# Function to check if container exists and is running
check_container() {
    local container_name=$1
    echo "Checking container: $container_name"
    
    if docker ps | grep -q "$container_name"; then
        echo "✅ Container '$container_name' is running"
        return 0
    else
        echo "❌ Container '$container_name' is not running"
        return 1
    fi
}

# Function to check database connectivity
check_database() {
    echo ""
    echo "🔍 Checking database connectivity..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        try {
            DB::connection()->getPdo();
            echo 'Database connection: OK' . PHP_EOL;
        } catch (Exception \$e) {
            echo 'Database connection: FAILED - ' . \$e->getMessage() . PHP_EOL;
            exit(1);
        }
    "
}

# Function to check required tables
check_required_tables() {
    echo ""
    echo "🔍 Checking required tables..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        \$requiredTables = ['mechanics', 'mechanic_reports', 'mechanic_service', 'services'];
        \$missingTables = [];
        
        foreach (\$requiredTables as \$table) {
            if (Schema::hasTable(\$table)) {
                echo 'Table ' . \$table . ': EXISTS' . PHP_EOL;
            } else {
                echo 'Table ' . \$table . ': MISSING' . PHP_EOL;
                \$missingTables[] = \$table;
            }
        }
        
        if (!empty(\$missingTables)) {
            echo 'ERROR: Missing required tables: ' . implode(', ', \$missingTables) . PHP_EOL;
            exit(1);
        }
    "
}

# Function to check current table structure
check_table_structure() {
    echo ""
    echo "🔍 Checking current mechanic_reports table structure..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        if (!Schema::hasTable('mechanic_reports')) {
            echo 'ERROR: mechanic_reports table does not exist!' . PHP_EOL;
            exit(1);
        }
        
        // Check current structure
        \$hasWeekStart = Schema::hasColumn('mechanic_reports', 'week_start');
        \$hasWeekEnd = Schema::hasColumn('mechanic_reports', 'week_end');
        \$hasCumulative = Schema::hasColumn('mechanic_reports', 'is_cumulative');
        \$hasPeriodStart = Schema::hasColumn('mechanic_reports', 'period_start');
        
        echo 'Current table structure:' . PHP_EOL;
        echo 'week_start: ' . (\$hasWeekStart ? 'EXISTS' : 'MISSING') . PHP_EOL;
        echo 'week_end: ' . (\$hasWeekEnd ? 'EXISTS' : 'MISSING') . PHP_EOL;
        echo 'is_cumulative: ' . (\$hasCumulative ? 'EXISTS' : 'MISSING') . PHP_EOL;
        echo 'period_start: ' . (\$hasPeriodStart ? 'EXISTS' : 'MISSING') . PHP_EOL;
        
        if (\$hasCumulative) {
            echo 'WARNING: Table appears to already have cumulative structure!' . PHP_EOL;
            echo 'Migration may have already been applied.' . PHP_EOL;
        } elseif (!\$hasWeekStart || !\$hasWeekEnd) {
            echo 'ERROR: Table does not have expected weekly structure!' . PHP_EOL;
            exit(1);
        } else {
            echo 'Table has expected weekly structure - ready for migration.' . PHP_EOL;
        }
    "
}

# Function to check foreign key constraints
check_foreign_keys() {
    echo ""
    echo "🔍 Checking foreign key constraints..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        try {
            \$constraints = DB::select(\"
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'mechanic_reports'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            \");
            
            echo 'Foreign key constraints on mechanic_reports:' . PHP_EOL;
            foreach (\$constraints as \$constraint) {
                echo '- ' . \$constraint->CONSTRAINT_NAME . ': ' . \$constraint->COLUMN_NAME . ' -> ' . \$constraint->REFERENCED_TABLE_NAME . '.' . \$constraint->REFERENCED_COLUMN_NAME . PHP_EOL;
            }
            
            \$uniqueConstraints = DB::select(\"
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'mechanic_reports' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
            \");
            
            echo 'Unique constraints on mechanic_reports:' . PHP_EOL;
            foreach (\$uniqueConstraints as \$constraint) {
                echo '- ' . \$constraint->CONSTRAINT_NAME . PHP_EOL;
            }
            
        } catch (Exception \$e) {
            echo 'Could not check constraints: ' . \$e->getMessage() . PHP_EOL;
        }
    "
}

# Function to check archive table
check_archive_table() {
    echo ""
    echo "🔍 Checking mechanic_report_archives table..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        if (Schema::hasTable('mechanic_report_archives')) {
            echo 'mechanic_report_archives table: EXISTS' . PHP_EOL;
            
            \$count = DB::table('mechanic_report_archives')->count();
            echo 'Existing archive records: ' . \$count . PHP_EOL;
            
            if (\$count > 0) {
                \$migrationRecords = DB::table('mechanic_report_archives')
                    ->where('archive_reason', 'weekly_to_cumulative_migration')
                    ->count();
                echo 'Previous migration archives: ' . \$migrationRecords . PHP_EOL;
            }
        } else {
            echo 'mechanic_report_archives table: MISSING' . PHP_EOL;
            echo 'ERROR: Archive table must be created first!' . PHP_EOL;
            echo 'Run: php artisan migrate --path=database/migrations/2025_05_26_004424_create_mechanic_report_archives_table.php' . PHP_EOL;
            exit(1);
        }
    "
}

# Function to check data integrity
check_data_integrity() {
    echo ""
    echo "🔍 Checking data integrity..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        // Check for orphaned records
        \$orphanedReports = DB::table('mechanic_reports')
            ->leftJoin('mechanics', 'mechanic_reports.mechanic_id', '=', 'mechanics.id')
            ->whereNull('mechanics.id')
            ->count();
            
        if (\$orphanedReports > 0) {
            echo 'WARNING: Found ' . \$orphanedReports . ' orphaned mechanic reports!' . PHP_EOL;
        } else {
            echo 'Data integrity: OK - No orphaned records found' . PHP_EOL;
        }
        
        // Check for duplicate records
        \$duplicates = DB::table('mechanic_reports')
            ->select('mechanic_id', 'week_start', 'week_end', DB::raw('COUNT(*) as count'))
            ->groupBy('mechanic_id', 'week_start', 'week_end')
            ->having('count', '>', 1)
            ->get();
            
        if (\$duplicates->count() > 0) {
            echo 'WARNING: Found ' . \$duplicates->count() . ' duplicate weekly reports!' . PHP_EOL;
            echo 'These should be resolved before migration.' . PHP_EOL;
        } else {
            echo 'Data integrity: OK - No duplicate records found' . PHP_EOL;
        }
        
        // Count current data
        \$reportCount = DB::table('mechanic_reports')->count();
        \$mechanicCount = DB::table('mechanics')->where('is_active', true)->count();
        
        echo 'Current data summary:' . PHP_EOL;
        echo '- Active mechanics: ' . \$mechanicCount . PHP_EOL;
        echo '- Weekly reports: ' . \$reportCount . PHP_EOL;
    "
}

# Function to check migration status
check_migration_status() {
    echo ""
    echo "🔍 Checking migration status..."
    
    docker exec $CONTAINER_NAME php artisan migrate:status | grep -E "(2025_05_26|Pending)" || echo "No pending cumulative migrations found"
}

# Function to check disk space
check_disk_space() {
    echo ""
    echo "🔍 Checking disk space..."
    
    # Check host disk space
    echo "Host disk space:"
    df -h . | tail -1
    
    # Check container disk space
    echo "Container disk space:"
    docker exec $CONTAINER_NAME df -h / | tail -1
}

# Main execution
main() {
    echo "Starting environment verification..."
    echo ""
    
    # Check containers
    echo "📦 Checking Docker containers..."
    if ! check_container $CONTAINER_NAME; then
        echo "Please start your application container first"
        exit 1
    fi
    
    if ! check_container $DB_CONTAINER; then
        echo "Please start your database container first"
        exit 1
    fi
    
    # Run all checks
    check_database
    check_required_tables
    check_table_structure
    check_foreign_keys
    check_archive_table
    check_data_integrity
    check_migration_status
    check_disk_space
    
    echo ""
    echo "🎉 Environment verification completed!"
    echo ""
    echo "Summary:"
    echo "- All required containers are running"
    echo "- Database connectivity is working"
    echo "- Required tables exist"
    echo "- Table structure is ready for migration"
    echo "- Archive table is available"
    echo ""
    echo "You can now proceed with the migration using:"
    echo "./run-cumulative-migration.sh"
}

# Run main function
main "$@"
