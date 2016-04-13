<?php
// New tables
self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_activitystats` (
		`activitystatsid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
		`userid` INT( 10 ) NOT NULL DEFAULT '0',
		`points` DOUBLE NOT NULL DEFAULT '0',
		`type` ENUM( 'daily', 'weekly', 'monthly' ) NOT NULL DEFAULT 'weekly',
		`dateline` INT( 10 ) NOT NULL DEFAULT '0',
		PRIMARY KEY ( `activitystatsid` ) ,
		INDEX ( `userid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_activitystats');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_contest` (
		`contestid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
		`title` VARCHAR( 50 ) NOT NULL DEFAULT '',
		`description` MEDIUMTEXT NULL DEFAULT NULL,
		`start` INT( 10 ) NOT NULL DEFAULT '0',
		`end` INT( 10 ) NOT NULL DEFAULT '0',
		`prizes` MEDIUMTEXT NULL DEFAULT NULL,
		`winners` MEDIUMTEXT NULL DEFAULT NULL,
		PRIMARY KEY ( `contestid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_contest');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_medalrequest` (
		`medalrequestid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT,
		`userid` INT( 10 ) NOT NULL DEFAULT '0',
		`targetuserid` INT( 10 ) NOT NULL DEFAULT '0',
		`medalid` INT( 10 ) NOT NULL DEFAULT '0',
		`dateline` INT( 10 ) NOT NULL DEFAULT '0',
		`status` ENUM( '0', '1', '2' ) NOT NULL DEFAULT '0',
		PRIMARY KEY ( `medalrequestid` ) ,
		INDEX ( `userid` ) ,
		INDEX ( `targetuserid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_medalrequest');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_points` (
	  `userid` INT( 10 ) NOT NULL ,
	  PRIMARY KEY ( `userid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_points');


// Altered Tables

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_condition'))
{
	self::$db->hide_errors();
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "dbtech_vbactivity_condition` ADD `type` ENUM( 'points', 'value' ) NOT NULL DEFAULT 'value'");
	self::$db->show_errors();
	
	self::report('Altered Table', 'dbtech_vbactivity_condition');	
}

$defaults = array(
	'dbtech_vbactivity_points_perpost' => 2,
	'dbtech_vbactivity_points_perpost_own' => 1,
	'dbtech_vbactivity_points_perthread' => 5,
	'dbtech_vbactivity_points_perthreadrating' => 0.5,
	'dbtech_vbactivity_points_perthreadratingreceived' => 0.5,
	'dbtech_vbactivity_points_pervmgiven' => 1,
	'dbtech_vbactivity_points_pervmreceived' => 1,
	'dbtech_vbactivity_points_perpollposted' => 5,
	'dbtech_vbactivity_points_perpollvote' => 2,
	'dbtech_vbactivity_points_perdayregistered' => 0.1,
	'dbtech_vbactivity_points_perfriend' => 5,
	'dbtech_vbactivity_points_pergivenrep' => 0.25,
	'dbtech_vbactivity_points_pergottenrep' => 0.25,
	'dbtech_vbactivity_points_perinfractiongiven' => 0,
	'dbtech_vbactivity_points_perinfractionreceived' => -25,
	'dbtech_vbactivity_points_perreferral' => 10,
	'dbtech_vbactivity_points_persgmessage' => 1,
	'dbtech_vbactivity_points_persgdiscussion' => 2.5,
	'dbtech_vbactivity_points_percalendarevent' => 0,
);

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db_alter->add_field(array(
		'name'       => 'points',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'display',
		'type'       => 'int',
		'length'     => '10',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '7'
	));
	self::$db_alter->add_field(array(
		'name'       => 'settings',
		'type'       => 'int',
		'length'     => '10',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '15'
	));
	
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET settings = '14'
		WHERE typename IN('albumpictures', 'attachments', 'attachmentviews')
	");
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET display = '0', settings = '20'
		WHERE typename = 'totalpoints'
	");
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET display = '0', settings = '16'
		WHERE typename = 'activitylevel'
	");
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET display = '5', settings = '27'
		WHERE typename = 'dayregistered'
	");
	
	$types = self::$db->query_read_slave("SELECT * FROM `" . TABLE_PREFIX . "dbtech_vbactivity_type` WHERE typename LIKE 'per%'");
	while ($type = self::$db->fetch_array($types))
	{
		$short = substr($type['typename'], 3);
		$typeid = self::$db->query_first_slave("SELECT typeid FROM `" . TABLE_PREFIX . "dbtech_vbactivity_type` WHERE typename = " . self::$db->sql_prepare($short));
		
		// Set points from options
		self::$db->query_write("
			UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
			SET 
				points = " . self::$db->sql_prepare(
					(isset($vbulletin->options["dbtech_vbactivity_points_{$type[typename]}"]) ?
						$vbulletin->options["dbtech_vbactivity_points_{$type[typename]}"] :
						$defaults["dbtech_vbactivity_points_{$type[typename]}"]
					)
				) . ",
				userid = " . self::$db->sql_prepare($type['userid']) . ",
				trophyname = " . self::$db->sql_prepare($type['trophyname']) . ",
				icon = " . self::$db->sql_prepare($type['icon']) . "
			WHERE typeid = " . intval($typeid['typeid'])
		);
		
		// Set new typeid
		self::$db->query_write("
			UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_condition
			SET type = 'points', typeid = " . intval($typeid['typeid']) . "
			WHERE typeid = " . intval($type['typeid'])
		);
		
		// Set new typeid
		self::$db->query_write("
			UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_trophylog
			SET typeid = " . intval($typeid['typeid']) . "
			WHERE typeid = " . intval($type['typeid'])
		);
		
		// Set new typeid
		self::$db->query_write("
			UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
			SET typeid = " . intval($typeid['typeid']) . "
			WHERE typeid = " . intval($type['typeid'])
		);
		
		// Delete old redundant points type
		self::$db->query_write("DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_trophylog WHERE typeid = " . intval($type['typeid']));
		self::$db->query_write("DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_type WHERE typeid = " . intval($type['typeid']));
	}
	self::$db->free_result($types);
	unset($type);
	
	if (!$existing = self::$db->query_first_slave("SELECT typeid FROM " . TABLE_PREFIX . "dbtech_vbactivity_type WHERE typename = 'lastvisit'"))
	{
		self::$db->query_write("
			INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_type` 
				(`typename`, `active`, `userid`, `trophyname`, `icon`, `points`, `display`, `settings`)
			VALUES 
				('lastvisit', '1', '0', '', NULL , '0', '0', '16')
		");
	}
	
	if (!$existing = self::$db->query_first_slave("SELECT typeid FROM " . TABLE_PREFIX . "dbtech_vbactivity_type WHERE typename = 'lastpost'"))
	{
		self::$db->query_write("
			INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_type` 
				(`typename`, `active`, `userid`, `trophyname`, `icon`, `points`, `display`, `settings`)
			VALUES 
				('lastpost', '1', '0', '', NULL , '0', '0', '16')
		");
	}
		
	self::$db->query_write("
		ALTER TABLE `" . TABLE_PREFIX . "dbtech_vbactivity_type` ORDER BY `typeid`
	");
		
	self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_condition' OR `title` = 'dbtech_vbactivity_type'");
	self::report('Reverted Cache', 'dbtech_vbactivity_points');
	self::report('Reverted Cache', 'dbtech_vbactivity_type');
}

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_points'))
{
	$types = self::$db->query_read_slave("SELECT * FROM `" . TABLE_PREFIX . "dbtech_vbactivity_type` WHERE typename NOT IN('totalpoints', 'activitylevel')");
	$typenames = array();
	while ($type = self::$db->fetch_array($types))
	{
		if (!in_array($type['typename'], $typenames))
		{
			self::$db_alter->add_field(array(
				'name'       => $type['typename'],
				'type'       => 'double',
				'null'       => false,	// True = NULL, false = NOT NULL
				'default'    => '0'
			));
			
			$typenames[] = $type['typename'];
		}
	}
	self::$db->free_result($types);
	unset($type);
}

if (self::$db_alter->fetch_table_info('user'))
{
	self::$db->hide_errors();
	self::$db->query_write("
		ALTER TABLE `" . TABLE_PREFIX . "user`
		ADD `dbtech_vbactivity_excluded` ENUM( '0', '1' ) NOT NULL DEFAULT '0'
	");
	self::$db->show_errors();
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_medalmoderatecount',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));	
	self::report('Altered Table', 'user');	
}

if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db->hide_errors();
	self::$db->query_write("
		ALTER TABLE `" . TABLE_PREFIX . "usergroup`
		ADD `dbtech_vbactivity_excluded` ENUM( '0', '1' ) NOT NULL DEFAULT '0'
	");
	self::$db->show_errors();
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_requestdelay',
		'type'       => 'smallint',
		'length'     => '5',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '1'
	));	
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_nominatedelay',
		'type'       => 'smallint',
		'length'     => '5',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '1'
	));	
	self::report('Altered Table', 'usergroup');	
}

define('CP_REDIRECT', 'vbactivity.php?do=finalise&version=2000');
define('DISABLE_PRODUCT_REDIRECT', true);
?>