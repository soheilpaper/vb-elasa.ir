<?php
// Add the usergroup field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_contest'))
{
	self::$db_alter->add_field(array(
		'name'       => 'data',
		'type'       => 'mediumtext',
		'null'       => true,	// True = NULL, false = NOT NULL
		'default'    => NULL
	));
	self::report('Altered Table', 'dbtech_vbactivity_contest');
}

self::$db->query("
	REPLACE INTO " . TABLE_PREFIX . "dbtech_vbactivity_contesttype
		(`contesttypeid`, `varname`, `title`, `description`, `active`, `filename`)
	VALUES 
		(4, 'threadraffle', 'Raffle Tickets (Thread)', 'The members who reply to a thread are entered into a raffle.', '1', '/dbtech/vbactivity_pro/contesttype/threadraffle.php')
");
self::report('Populated Table', 'dbtech_vbactivity_contesttype');	

foreach (array(
	'contesttype',
) as $table)
{
	self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` = 'dbtech_vbactivity_{$table}'");
	self::report('Reverted Cache', 'dbtech_vbactivity_' . $table);
}
?>