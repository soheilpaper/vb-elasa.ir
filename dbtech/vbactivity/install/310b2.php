<?php

self::$db->query("
	REPLACE INTO " . TABLE_PREFIX . "dbtech_vbactivity_contesttype
		(`contesttypeid`, `varname`, `title`, `description`, `active`, `filename`)
	VALUES 
		(3, 'targetraffle', 'Raffle Tickets (Threshold)', 'The members who reach the points target are entered into a raffle.', '1', '/dbtech/vbactivity_pro/contesttype/targetraffle.php')
");
self::report('Populated Table', 'dbtech_vbactivity_contesttype');	

if (self::$db_alter->fetch_table_info('dbtech_vbactivity_type'))
{
	self::$db->query("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
		SET settings = 15
		WHERE typename = 'dbgalleryupload'
	");
	self::report('Updated Table', 'dbtech_vbactivity_type');	
}

foreach (array(
	'contesttype',
	'type',
) as $table)
{
	self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_{$table}'");
	self::report('Reverted Cache', 'dbtech_vbactivity_' . $table);
}
?>