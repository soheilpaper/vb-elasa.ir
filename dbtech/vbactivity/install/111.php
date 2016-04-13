<?php
// Altered Tables

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_rewards'))
{
	self::$db->hide_errors();		
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "dbtech_vbactivity_rewards` DROP PRIMARY KEY");
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "dbtech_vbactivity_rewards` ADD `rewardid` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
	self::$db->show_errors();		
	self::report('Altered Table', 'dbtech_vbactivity_rewards');	
}

if (self::$db_alter->fetch_table_info('user'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_rewardscache',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));	
	self::report('Altered Table', 'user');
}
?>