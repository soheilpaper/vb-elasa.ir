<?php
// Altered Tables

// Add the usergroup field
if (self::$db_alter->fetch_table_info('user'))
{
	self::$db->hide_errors();
	self::$db->query_write("ALTER TABLE " . TABLE_PREFIX . "user ADD dbtech_vbactivity_excluded_tmp ENUM('0', '1') NOT NULL DEFAULT '0'");
	self::$db->query_write("ALTER TABLE `" . TABLE_PREFIX . "user` ADD INDEX ( `dbtech_vbactivity_excluded_tmp` )");
	self::$db->show_errors();
	self::report('Altered Table', 'user');	
}
?>