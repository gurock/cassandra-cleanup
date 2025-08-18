# Cassandra Attachment Cleanup Script

## Overview

This script removes old attachment files that remain after running the Cassandra deprecation migration. During the migration process, attachment files are copied and renamed to a new naming convention, but the original files are not automatically deleted, resulting in doubled disk usage.

## Purpose

- **Problem**: The Cassandra deprecation migration copies and renames attachment files but leaves the original files in place
- **Solution**: This script safely identifies and removes the old files while preserving the new renamed files
- **Result**: Reduces disk usage back to expected levels after migration

## Requirements

- PHP (command line interface)
- Read/write permissions on the attachments directory
- **Important**: The Cassandra deprecation process must be completed first

## Usage

### Basic Usage
```bash
php remove_cassandra_attachments.php /path/to/attachments
```

### Dry Run (Recommended First)
```bash
php remove_cassandra_attachments.php /path/to/attachments --dry-run
```

The dry run mode will:
- List all files that would be deleted
- Save the list to `cassandra_files_to_delete.txt` in the script directory
- **Not delete any files**

## How It Works

The script identifies attachment files that need to be deleted based on their naming pattern. It first scans the attachments directory to automatically detect the instance ID prefix from files that follow the new naming convention (created during migration). Once the prefix is identified, the script preserves all files matching the new pattern (`{prefix}-{number}` or `{prefix}-{number}-thumb{1|2}`) and marks all other files for deletion. This approach ensures that only the old, duplicate files are removed while keeping the migrated files intact.

## Safety Features

- **Confirmation Prompt**: Asks for explicit confirmation before proceeding
- **Dry Run Mode**: Allows you to preview what will be deleted
- **Automatic Prefix Detection**: Eliminates manual configuration errors
- **Clear Warnings**: Reminds users to backup before proceeding

## Important Notes

⚠️ **Backup Recommendation**: Always backup your attachments directory before running this script in deletion mode

⚠️ **Prerequisites**: Ensure the Cassandra deprecation migration has been completed successfully before running this script

⚠️ **Permissions**: The script must be run with appropriate file system permissions to delete files. For dry run mode, write permissions are also required on the script directory to create the output file

## Example Output

### Dry Run
```
⚠️ This script will permanently delete old migrated files. ⚠️
Please consider backing up your attachments folder before proceeding.

Detected prefix: 1
This will LIST files in:
    /var/www/testrail/attachments
that remain after running the Cassandra deprecation script and are no longer in use.
Proceed? (y/N): y
Dry run complete. List saved to: /path/to/script/cassandra_files_to_delete.txt
```

### Actual Deletion
```
⚠️ This script will permanently delete old migrated files. ⚠️
Please consider backing up your attachments folder before proceeding.

Detected prefix: 1
WARNING: This will DELETE files in:
    /var/www/testrail/attachments
that remain after running the Cassandra deprecation script and are no longer in use.
Proceed? (y/N): y
Deleting: /var/www/testrail/attachments/old_file_1
Deleting: /var/www/testrail/attachments/old_file_2
Total files deleted: 1247
```

## Troubleshooting

**Error: "No files found matching naming convention"**
- Ensure the Cassandra deprecation process has been completed
- Verify the attachments directory contains migrated files with the new naming convention

**Error: "Directory does not exist or is not readable"**
- Check the path to the attachments directory
- Verify read permissions on the directory

**Permission denied when deleting files**
- Ensure the script is run with sufficient permissions
- Consider running with `sudo` if necessary (after careful consideration)

**Error writing dry run output file**
- Verify write permissions on the script directory
- Ensure sufficient disk space is available