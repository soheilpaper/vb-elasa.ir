<?php
// New Tables

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_backup` (
	  `backupid` INT( 10 ) NOT NULL AUTO_INCREMENT ,
	  `title` VARCHAR( 50 ) NOT NULL ,
	  `dateline` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `backupdata` LONGTEXT NULL DEFAULT NULL ,
	  PRIMARY KEY ( `backupid` )  
	)
");
self::report('Created Table', 'dbtech_vbactivity_backup');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_medal` (
	  `medalid` INT( 10 ) NOT NULL AUTO_INCREMENT ,
	  `categoryid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `title` VARCHAR( 50 ) NOT NULL DEFAULT '',
	  `description` MEDIUMTEXT NULL DEFAULT NULL ,
	  `icon` MEDIUMTEXT NULL DEFAULT NULL ,
	  `displayorder` INT( 10 ) UNSIGNED NOT NULL DEFAULT '10',
	  PRIMARY KEY ( `medalid` ) ,
	  KEY ( `categoryid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_medal');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_promotion` (
	  `promotionid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `fromusergroupid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `tousergroupid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  PRIMARY KEY ( `promotionid` )  
	)
");
self::report('Created Table', 'dbtech_vbactivity_promotion');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_snapshot` (
	  `snapshotid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `title` VARCHAR( 50 ) NOT NULL ,
	  `description` MEDIUMTEXT NULL DEFAULT NULL ,
	  `active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
	  `data` LONGTEXT NULL DEFAULT NULL ,
	  PRIMARY KEY ( `snapshotid` )	  
	)
");
self::report('Created Table', 'dbtech_vbactivity_snapshot');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_snapshotschedule` (
	  `snapshotscheduleid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `start_day` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
	  `start_hour` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0',
	  `end_day` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
	  `end_hour` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0',
	  `loadsnapshotid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `revertsnapshotid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0',
	  PRIMARY KEY ( `snapshotscheduleid` ) ,	  
	  KEY ( `loadsnapshotid` ) ,
	  KEY ( `revertsnapshotid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_snapshotschedule');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_trophylog` (
	  `trophylogid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `typeid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `dateline` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `userid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `addremove` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1',
	  PRIMARY KEY ( `trophylogid` ) ,
	  KEY ( `userid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_trophylog');

// Altered Tables

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_medal'))
{
	self::$db_alter->add_field(array(
		'name'       => 'sticky',
		'type'       => 'tinyint',
		'length'     => '1',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_medal');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db_alter->add_field(array(
		'name'       => 'userid',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'trophyname',
		'type'       => 'varchar',
		'length'     => '50',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => ''
	));
	self::$db_alter->add_field(array(
		'name'       => 'icon',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
	));
	self::report('Altered Table', 'dbtech_vbactivity_type');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('user'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_trophycount',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_medalcount',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'user');	
}
?>