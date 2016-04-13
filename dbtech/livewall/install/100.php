<?php

// Add the administrator field
if (self::$db_alter->fetch_table_info('administrator'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_livewalladminperms',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'administrator');
}

if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_livewallpermissions',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'usergroup');
}

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_livewall_contenttype` (
		`contenttypeid` varchar(25) NOT NULL,
		`title` varchar(50) NOT NULL,
		`active` enum('0','1') NOT NULL DEFAULT '1',
		`filename` mediumtext,
		`permissions` mediumtext,
		`code` mediumtext,
		`preview` int(10) UNSIGNED NOT NULL DEFAULT '300',
		PRIMARY KEY (`contenttypeid`)
	) ENGINE=MyISAM ;
");
self::report('Created Table', 'dbtech_livewall_contenttype');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_livewall_settings` (
		`userid` int(10) NOT NULL,
		`posts_display` tinyint(1) NOT NULL DEFAULT '0',
		`posts_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`avatarchanges_display` tinyint(1) NOT NULL DEFAULT '0',
		`avatarchanges_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`profilepictures_display` tinyint(1) NOT NULL DEFAULT '0',
		`profilepictures_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`signaturechanges_display` tinyint(1) NOT NULL DEFAULT '0',
		`signaturechanges_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`threads_display` tinyint(1) NOT NULL DEFAULT '0',
		`threads_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`socialgroupposts_display` tinyint(1) NOT NULL DEFAULT '0',
		`socialgroupposts_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`socialgroupthreads_display` tinyint(1) NOT NULL DEFAULT '0',
		`socialgroupthreads_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`usernamechanges_display` tinyint(1) NOT NULL DEFAULT '0',
		`usernamechanges_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`usernotes_display` tinyint(1) NOT NULL DEFAULT '0',
		`usernotes_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`visitormessages_display` tinyint(1) NOT NULL DEFAULT '0',
		`visitormessages_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`blogposts_display` tinyint(1) NOT NULL DEFAULT '0',
		`blogposts_privacy` tinyint(1) NOT NULL DEFAULT '0',
		`blogcomments_display` tinyint(1) NOT NULL DEFAULT '0',
		`blogcomments_privacy` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`userid`)
	) ENGINE=MyISAM ;
");
self::report('Created Table', 'dbtech_livewall_settings');

self::$db->query_write("
	REPLACE INTO `" . TABLE_PREFIX  . "dbtech_livewall_contenttype` 
		(`contenttypeid`, `title`, `active`, `filename`)
	VALUES
		('posts', 'New Posts', '1', 'dbtech/livewall/contenttypes/posts.php'),
		('avatarchanges', 'Avatar Changes', '1', 'dbtech/livewall/contenttypes/avatarchanges.php'),
		('profilepictures', 'New Profile Pictures', '1', 'dbtech/livewall_pro/contenttypes/profilepictures.php'),
		('signaturechanges', 'Signature Changes', '1', 'dbtech/livewall/contenttypes/signaturechanges.php'),
		('threads', 'New Threads', '1', 'dbtech/livewall/contenttypes/threads.php'),
		('socialgroupposts', 'New Social Group Posts', '1', 'dbtech/livewall/contenttypes/socialgroupposts.php'),
		('socialgroupthreads', 'New Social Group Threads', '1', 'dbtech/livewall/contenttypes/socialgroupthreads.php'),
		('usernamechanges', 'Username Changes', '1', 'dbtech/livewall/contenttypes/usernamechanges.php'),
		('usernotes', 'New User Notes', '1', 'dbtech/livewall_pro/contenttypes/usernotes.php'),
		('visitormessages', 'New Visitor Messages', '1', 'dbtech/livewall_pro/contenttypes/visitormessages.php'),
		('blogposts', 'New Blog Posts', '1', 'dbtech/livewall_pro/contenttypes/blogposts.php'),
		('blogcomments', 'New Blog Comments', '1', 'dbtech/livewall_pro/contenttypes/blogcomments.php')
");
self::report('Populdated Table', 'dbtech_livewall_contenttype');

//define('CP_REDIRECT', 'livewall.php?do=finalise&version=100');
//define('DISABLE_PRODUCT_REDIRECT', true);