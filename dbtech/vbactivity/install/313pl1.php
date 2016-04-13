<?php

if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET settings = '15'
		WHERE typename = 'attachments'
	");

	self::$db->query("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET settings = '0'
		WHERE typename IN('albumpictures', 'attachmentviews', 'ttbattledraw', 'ttbattlewon', 'ttbattlelost', 'tttournywon', 'tttrade')
	");
	self::report('Updated Table', 'dbtech_vbactivity_type');
}

// Add the usergroup field
if (self::$db_alter->fetch_table_info('user'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_promotioncount',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'user');	
}
?>