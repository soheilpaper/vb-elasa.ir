<?php
// Reverted Tables

// Drop
if (self::$db_alter->fetch_table_info('administrator'))
{
	self::$db_alter->drop_field('dbtech_vbactivityadminperms');
	self::report('Reverted Table', 'administrator');
}

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` LIKE 'dbtech_vbactivity_%'");
self::report('Reverted Table', 'datastore');

// Drop
if (self::$db_alter->fetch_table_info('user'))
{
	self::$db_alter->drop_field(array(
		'dbtech_vbactivity_achievementcount',
		'dbtech_vbactivity_medalcount',
		'dbtech_vbactivity_medalmoderatecount',
		'dbtech_vbactivity_promotioncount',
		'dbtech_vbactivity_excluded',
		'dbtech_vbactivity_excluded_tmp',
		'dbtech_vbactivity_points',
		'dbtech_vbactivity_pointscache',
		'dbtech_vbactivity_pointscache_day',
		'dbtech_vbactivity_pointscache_week',
		'dbtech_vbactivity_pointscache_month',
		'dbtech_vbactivity_rewardscache',
		'dbtech_vbactivity_trophycount',
		'dbtech_vbactivity_settings',
		'dbtech_vbactivity_autocollapse_stats',
		'dbtech_vbactivity_autocollapse_bar'
	));
	self::report('Reverted Table', 'user');
}

if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db_alter->drop_field(array(
		'dbtech_vbactivitypermissions',
		'dbtech_vbactivitymodpermissions',
		'dbtech_vbactivity_excluded',
		'dbtech_vbactivity_requestdelay',
		'dbtech_vbactivity_nominatedelay'
	));
	self::report('Reverted Table', 'usergroup');
}


// Deleted Tables
$tables = array(
	'achievement',
	'activitylevel',
	'activitystats',
	'awardbonus',
	'backup',
	'category',
	'condition',
	'conditionbridge',
	'contest',
	'contestprogress',
	'contesttype',
	'medal',
	'medalrequest',
	'points',
	'pointslog',
	'promotion',
	'rewards',
	'snapshot',
	'snapshotschedule',
	'trophylog',
	'type'
);

foreach ($tables as $table)
{
	self::$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_{$table}`");
	self::report('Dropped Table', 'dbtech_vbactivity_' . $table);	
}
?>