<?php

// Revert
if (self::$db_alter->fetch_table_info('administrator'))
{
	self::$db_alter->drop_field('dbtech_livewalladminperms');
	self::report('Reverted Table', 'administrator');
}

// Clean up
self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` LIKE 'dbtech_livewall_%'");
self::report('Reverted Table', 'datastore');

// Revert
if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db_alter->drop_field('dbtech_livewallpermissions');
	self::report('Reverted Table', 'usergroup');
}

// Drop
$tables = array(
	'comment',
	'contenttype',
	'favourite',
	'settings',
	'status',
);
foreach ($tables as $table)
{
	self::$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "dbtech_livewall_{$table}`");
	self::report('Deleted Table', 'dbtech_livewall_' . $table);
}