<?php
// Altered Tables

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_achievement'))
{
	self::$db_alter->add_field(array(
		'name'       => 'parentid',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_achievement');	
}

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_contest'))
{
	self::$db_alter->add_field(array(
		'name'       => 'numwinners',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'target',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_contest');	
}

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_points'))
{
	self::$db_alter->add_field(array(
		'name'       => 'blogpost',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'blogcomment',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'shout',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'thanksgiven',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'thanksreceived',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'likesgiven',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'likesreceived',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'dislikesgiven',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'dislikesreceived',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'mentionsgiven',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'mentionsreceived',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'tagsgiven',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db_alter->add_field(array(
		'name'       => 'tagsreceived',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_points');	
}

foreach (array(
	'blogpost' => 10,
	'blogcomment' => 2,
	'shout' => 0.02,
	'thanksgiven' => 0.5,
	'thanksreceived' => 0.5,
	'likesgiven' => 0.5,
	'likesreceived' => 0.5,
	'dislikesgiven' => 0.5,
	'dislikesreceived' => -0.5,
	'mentionsgiven' => 0.5,
	'mentionsreceived' => 0.5,
	'tagsgiven' => 0.5,
	'tagsreceived' => 0.5,
	//'ibarcadechamp' => 10,
	//'ibarcadegame' => 0.02,
) as $typename => $points)
{
	if (!$existing = self::$db->query_first_slave("SELECT typeid FROM " . TABLE_PREFIX . "dbtech_vbactivity_type WHERE typename = '" . $typename . "'"))
	{
		self::$db->query_write("
			INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_type` 
				(`typename`, `points`)
			VALUES 
				('" . $typename . "', '" . $points . "')
		");
	}
}

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_condition' OR `title` = 'dbtech_vbactivity_type'");
self::report('Reverted Cache', 'dbtech_vbactivity_points');
self::report('Reverted Cache', 'dbtech_vbactivity_type');

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_medal'))
{
	self::$db_alter->add_field(array(
		'name'       => 'availability',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '3'
	));
	self::report('Altered Table', 'dbtech_vbactivity_medal');	
}
?>