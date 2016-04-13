<?php
// Altered Tables

// Add the dbtech_vbactivity_achievement field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_achievement'))
{
	self::$db_alter->add_field(array(
		'name'       => 'sticky',
		'type'       => 'tinyint',
		'length'     => '1',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'dbtech_vbactivity_achievement');	
}
?>