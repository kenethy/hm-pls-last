#!/bin/bash

# Script to safely run the cumulative mechanic reports migration in Docker VPS
# This script handles the MySQL foreign key constraint issues

set -e

echo "🚀 Starting Cumulative Mechanic Reports Migration"
echo "=================================================="

# Configuration - Update these for your Docker setup
CONTAINER_NAME="hartono-app"  # Update with your actual container name
DB_CONTAINER="hartono-mysql"  # Update with your MySQL container name

# Function to check if container exists and is running
check_container() {
    local container_name=$1
    if ! docker ps | grep -q "$container_name"; then
        echo "❌ Error: Container '$container_name' is not running"
        echo "Please start your Docker containers first"
        exit 1
    fi
    echo "✅ Container '$container_name' is running"
}

# Function to backup database
backup_database() {
    echo ""
    echo "📦 Creating database backup..."
    
    # Create backup directory if it doesn't exist
    mkdir -p ./backups
    
    # Generate backup filename with timestamp
    BACKUP_FILE="./backups/mechanic_reports_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    # Create backup
    docker exec $DB_CONTAINER mysqldump -u root -p$MYSQL_ROOT_PASSWORD hartono_motor > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        echo "✅ Database backup created: $BACKUP_FILE"
    else
        echo "❌ Failed to create database backup"
        exit 1
    fi
}

# Function to check migration status
check_migration_status() {
    echo ""
    echo "🔍 Checking migration status..."
    
    docker exec $CONTAINER_NAME php artisan migrate:status | grep -E "(2025_05_26|Pending)" || true
}

# Function to run pre-migration checks
pre_migration_checks() {
    echo ""
    echo "🔍 Running pre-migration checks..."
    
    # Check if mechanic_report_archives table exists
    echo "Checking if mechanic_report_archives table exists..."
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        if (Schema::hasTable('mechanic_report_archives')) {
            echo 'mechanic_report_archives table exists' . PHP_EOL;
        } else {
            echo 'ERROR: mechanic_report_archives table does not exist!' . PHP_EOL;
            echo 'Please run the archive table migration first' . PHP_EOL;
            exit(1);
        }
    "
    
    # Check current mechanic_reports structure
    echo "Checking current mechanic_reports table structure..."
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        if (Schema::hasTable('mechanic_reports')) {
            \$hasWeekStart = Schema::hasColumn('mechanic_reports', 'week_start');
            \$hasWeekEnd = Schema::hasColumn('mechanic_reports', 'week_end');
            \$hasCumulative = Schema::hasColumn('mechanic_reports', 'is_cumulative');
            
            echo 'week_start column: ' . (\$hasWeekStart ? 'EXISTS' : 'MISSING') . PHP_EOL;
            echo 'week_end column: ' . (\$hasWeekEnd ? 'EXISTS' : 'MISSING') . PHP_EOL;
            echo 'is_cumulative column: ' . (\$hasCumulative ? 'EXISTS' : 'MISSING') . PHP_EOL;
            
            if (\$hasCumulative) {
                echo 'Migration appears to already be applied!' . PHP_EOL;
                exit(1);
            }
        } else {
            echo 'ERROR: mechanic_reports table does not exist!' . PHP_EOL;
            exit(1);
        }
    "
}

# Function to run the migration
run_migration() {
    echo ""
    echo "🔄 Running cumulative migration..."
    
    # Run the specific migration
    docker exec $CONTAINER_NAME php artisan migrate --path=database/migrations/2025_05_26_004503_transform_mechanic_reports_to_cumulative.php --force
    
    if [ $? -eq 0 ]; then
        echo "✅ Migration completed successfully!"
    else
        echo "❌ Migration failed!"
        echo "Check the error messages above for details"
        exit 1
    fi
}

