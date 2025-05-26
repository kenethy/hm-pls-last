#!/bin/bash

# Script to rollback the cumulative mechanic reports migration in Docker VPS
# This script safely reverts to the weekly reporting system

set -e

echo "🔄 Rolling Back Cumulative Mechanic Reports Migration"
echo "====================================================="

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

# Function to backup database before rollback
backup_database() {
    echo ""
    echo "📦 Creating database backup before rollback..."
    
    # Create backup directory if it doesn't exist
    mkdir -p ./backups
    
    # Generate backup filename with timestamp
    BACKUP_FILE="./backups/pre_rollback_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    # Create backup
    docker exec $DB_CONTAINER mysqldump -u root -p$MYSQL_ROOT_PASSWORD hartono_motor > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        echo "✅ Database backup created: $BACKUP_FILE"
    else
        echo "❌ Failed to create database backup"
        exit 1
    fi
}

# Function to check if rollback is possible
check_rollback_possible() {
    echo ""
    echo "🔍 Checking if rollback is possible..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        // Check if cumulative table exists
        if (!Schema::hasTable('mechanic_reports')) {
            echo 'ERROR: mechanic_reports table does not exist!' . PHP_EOL;
            exit(1);
        }
        
        // Check if it's the cumulative version
        \$hasCumulative = Schema::hasColumn('mechanic_reports', 'is_cumulative');
        if (!\$hasCumulative) {
            echo 'ERROR: Table appears to already be in weekly format!' . PHP_EOL;
            exit(1);
        }
        
        // Check if archive table exists with migration data
        if (!Schema::hasTable('mechanic_report_archives')) {
            echo 'ERROR: mechanic_report_archives table does not exist!' . PHP_EOL;
            echo 'Cannot rollback without archived data!' . PHP_EOL;
            exit(1);
        }
        
        \$archiveCount = DB::table('mechanic_report_archives')
            ->where('archive_reason', 'weekly_to_cumulative_migration')
            ->count();
            
        if (\$archiveCount === 0) {
            echo 'ERROR: No archived weekly data found!' . PHP_EOL;
            echo 'Cannot rollback without original weekly data!' . PHP_EOL;
            exit(1);
        }
        
        echo 'Rollback is possible. Found ' . \$archiveCount . ' archived weekly reports.' . PHP_EOL;
    "
}

# Function to perform the rollback
perform_rollback() {
    echo ""
    echo "🔄 Performing rollback..."
    
    # Run the rollback migration
    docker exec $CONTAINER_NAME php artisan migrate:rollback --path=database/migrations/2025_05_26_004503_transform_mechanic_reports_to_cumulative.php --force
    
    if [ $? -eq 0 ]; then
        echo "✅ Rollback completed successfully!"
    else
        echo "❌ Rollback failed!"
        echo "Check the error messages above for details"
        exit 1
    fi
}

# Function to verify rollback results
verify_rollback() {
    echo ""
    echo "🔍 Verifying rollback results..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        // Check if table is back to weekly format
        if (Schema::hasTable('mechanic_reports')) {
            \$hasWeekStart = Schema::hasColumn('mechanic_reports', 'week_start');
            \$hasWeekEnd = Schema::hasColumn('mechanic_reports', 'week_end');
            \$hasCumulative = Schema::hasColumn('mechanic_reports', 'is_cumulative');
            
            echo 'Weekly table structure:' . PHP_EOL;
            echo 'week_start: ' . (\$hasWeekStart ? 'OK' : 'MISSING') . PHP_EOL;
            echo 'week_end: ' . (\$hasWeekEnd ? 'OK' : 'MISSING') . PHP_EOL;
            echo 'is_cumulative: ' . (\$hasCumulative ? 'STILL EXISTS (ERROR)' : 'REMOVED (OK)') . PHP_EOL;
            
            // Count restored records
            \$reportCount = DB::table('mechanic_reports')->count();
            echo 'Restored weekly reports: ' . \$reportCount . ' records' . PHP_EOL;
            
            // Check unique constraint
            try {
                \$constraints = DB::select(\"
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'mechanic_reports' 
                    AND CONSTRAINT_TYPE = 'UNIQUE'
                \");
                
                echo 'Unique constraints restored: ' . count(\$constraints) . PHP_EOL;
                foreach (\$constraints as \$constraint) {
                    echo '- ' . \$constraint->CONSTRAINT_NAME . PHP_EOL;
                }
            } catch (Exception \$e) {
                echo 'Could not check constraints: ' . \$e->getMessage() . PHP_EOL;
            }
        }
    "
}

# Function to clean up migration record
cleanup_migration_record() {
    echo ""
    echo "🧹 Cleaning up migration record..."
    
    docker exec $CONTAINER_NAME php artisan tinker --execute="
        // Remove the migration record so it can be run again if needed
        \$deleted = DB::table('migrations')
            ->where('migration', '2025_05_26_004503_transform_mechanic_reports_to_cumulative')
            ->delete();
            
        if (\$deleted > 0) {
            echo 'Migration record removed. Migration can be run again if needed.' . PHP_EOL;
        } else {
            echo 'No migration record found to remove.' . PHP_EOL;
        }
    "
}

# Main execution
main() {
    echo "Starting rollback process..."
    
    # Check if containers are running
    check_container $CONTAINER_NAME
    check_container $DB_CONTAINER
    
    # Get MySQL root password (you may need to set this)
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        echo "Please set MYSQL_ROOT_PASSWORD environment variable"
        echo "Example: export MYSQL_ROOT_PASSWORD=your_password"
        exit 1
    fi
    
    # Check if rollback is possible
    check_rollback_possible
    
    # Ask for confirmation
    echo ""
    echo "⚠️  WARNING: This will rollback your mechanic reports to the weekly system"
    echo "All cumulative data will be lost and weekly reports will be restored from archive"
    echo ""
    read -p "Do you want to continue with the rollback? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        echo "Rollback cancelled"
        exit 0
    fi
    
    # Create backup before rollback
    backup_database
    
    # Perform the rollback
    perform_rollback
    
    # Verify results
    verify_rollback
    
    # Clean up migration record
    cleanup_migration_record
    
    echo ""
    echo "🎉 Rollback completed successfully!"
    echo "Your mechanic reports system has been reverted to weekly reporting"
    echo "Weekly data has been restored from the archive"
    echo ""
    echo "Note: You may need to update your Filament resources and models"
    echo "to work with the weekly system again if you plan to keep this setup"
}

# Run main function
main "$@"
