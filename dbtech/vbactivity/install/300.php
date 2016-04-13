<?php
// Created Tables

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_contestprogress`
	(
		`contestid` int(10) unsigned NOT NULL,
		`userid` int(10) unsigned NOT NULL,
		`points` double NOT NULL DEFAULT '0',
		PRIMARY KEY (`contestid`,`userid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_vbactivity_contestprogress');	

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_contesttype`
	(
		`contesttypeid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`varname` varchar(25) NOT NULL DEFAULT '',
		`title` varchar(250) NOT NULL DEFAULT '',
		`description` mediumtext,
		`active` ENUM('0', '1') DEFAULT '1',
		`filename` mediumtext,
		PRIMARY KEY (`contesttypeid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_vbactivity_contesttype');	

self::$db->query("
	REPLACE INTO " . TABLE_PREFIX . "dbtech_vbactivity_contesttype
		(`contesttypeid`, `varname`, `title`, `description`, `active`, `filename`)
	VALUES 
		(1, 'target', 'First To Reach X', 'The winners are the members who are first to reach the points target.', '1', '/dbtech/vbactivity_pro/contesttype/target.php'),
		(2, 'total', 'Total Score', 'The winners are the members who have accumulated the most amount of points when the contest ends.', '1', '/dbtech/vbactivity_pro/contesttype/total.php')
");
self::report('Populated Table', 'dbtech_vbactivity_contesttype');


// Altered Tables

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_achievement'))
{
	self::$db_alter->add_field(array(
		'name'       => 'icon_small',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::report('Altered Table', 'dbtech_vbactivity_achievement');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_contest'))
{
	self::$db_alter->add_field(array(
		'name'       => 'prizes2',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::$db_alter->add_field(array(
		'name'       => 'link',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::$db_alter->add_field(array(
		'name'       => 'banner',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::$db_alter->add_field(array(
		'name'       => 'banner_small',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::$db_alter->add_field(array(
		'name'       => 'admin_notifs',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));	
	self::$db_alter->add_field(array(
		'name'       => 'excludedcriteria',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));	
	self::$db_alter->add_field(array(
		'name'       => 'excludedforums',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));	
	self::$db->hide_errors();
	self::$db->query("ALTER TABLE " . TABLE_PREFIX . "dbtech_vbactivity_contest ADD `is_public` ENUM( '0', '1' ) NOT NULL DEFAULT '1'");
	self::$db->query("ALTER TABLE " . TABLE_PREFIX . "dbtech_vbactivity_contest ADD `show_criteria` ENUM( '0', '1' ) NOT NULL DEFAULT '1'");
	self::$db->query("ALTER TABLE " . TABLE_PREFIX . "dbtech_vbactivity_contest ADD `show_progress` ENUM( '0', '1' ) NOT NULL DEFAULT '1'");
	self::$db->show_errors();
	self::$db_alter->add_field(array(
		'name'       => 'winner_notifs',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'contesttypeid',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '1'
	));
	self::report('Altered Table', 'dbtech_vbactivity_contest');
}

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_contest'");
self::report('Reverted Cache', 'dbtech_vbactivity_contest');

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_medal'))
{
	self::$db_alter->add_field(array(
		'name'       => 'icon_small',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::report('Altered Table', 'dbtech_vbactivity_medal');	
}

if (self::$db_alter->fetch_table_info('dbtech_vbactivity_medalrequest'))
{
	self::$db_alter->add_field(array(
		'name'       => 'reason',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::report('Altered Table', 'dbtech_vbactivity_medalrequest');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_points'))
{
	self::$db_alter->add_field(array(
		'name'       => 'profilecomplete',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'contestswon',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'contestprize',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_points');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_pointslog'))
{
	self::$db_alter->add_field(array(
		'name'       => 'forumid',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '1'
	));
	self::report('Altered Table', 'dbtech_vbactivity_pointslog');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_rewards'))
{
	self::$db_alter->add_field(array(
		'name'       => 'reason',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::report('Altered Table', 'dbtech_vbactivity_rewards');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db_alter->add_field(array(
		'name'       => 'pointsperforum',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::report('Altered Table', 'dbtech_vbactivity_type');	
}

$types = array(
	'givenrep',
	'gottenrep',
	'infractiongiven',
	'infractionreceived',
	'mentionsgiven',
	'mentionsreceived',
	'pollposted',
	'pollvote',
	'post',
	'post_own',
	'tagsgiven',
	'tagsreceived',
	'thread',
	'threadrating',
	'threadratingreceived',
);
if (self::$vbulletin->products['dbtech_thanks'])
{
	foreach ((array)THANKS::$cache['button'] as $buttonid => $button)
	{
		$types[] = $button['varname'];
	}
}

self::$db->query("
	UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
	SET settings = settings + 32
	WHERE NOT (settings & 32)
		AND typename IN('" . implode("', '", $types) . "')
");
self::$db->query("
	INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_type
		(typename, active, points, display, settings, filename)
	VALUES 
		('profilecomplete', '1', '5', '7', '15', '/dbtech/vbactivity/type/profilecomplete.php'),
		('contestswon', '1', '15', '7', '15', '/dbtech/vbactivity_pro/type/contestswon.php'),
		('contestprize', '1', '1', '0', '0', '/dbtech/vbactivity_pro/type/contestprize.php')
");
self::report('Updated Table', 'dbtech_vbactivity_type');	

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_type'");
self::report('Reverted Cache', 'dbtech_vbactivity_type');

?>