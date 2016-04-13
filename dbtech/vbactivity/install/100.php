<?php
self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_achievement` (
	  `achievementid` INT( 10 ) NOT NULL AUTO_INCREMENT ,
	  `categoryid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `title` VARCHAR( 50 ) NOT NULL DEFAULT '',
	  `description` MEDIUMTEXT NULL DEFAULT NULL ,
	  `icon` MEDIUMTEXT NULL DEFAULT NULL ,
	  `displayorder` INT( 10 ) UNSIGNED NOT NULL DEFAULT '10',
	  PRIMARY KEY ( `achievementid` ) ,
	  KEY ( `categoryid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_achievement');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_activitylevel` (
	  `activitylevelid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `title` VARCHAR( 50 ) NOT NULL ,
	  `icon` MEDIUMTEXT NULL DEFAULT NULL ,
	  `percent` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' ,
	  PRIMARY KEY ( `activitylevelid` )  
	)
");
self::report('Created Table', 'dbtech_vbactivity_activitylevel');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_category` (
	  `categoryid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `title` VARCHAR( 50 ) NOT NULL DEFAULT '',
	  `description` MEDIUMTEXT NULL DEFAULT NULL,
	  `displayorder` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  PRIMARY KEY ( `categoryid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_category');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_condition` (
	  `conditionid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `typeid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `comparison` ENUM( '<', '<=', '=', '>=', '>' ) NOT NULL,
	  `value` INT( 10 ) NOT NULL DEFAULT '0' ,
	  PRIMARY KEY ( `conditionid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_condition');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_conditionbridge` (
		  `conditionbridgeid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
		  `conditionid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
		  `feature` VARCHAR( 50 ) NOT NULL DEFAULT '' ,
		  `featureid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' ,
		  PRIMARY KEY ( `conditionbridgeid` ) ,
		  KEY ( `conditionid` ) ,
		  KEY ( `featureid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_conditionbridge');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_pointslog` (
	  `pointslogid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `userid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `dateline` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `points` FLOAT NOT NULL DEFAULT '0',
	  `typeid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  `idfield` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
	  PRIMARY KEY ( `pointslogid` ) ,
	  KEY `userid` ( `userid` ),
	  KEY `typeid` ( `typeid` ),
	  KEY `idfield` ( `idfield` )
	) ENGINE = " . self::$hightrafficengine . "
");
self::report('Created Table', 'dbtech_vbactivity_pointslog');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_rewards` (
	  `userid` INT( 10 ) UNSIGNED NOT NULL ,
	  `feature` VARCHAR( 50 ) NOT NULL DEFAULT '' ,
	  `featureid` INT( 10 ) UNSIGNED NOT NULL ,
	  `dateline` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' ,
	  PRIMARY KEY ( `userid`, `feature`, `featureid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_rewards');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "dbtech_vbactivity_type` (
	  `typeid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
	  `typename` VARCHAR( 50 ) NOT NULL DEFAULT '' ,
	  `active` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' ,
	  PRIMARY KEY ( `typeid` )
	)
");
self::report('Created Table', 'dbtech_vbactivity_type');


// Altered Tables

// Add the administrator field
if (self::$db_alter->fetch_table_info('administrator'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivityadminperms',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'administrator');	
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('user'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_achievementcount',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_points',
		'type'       => 'float',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'user');	
}

if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivitypermissions',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'usergroup');	
}


// Populated Tables

// Populate the activitylevel table
self::$db->query_write("
	INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_activitylevel
		(title, percent)
	VALUES
		('Activity Champion', 200),
		('Extremely Active', 175),
		('Very Active', 150),
		('Quite Active', 125),
		('Active', 75),	
		('Somewhat Active', 50),
		('Moderately Inactive', 25),		
		('Inactive', 0)
");
self::report('Populated Table', 'dbtech_vbactivity_activitylevel');	

// Populate the type table
self::$db->query_write("
	INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_type
		(typename, active)
	VALUES 
		('perpost', 1),
		('perpost_own', 1),
		('perthread', 1),
		('perthreadrating', 1),
		('perthreadratingreceived', 1),		
		('pertag', 0),
		('pervmgiven', 1),
		('pervmreceived', 1),
		('perpollposted', 1),
		('perpollvote', 1),
		('perfriend', 1),
		('pergivenrep', 1),
		('pergottenrep', 1),
		('perinfractiongiven', 1),
		('perinfractionreceived', 1),
		('perreferral', 1),
		('persgmessage', 1),
		('persgdiscussion', 1),
		('percalendarevent', 1),
		('perdayregistered', 1),
		('totalpoints', 1),
		('activitylevel', 1),
		('albumpictures', 0),
		('attachments', 0),
		('attachmentviews', 0),
		('post', 1),
		('post_own', 0),
		('thread', 1),
		('threadrating', 1),
		('threadratingreceived', 1),		
		('vmgiven', 1),
		('vmreceived', 1),
		('pollposted', 1),
		('pollvote', 1),
		('friend', 1),
		('givenrep', 1),
		('gottenrep', 1),
		('infractiongiven', 1),
		('infractionreceived', 1),
		('referral', 1),
		('sgmessage', 1),
		('sgdiscussion', 1),
		('calendarevent', 1),
		('dayregistered', 1)
");
self::report('Populated Table', 'dbtech_vbactivity_type');	
?>