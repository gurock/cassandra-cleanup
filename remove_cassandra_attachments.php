#!/usr/bin/env php
<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: php {$argv[0]} /path/to/attachments [--dry-run]\n");
    exit(1);
}

$baseDir = $argv[1];
$isDryRun = in_array('--dry-run', $argv, true);

// check if attachments dir exists
if (!is_dir($baseDir) || !is_readable($baseDir)) {
    fwrite(STDERR, "Error: Directory '$baseDir' does not exist or is not readable.\n");
    exit(1);
}

// find prefix dynamically
$prefix = null;
$prefixPattern = '/^(\d+)-\d{1,20}(-thumb[12])?$/';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }
    $basename = $file->getBasename();
    if (preg_match($prefixPattern, $basename, $matches)) {
        $prefix = $matches[1];
        break;
    }
}

if ($prefix === null) {
    fwrite(STDERR, "Error: No files found matching naming convention to determine prefix (Instance ID), Make sure to run deprecation process first.\n For example there must be files like '1-12-thumb1'\n");
    exit(1);
}

$pattern = '/^' . preg_quote($prefix, '/') . '-[0-9]{1,20}(-thumb[12])?$/';

// confirm about deletion of files
echo "⚠️ This script will permanently delete old migrated files. ⚠️\n";
echo "Please consider backing up your attachments folder before proceeding.\n\n";
echo "Detected prefix: $prefix\n";
echo ($isDryRun ? "" : "WARNING: ") . "This will " . ($isDryRun ? "LIST" : "DELETE") . " files in:\n";
echo "    $baseDir\n";
echo "that remain after running the Cassandra deprecation script and are no longer in use.\n";
echo "Proceed? (y/N): ";
$confirm = trim(fgets(STDIN));
if (!in_array(strtolower($confirm), ['y', 'yes'], true)) {
    echo "Operation cancelled.\n";
    exit(1);
}

$deletedCount = 0;
$dryRunList = [];

$iterator->rewind(); // reset iterator for second pass
foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }

    $basename = $file->getBasename();
    if (!preg_match($pattern, $basename)) {
        if ($isDryRun) {
            $dryRunList[] = $file->getPathname();
        } else {
            echo "Deleting: {$file->getPathname()}\n";
            @unlink($file->getPathname());
        }
        $deletedCount++;
    }
}

if ($isDryRun) {
    $outputFile = __DIR__ . '/cassandra_files_to_delete.txt';
    file_put_contents($outputFile, implode(PHP_EOL, $dryRunList));
    echo "Dry run complete. List saved to: $outputFile\n";
} else {
    echo "Total files deleted: $deletedCount\n";
}
