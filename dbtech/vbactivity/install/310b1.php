<?php

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_awardbonus`
	(
		`rewardid` int(10) unsigned NOT NULL,
		`userid` int(10) unsigned NOT NULL DEFAULT '0',
		`awardid` int(10) unsigned NOT NULL DEFAULT '0',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		`points` double NOT NULL DEFAULT '0',
		PRIMARY KEY (`rewardid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_vbactivity_awardbonus');

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_condition'))
{
	self::$db_alter->add_field(array(
		'name'       => 'forumid',
		'type'       => 'int',
		'length'     => '10',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_condition');
}

if (self::$db_alter->fetch_table_info('dbtech_vbactivity_contest'))
{
	self::$db_alter->add_field(array(
		'name'       => 'recurring',
		'type'       => 'int',
		'length'     => '10',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'numusers',
		'type'       => 'int',
		'length'     => '10',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db->query("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_contest
		SET numusers = numwinners
	");	
	self::report('Altered Table', 'dbtech_vbactivity_contest');
}

if (self::$db_alter->fetch_table_info('dbtech_vbactivity_points'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbgalleryupload',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'awards',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_points');
}

if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db_alter->add_field(array(
		'name'       => 'sortorder',
		'type'       => 'tinyint',
		'length'     => '1',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '1'
	));
	self::$db->query("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET sortorder = '0'
		WHERE typename = 'infractionreceived'
	");

	self::$db->query("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET active = '1', points = '1', display = '7', settings = '0'
		WHERE typename = 'attachments'
	");
	self::$db->query("
		INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_type
			(typename, active, points, display, settings, filename)
		VALUES 
			('dbgalleryupload', '1', '5', '7', '15', '/dbtech/vbactivity/type/dbgalleryupload.php')
	");
	self::$db->query("
		INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_type
			(typename, active, points, display, settings, filename)
		VALUES 
			('awards', '1', '0', '0', '15', '/dbtech/vbactivity/type/awards.php')
	");
	self::$db->query("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET settings = settings + 64
		WHERE NOT (settings & 64)
			AND typename IN(
				'post', 'post_own', 'thread', 'threadrating', 'threadratingreceived', 'pollposted', 
				'pollvote', 'givenrep', 'gottenrep', 'infractiongiven', 'infractionreceived', 
				'mentionsgiven', 'mentionsreceived', 'tagsgiven', 'tagsreceived'
			)
	");
	self::report('Updated Table', 'dbtech_vbactivity_type');	
}

if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivitymodpermissions',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'usergroup');	
}

foreach (array(
	'condition',
	'contest',
	'type'
) as $table)
{
	self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_{$table}'");
	self::report('Reverted Cache', 'dbtech_vbactivity_' . $table);
}
?>