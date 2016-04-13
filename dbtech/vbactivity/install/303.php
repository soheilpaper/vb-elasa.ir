<?php
// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_points'))
{
	self::$db_alter->add_field(array(
		'name'       => 'registration',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_points');	
}

if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db->query("
		INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_type
			(typename, active, points, display, settings, filename)
		VALUES 
			('registration', '1', '25', '0', '11', '/dbtech/vbactivity/type/registration.php')
	");
	self::report('Updated Table', 'dbtech_vbactivity_type');	
}

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_type'");
self::report('Reverted Cache', 'dbtech_vbactivity_type');
?>