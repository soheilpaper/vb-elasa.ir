<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

if (!is_object($vbulletin->db))
{
	exit;
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// Set the excluded parameters
VBACTIVITY::set_excluded_param();

if (date('N') == 1)
{
	// This is the first day of the week
	if ($vbulletin->options['dbtech_vbactivity_stats_storage'] & 2)
	{
		// Store the weekly winrar
		$vbulletin->db->query_write("
			INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_activitystats
				(userid, points, type, dateline)
			SELECT userid, dbtech_vbactivity_pointscache_week, 'weekly' AS type, " . TIMENOW . " AS dateline
			FROM " . TABLE_PREFIX . "user AS user
			WHERE user.dbtech_vbactivity_excluded_tmp = '0'
				AND dbtech_vbactivity_pointscache_week > 0
			ORDER BY dbtech_vbactivity_pointscache_week DESC
			LIMIT 1
		");		

	}

	// Reset weekly points cache
	VBACTIVITY::$db->update('user', array('dbtech_vbactivity_pointscache_week' => 0), 'WHERE 1=1');
}

if (date('j') == 1)
{
	// This is first day of the month	
	if ($vbulletin->options['dbtech_vbactivity_stats_storage'] & 4)
	{
		// Store the monthly winrar
		$vbulletin->db->query_write("
			INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_activitystats
				(userid, points, type, dateline)
			SELECT userid, dbtech_vbactivity_pointscache_month, 'monthly' AS type, " . TIMENOW . " AS dateline
			FROM " . TABLE_PREFIX . "user AS user
			WHERE user.dbtech_vbactivity_excluded_tmp = '0'
				AND dbtech_vbactivity_pointscache_month > 0
			ORDER BY dbtech_vbactivity_pointscache_month DESC
			LIMIT 1
		");		
	}

	// Reset monthly points cache
	VBACTIVITY::$db->update('user', array('dbtech_vbactivity_pointscache_month' => 0), 'WHERE 1=1');
}

if ($vbulletin->options['dbtech_vbactivity_stats_storage'] & 1)
{
	// Store the daily winrar
	$vbulletin->db->query_write("
		INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_activitystats
			(userid, points, type, dateline)
		SELECT userid, dbtech_vbactivity_pointscache_day, 'daily' AS type, " . TIMENOW . " AS dateline
		FROM " . TABLE_PREFIX . "user AS user
		WHERE user.dbtech_vbactivity_excluded_tmp = '0'
			AND dbtech_vbactivity_pointscache_day > 0
		ORDER BY dbtech_vbactivity_pointscache_day DESC
		LIMIT 1
	");
}

// Reset monthly points cache
VBACTIVITY::$db->update('user', array('dbtech_vbactivity_pointscache_day' => 0), 'WHERE 1=1');

$typeid = VBACTIVITY::fetch_type('dayregistered');
$type = VBACTIVITY::$cache['type']["$typeid"];
if ($type['points'])
{
	$vbulletin->db->query_write("
		INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
			(userid, dateline, points, typeid)
		SELECT 
			userid,
			UNIX_TIMESTAMP() AS dateline,
			" . $type['points'] . " AS points,
			" . $typeid . " AS typeid
		FROM " . TABLE_PREFIX . "user AS user
		WHERE user.dbtech_vbactivity_excluded_tmp = '0'
		ORDER BY userid
	");
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_points
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		SET
			dayregistered = dayregistered + " . $vbulletin->db->sql_prepare($type['points']) . "
		WHERE user.dbtech_vbactivity_excluded_tmp = '0'
	");
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "user AS user
		SET
			dbtech_vbactivity_points = dbtech_vbactivity_points + " . $vbulletin->db->sql_prepare($type['points']) . ",
			dbtech_vbactivity_pointscache = dbtech_vbactivity_pointscache + " . $vbulletin->db->sql_prepare($type['points']) . ",
			dbtech_vbactivity_pointscache_week = dbtech_vbactivity_pointscache_week + " . $vbulletin->db->sql_prepare($type['points']) . ",
			dbtech_vbactivity_pointscache_month = dbtech_vbactivity_pointscache_month + " . $vbulletin->db->sql_prepare($type['points']) . "
		WHERE user.dbtech_vbactivity_excluded_tmp = '0'
	");
}
log_cron_action('', $nextitem, 1);