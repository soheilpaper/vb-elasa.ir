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

if ($snapshot = $vbulletin->db->query_first_slave("
	SELECT snapshotid, data
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot
	WHERE snapshotid = (
		SELECT revertsnapshotid
		FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshotschedule
		WHERE end_hour = " . intval(date('G')) . "
			AND end_day = " . intval(date('w')) . "
			AND active = 1
	)
		OR snapshotid = (
		SELECT loadsnapshotid
		FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshotschedule
		WHERE start_hour = " . intval(date('G')) . "
			AND start_day = " . intval(date('w')) . "
			AND active = 1
	)
"))
{
	$data = unserialize($snapshot['data']);
	
	foreach ($data as $varname => $value)
	{
		// Set the setting
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "setting
			SET value = $value
			WHERE varname = 'dbtech_vbactivity_points_{$varname}'
		");
	}
	
	// Cache the options
	build_options();
	
	// Set the Active flag
	$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_snapshot SET active = IF(snapshotid = " . $vbulletin->db->sql_prepare($snapshot['snapshotid']) . ", 1, 0)");
}

log_cron_action('', $nextitem, 1);