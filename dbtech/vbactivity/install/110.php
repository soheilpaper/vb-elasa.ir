<?php
// Altered Tables

// Add the dbtech_vbactivity_pointslog field
if (self::$db_alter->fetch_table_info('dbtech_vbactivity_pointslog'))
{
	self::$db->hide_errors();	
	self::$db->query_write("ALTER TABLE " . TABLE_PREFIX . "dbtech_vbactivity_pointslog CHANGE `points` `points` DOUBLE NOT NULL DEFAULT '0'");
	self::$db->query_write("ALTER TABLE " . TABLE_PREFIX . "dbtech_vbactivity_pointslog ADD INDEX `userdate` ( `userid` , `dateline` )");
	self::$db->show_errors();	
	self::report('Altered Table', 'dbtech_vbactivity_pointslog');	
}

if (self::$db_alter->fetch_table_info('user'))
{
	self::$db->query_write("ALTER TABLE " . TABLE_PREFIX . "user CHANGE `dbtech_vbactivity_points` `dbtech_vbactivity_points` DOUBLE NOT NULL DEFAULT '0'");
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_pointscache',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));	
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_pointscache_day',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));	
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_pointscache_week',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));	
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_vbactivity_pointscache_month',
		'type'       => 'double',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));	
	self::report('Altered Table', 'user');
}

// Get rid of old rows
self::$db->query_write("UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_pointslog SET points = ROUND(points, 2)");
self::$db->query_write("UPDATE " . TABLE_PREFIX . "user SET dbtech_vbactivity_points = ROUND(dbtech_vbactivity_points, 2)");

$curday = date('w');
if ($curday != '0')
{
	// This is not a sunday
	$timestamp = strtotime(($curday * -1) . ' day');
}
else
{
	// This is a sunday
	$timestamp = mktime(0, 0, 0);
}

// Fetch various timestamps
$today 	= mktime(0, 0, 0);
$week 	= mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp));
$month 	= mktime(0, 0, 0, date('n'), 1);


$pointsLog1 = self::$db->query_read_slave("
	SELECT SUM(points) AS numpoints, userid
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog 
	GROUP BY userid
");
while ($pointslog = self::$db->fetch_array($pointsLog1))
{
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "user
		SET dbtech_vbactivity_pointscache = '" . doubleval($pointslog['numpoints']) . "'
		WHERE userid = " . intval($pointslog['userid'])
	);
}

$pointsLog2 = self::$db->query_read_slave("
	SELECT SUM(points) AS numpoints, userid
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
	WHERE dateline >= $today
	GROUP BY userid
");
while ($pointslog = self::$db->fetch_array($pointsLog1))
{
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "user
		SET dbtech_vbactivity_pointscache_day = '" . doubleval($pointslog['numpoints']) . "'
		WHERE userid = " . intval($pointslog['userid'])
	);
}

$pointsLog3 = self::$db->query_read_slave("
	SELECT SUM(points) AS numpoints, userid
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
	WHERE dateline >= $week
	GROUP BY userid
");
while ($pointslog = self::$db->fetch_array($pointsLog1))
{
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "user
		SET dbtech_vbactivity_pointscache_week = '" . doubleval($pointslog['numpoints']) . "'
		WHERE userid = " . intval($pointslog['userid'])
	);
}

$pointsLog4 = self::$db->query_read_slave("
	SELECT SUM(points) AS numpoints, userid
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
	WHERE dateline >= $month
	GROUP BY userid
");
while ($pointslog = self::$db->fetch_array($pointsLog1))
{
	self::$db->query_write("
		UPDATE " . TABLE_PREFIX . "user
		SET dbtech_vbactivity_pointscache_month = '" . doubleval($pointslog['numpoints']) . "'
		WHERE userid = " . intval($pointslog['userid'])
	);
}

self::report('Updated Cache',  'Points');
?>