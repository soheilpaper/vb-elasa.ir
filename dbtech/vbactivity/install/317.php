<?php

// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET settings = '15'
		WHERE typename = 'attachments'
	");
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET display = '0'
		WHERE typename = 'totalpoints'
	");
	self::$db->query("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET settings = '0'
		WHERE typename IN('albumpictures', 'attachmentviews', 'ttbattledraw', 'ttbattlewon', 'ttbattlelost', 'tttournywon', 'tttrade')
	");
	self::report('Updated Table', 'dbtech_vbactivity_type');
}
?>