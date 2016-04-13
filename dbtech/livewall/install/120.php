<?php

// Add the administrator field
if (self::$db_alter->fetch_table_info('dbtech_livewall_contenttype'))
{
	self::$db_alter->add_field(array(
		'name'       => 'preview_sidebar',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '140'
	));
}

// Add the administrator field
if (self::$db_alter->fetch_table_info('dbtech_livewall_settings'))
{
	self::$db_alter->add_field(array(
		'name'       => 'aptl_display',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'aptl_privacy',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	/*
	self::$db_alter->add_field(array(
		'name'       => 'aut_display',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'aut_privacy',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	*/
	self::report('Altered Table', 'dbtech_livewall_settings');
}

self::$db->query_write("
	REPLACE INTO `" . TABLE_PREFIX  . "dbtech_livewall_contenttype` 
		(`contenttypeid`, `title`, `active`, `filename`)
	VALUES
		('aptl', '[DBTech] Advanced Post Thanks / Like Clicks', '1', 'dbtech/livewall/contenttypes/aptl.php')
");
/*
,
		('aut', '[DBTech] Advanced User Tagging Mentions', '1', 'dbtech/livewall/contenttypes/aut.php'),
*/
self::report('Populated Table', 'dbtech_livewall_contenttype');

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_livewall_contenttype'");
self::report('Updated Table', 'datastore');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_livewall_favourite` (
		`contenttypeid` varchar(25) NOT NULL DEFAULT '',
		`contentid` int(10) unsigned NOT NULL DEFAULT '0',
		`userid` int(10) unsigned NOT NULL DEFAULT '0',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		PRIMARY KEY (`contenttypeid`,`contentid`,`userid`),
		KEY `dateline` (`dateline`)
	) ENGINE=MyISAM ;
");
self::report('Created Table', 'dbtech_livewall_comment');