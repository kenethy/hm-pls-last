# Cumulative Mechanic Reports Migration Guide

This guide helps you safely migrate from weekly-based to cumulative mechanic reporting in your Docker VPS environment.

## 🚨 Important Notes

- **This migration transforms your database structure permanently**
- **All existing weekly data will be archived and preserved**
- **The migration uses MySQL-safe techniques to avoid foreign key constraint issues**
- **Always backup your database before proceeding**

## 📋 Prerequisites

1. **Docker Environment**: Ensure your Docker containers are running
2. **Database Access**: You need MySQL root password
3. **Archive Table**: The `mechanic_report_archives` table must exist

## 🔧 Configuration

Before running any scripts, update the container names in each script:

```bash
# Edit these variables in all scripts:
CONTAINER_NAME="hartono-app"    # Your Laravel app container name
DB_CONTAINER="hartono-mysql"    # Your MySQL container name
```

## 📝 Step-by-Step Migration Process

### Step 1: Set Environment Variables

```bash
export MYSQL_ROOT_PASSWORD=your_mysql_root_password
```

### Step 2: Verify Environment

```bash
./verify-migration-environment.sh
```

This script checks:
- ✅ Container status
- ✅ Database connectivity
- ✅ Required tables
- ✅ Current table structure
- ✅ Foreign key constraints
- ✅ Archive table availability
- ✅ Data integrity
- ✅ Disk space

### Step 3: Mark Problematic Migration as Complete

If you have the `service_report_checklist_items_table` migration issue:

```bash
# Connect to your MySQL container
docker exec -it hartono-mysql mysql -u root -p

# In MySQL console:
USE hartono_motor;
INSERT INTO migrations (migration, batch) VALUES 
('2025_05_20_000001_create_service_report_checklist_items_table', 6);
exit;
```

### Step 4: Run the Migration

```bash
./run-cumulative-migration.sh
```

This script will:
1. 📦 Create database backup
2. 🔍 Run pre-migration checks
3. ⚠️ Ask for confirmation
4. 🔄 Execute the migration
5. ✅ Verify results
6. 🧪 Test management commands

### Step 5: Verify Success

After migration, verify the system:

```bash
# Check system status
docker exec hartono-app php artisan mechanic:manage-reports status

# Test recalculation
docker exec hartono-app php artisan mechanic:manage-reports recalculate --force

# Clear caches
docker exec hartono-app php artisan optimize:clear
```

## 🔄 Rollback (If Needed)

If you need to rollback to the weekly system:

```bash
./rollback-cumulative-migration.sh
```

**⚠️ Warning**: Rollback will lose all cumulative data and restore weekly reports from archive.

## 🛠️ Migration Technical Details

### What the Migration Does

1. **Archives Weekly Data**: All existing weekly reports are moved to `mechanic_report_archives`
2. **Creates New Structure**: A new table with cumulative fields is created
3. **Replaces Old Table**: Uses `SET FOREIGN_KEY_CHECKS=0` to safely replace the table
4. **Generates Cumulative Reports**: Creates initial cumulative reports for all active mechanics

### New Table Structure

```sql
-- New cumulative structure
CREATE TABLE mechanic_reports (
    id BIGINT PRIMARY KEY,
    mechanic_id BIGINT,
    period_start DATE NULL,           -- Replaces week_start
    period_end DATE NULL,             -- Replaces week_end
    services_count INT DEFAULT 0,
    total_labor_cost DECIMAL(10,2) DEFAULT 0,
    notes TEXT NULL,
    is_paid BOOLEAN DEFAULT FALSE,
    paid_at TIMESTAMP NULL,
    is_cumulative BOOLEAN DEFAULT TRUE,     -- NEW
    last_calculated_at TIMESTAMP NULL,     -- NEW
    period_reset_at TIMESTAMP NULL,        -- NEW
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(id),
    UNIQUE KEY (mechanic_id, is_cumulative)
);
```

### Foreign Key Constraint Solution

The migration avoids the "Cannot drop index needed in a foreign key constraint" error by:

1. **Creating New Table**: Instead of modifying existing table
2. **Disabling FK Checks**: Temporarily disables foreign key checks
3. **Table Replacement**: Drops old table and renames new one
4. **Re-enabling FK Checks**: Restores foreign key enforcement

## 🧪 Testing After Migration

### 1. Test Filament Admin Interface

- Navigate to `/admin/mechanic-reports`
- Verify cumulative reports display correctly
- Test "Perbarui" (Recalculate) button
- Test "Reset" button functionality

### 2. Test Service Completion

- Complete a service with mechanics assigned
- Verify cumulative reports update automatically
- Check that labor costs are properly calculated

### 3. Test Management Commands

```bash
# Check system status
docker exec hartono-app php artisan mechanic:manage-reports status

# Recalculate all reports
docker exec hartono-app php artisan mechanic:manage-reports recalculate

# Reset a specific mechanic (use with caution)
docker exec hartono-app php artisan mechanic:manage-reports reset --mechanic=1
```

## 📊 Archive Access

Historical weekly data is preserved in `mechanic_report_archives`:

- Access via Filament: `/admin/mechanic-report-archives`
- Read-only interface for historical data
- Filterable by date range and archive reason

## 🚨 Troubleshooting

### Migration Fails with Foreign Key Error

If you still get foreign key errors:

1. Check that you're using the updated migration file
2. Verify MySQL version supports `SET FOREIGN_KEY_CHECKS=0`
3. Ensure no other processes are using the table during migration

### Rollback Fails

If rollback fails:

1. Check that archive data exists
2. Verify database backup is available
3. Manually restore from backup if needed

### Performance Issues

If migration is slow:

1. Check disk space
2. Monitor MySQL performance during migration
3. Consider running during low-traffic periods

## 📞 Support

If you encounter issues:

1. Check the script output for specific error messages
2. Verify all prerequisites are met
3. Ensure container names are correctly configured
4. Check MySQL logs for detailed error information

## 🎯 Post-Migration Checklist

- [ ] Migration completed successfully
- [ ] System status shows cumulative reports
- [ ] Filament admin interface works
- [ ] Service completion updates reports
- [ ] Management commands work
- [ ] Historical data accessible in archives
- [ ] Application caches cleared
- [ ] Backup created and verified
