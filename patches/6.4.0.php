<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$tables = $db->metaTables();

// ===========================================================================
// Create initial tables

if(!isset($tables['asset'])) {
	$sql = sprintf("
		CREATE TABLE IF NOT EXISTS asset (
			id INT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) DEFAULT '',
			updated_at INT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) ENGINE=%s;
	", APP_DB_ENGINE);
	$db->ExecuteMaster($sql);

	$tables['asset'] = 'asset';
}