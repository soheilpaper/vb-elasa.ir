<?php

// Add the administrator field
if (self::$db_alter->fetch_table_info('dbtech_livewall_settings'))
{
	self::$db_alter->add_field(array(
		'name'       => 'statusupdate_display',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'statusupdate_privacy',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'vblive_display',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'vblive_privacy',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'vbarcadescore_display',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'vbarcadescore_privacy',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_livewall_settings');
}

self::$db->query_write("
	REPLACE INTO `" . TABLE_PREFIX  . "dbtech_livewall_contenttype` 
		(`contenttypeid`, `title`, `active`, `filename`)
	VALUES
		('statusupdate', 'Status Updates', '1', 'dbtech/livewall/contenttypes/statusupdate.php'),
		('vblive', 'vBLive Status Updates', '1', 'dbtech/livewall/contenttypes/vblive.php'),
		('vbarcadescore', 'vBArcade Scores', '1', 'dbtech/livewall/contenttypes/vbarcadescore.php')
");
self::report('Populated Table', 'dbtech_livewall_contenttype');

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_livewall_contenttype'");
self::report('Updated Table', 'datastore');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_livewall_comment` (
		`commentid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`userid` int(10) unsigned NOT NULL DEFAULT '0',
		`contenttypeid` varchar(25) NOT NULL DEFAULT '',
		`contentid` int(10) unsigned NOT NULL DEFAULT '0',
		`message` mediumtext,
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`commentid`),
		KEY `contenttypeid` (`contenttypeid`,`contentid`)
	) ENGINE=MyISAM ;
");
self::report('Created Table', 'dbtech_livewall_comment');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_livewall_status` (
		`statusid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`userid` int(10) unsigned NOT NULL DEFAULT '0',
		`pagetext` MEDIUMTEXT NULL DEFAULT NULL,
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`statusid`),
		KEY `userid` (`userid`)
	) ENGINE=MyISAM ;
");
self::report('Created Table', 'dbtech_livewall_status');

if (file_exists(DIR . '/includes/class_block.php'))
{
	require_once(DIR . '/includes/class_block.php');
	$blockmanager = vB_BlockManager::create(self::$vbulletin);
	$blockmanager->reloadBlockTypes(true);
	self::report('Rebuilt Data', 'Forum Blocks');
}