# Function to verify migration results
verify_migration() {
    echo ""
    echo "🔍 Verifying migration results..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        // Check new table structure
        if (Schema::hasTable('mechanic_reports')) {
            \$hasPeriodStart = Schema::hasColumn('mechanic_reports', 'period_start');
            \$hasPeriodEnd = Schema::hasColumn('mechanic_reports', 'period_end');
            \$hasCumulative = Schema::hasColumn('mechanic_reports', 'is_cumulative');
            \$hasLastCalculated = Schema::hasColumn('mechanic_reports', 'last_calculated_at');
            \$hasResetAt = Schema::hasColumn('mechanic_reports', 'period_reset_at');
            
            echo 'New table structure:' . PHP_EOL;
            echo 'period_start: ' . (\$hasPeriodStart ? 'OK' : 'MISSING') . PHP_EOL;
            echo 'period_end: ' . (\$hasPeriodEnd ? 'OK' : 'MISSING') . PHP_EOL;
            echo 'is_cumulative: ' . (\$hasCumulative ? 'OK' : 'MISSING') . PHP_EOL;
            echo 'last_calculated_at: ' . (\$hasLastCalculated ? 'OK' : 'MISSING') . PHP_EOL;
            echo 'period_reset_at: ' . (\$hasResetAt ? 'OK' : 'MISSING') . PHP_EOL;
            
            // Check if old columns are gone
            \$hasWeekStart = Schema::hasColumn('mechanic_reports', 'week_start');
            \$hasWeekEnd = Schema::hasColumn('mechanic_reports', 'week_end');
            
            echo 'Old columns removed:' . PHP_EOL;
            echo 'week_start: ' . (\$hasWeekStart ? 'STILL EXISTS (ERROR)' : 'REMOVED (OK)') . PHP_EOL;
            echo 'week_end: ' . (\$hasWeekEnd ? 'STILL EXISTS (ERROR)' : 'REMOVED (OK)') . PHP_EOL;
            
            // Count records
            \$reportCount = DB::table('mechanic_reports')->count();
            \$archiveCount = DB::table('mechanic_report_archives')->count();
            
            echo 'Data counts:' . PHP_EOL;
            echo 'mechanic_reports: ' . \$reportCount . ' records' . PHP_EOL;
            echo 'mechanic_report_archives: ' . \$archiveCount . ' records' . PHP_EOL;
        }
    "
}

# Function to test the management command
test_management_command() {
    echo ""
    echo "🧪 Testing management command..."
    
    docker exec $CONTAINER_NAME php artisan mechanic:manage-reports status
}

# Main execution
main() {
    echo "Starting migration process..."
    
    # Check if containers are running
    check_container $CONTAINER_NAME
    check_container $DB_CONTAINER
    
    # Get MySQL root password (you may need to set this)
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        echo "Please set MYSQL_ROOT_PASSWORD environment variable"
        echo "Example: export MYSQL_ROOT_PASSWORD=your_password"
        exit 1
    fi
    
    # Run pre-migration checks
    pre_migration_checks
    
    # Ask for confirmation
    echo ""
    echo "⚠️  WARNING: This will transform your mechanic reports from weekly to cumulative system"
    echo "All existing weekly data will be archived and the table structure will change"
    echo ""
    read -p "Do you want to continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        echo "Migration cancelled"
        exit 0
    fi
    
    # Create backup
    backup_database
    
    # Check current migration status
    check_migration_status
    
    # Run the migration
    run_migration
    
    # Verify results
    verify_migration
    
    # Test management command
    test_management_command
    
    echo ""
    echo "🎉 Migration completed successfully!"
    echo "Your mechanic reports system has been transformed to cumulative reporting"
    echo "Historical weekly data has been preserved in the mechanic_report_archives table"
    echo ""
    echo "Next steps:"
    echo "1. Test the Filament admin interface"
    echo "2. Verify that service completion updates cumulative reports"
    echo "3. Use 'php artisan mechanic:manage-reports' commands for management"
}

# Run main function
main "$@"
