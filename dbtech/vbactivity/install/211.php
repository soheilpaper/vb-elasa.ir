<?php
// Altered Tables

// Add the dbtech_vbactivity_rewards field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_points'))
{
	foreach (array(
		'ttbattledraw' => 0.25,
		'ttbattlewon' => 0.5,
		'ttbattlelost' => -0.5,
		'tttournywon' => 25,
		'tttrade' => 0.1,
	) as $typename => $points)
	{
		self::$db_alter->add_field(array(
			'name'       => $typename,
			'type'       => 'double',
			'null'       => false,	// True = NULL, false = NOT NULL
			'default'    => '0'
		));
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
	self::report('Altered Table', 'dbtech_vbactivity_points');	
	self::report('Populated Table', 'dbtech_vbactivity_type');
}

self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_condition' OR `title` = 'dbtech_vbactivity_type'");
self::report('Reverted Cache', 'dbtech_vbactivity_points');
self::report('Reverted Cache', 'dbtech_vbactivity_type');
?>