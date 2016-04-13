<?php
if ($vbulletin->products['dbtech_vbactivity_pro'])
{
	// We have the pro version installed, remove it without erasing data
	if (!function_exists('delete_product'))
	{
		require(DIR . '/includes/adminfunctions_plugin.php');
	}
	delete_product('dbtech_vbactivity_pro');
}
else
{
	// We're lacking the Pro database
	require(DIR . '/dbtech/vbactivity/install/pro.php');
}

// Altered Tables

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_rewards'))
{
	self::$db->hide_errors();
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`  ADD INDEX ( `feature` , `featureid` )");
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`  ADD INDEX ( `userid` )");
	self::$db->query_write("OPTIMIZE TABLE `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`");
	self::$db->show_errors();
	self::report('Altered Table', 'dbtech_vbactivity_rewards');	
}

if (self::$db_alter->fetch_table_info('user'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_settings',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::$db->hide_errors();
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "user` ADD `dbtech_vbactivity_autocollapse_stats` ENUM( '-1', '0', '1' ) NOT NULL DEFAULT '-1'");
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "user` ADD `dbtech_vbactivity_autocollapse_bar` ENUM( '-1', '0', '1' ) NOT NULL DEFAULT '-1'");
	self::$db->show_errors();	
	self::report('Altered Table', 'user');	
}
?>