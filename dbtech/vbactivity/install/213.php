<?php
// Altered Tables

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_points'))
{
	// Remove data we don't need anymore (moved to APTL mod)
	self::$db_alter->drop_field('dislikesgiven');
	self::$db_alter->drop_field('dislikesreceived');
	self::$db_alter->drop_field('likesgiven');
	self::$db_alter->drop_field('likesreceived');
	self::$db_alter->drop_field('thanksgiven');
	self::$db_alter->drop_field('thanksreceived');
	self::report('Altered Table', 'dbtech_vbactivity_points');
}

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db_alter->add_field(array(
		'name'       => 'filename',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	
	// Remove data we don't need anymore (moved to APTL mod)
	self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_type` WHERE `typename` IN('dislikesgiven', 'dislikesreceived', 'likesgiven', 'likesreceived', 'thanksgiven', 'thanksreceived')");
	
	$pro = array('blogcomment', 'blogpost', 'ibarcadechamp', 'ibarcadegame');
	$types = self::$db->query_read_slave("SELECT * FROM `" . TABLE_PREFIX . "dbtech_vbactivity_type`");
	while ($type = self::$db->fetch_array($types))
	{
		self::$db->query_write("
			UPDATE `" . TABLE_PREFIX . "dbtech_vbactivity_type`
			SET `filename` = '/dbtech/vbactivity" . (in_array($type['typename'], $pro) ? '_pro' : '') . "/type/" . $type['typename'] . ".php'
			WHERE `typeid` = '" . $type['typeid'] . "'
		");
	}
	self::report('Altered Table', 'dbtech_vbactivity_type');
}

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_type'");
self::report('Reverted Cache', 'dbtech_vbactivity_type');
?>