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

// Init these arrays
$leaders = array();
$SQL = array();
$SQL['trophylog'] = array();
$SQL['trophycount'] = array();

// Set the excluded parameters
VBACTIVITY::set_excluded_param('trophy');

foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
{
	if (!$type['active'] OR !($type['settings'] & 4))
	{
		// This type wasn't even active
		continue;
	}
	
	if ($type['typename'] == 'totalpoints')
	{
		// Query for the leader of this points type
		$leader = $vbulletin->db->query_first_slave("
			SELECT user.userid, dbtech_vbactivity_points AS points
			FROM " . TABLE_PREFIX . "user AS user
			WHERE user.dbtech_vbactivity_excluded_tmp = '0'
			ORDER BY dbtech_vbactivity_points DESC, user.userid ASC
			LIMIT 1
		");
	}
	else
	{
		// Query for the leader of this points type
		$leader = $vbulletin->db->query_first_slave("
			SELECT user.userid, " . $type['typename'] . " AS points
			FROM " . TABLE_PREFIX . "dbtech_vbactivity_points AS points
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = points.userid)
			WHERE user.dbtech_vbactivity_excluded_tmp = '0'
			ORDER BY " . $type['typename'] . " " . ($type['points'] >= 0 ? 'DESC' : 'ASC') . ", user.userid ASC
			LIMIT 1
		");
	}
	
	// Set leader
	$leaderid = ($leader['points'] ? $leader['userid'] : 0);
	
	if ($type['userid'] != $leaderid)
	{
		if ($type['userid'] > 0)
		{
			// A leader lost his trophy
			$SQL['trophylog'][] = "($typeid, " . TIMENOW . ", " . $type['userid'] . ", 0)";
		}
		
		// We have a new champion
		$SQL['trophylog'][] = "($typeid, " . TIMENOW . ", $leaderid, 1)";
		$SQL['trophycount'][$leaderid] += 1;
		$SQL['trophylist'][$leaderid][] = $type['typename'];
		
		// Update trophy table
		$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
			SET userid = $leaderid
			WHERE typeid = $typeid
		");		
	}	
}

if (count($SQL['trophylog']))
{
	// Insert trophy log entries
	$vbulletin->db->query_write("
		INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_trophylog
			(typeid, dateline, userid, addremove)
		VALUES
			" . implode(',', $SQL['trophylog'])
	);
			
	foreach ($SQL['trophycount'] as $userid => $count)
	{
		// Add notifications
		VBACTIVITY::add_notification('trophy', $SQL['trophylist'][$userid], $userid, $count);
	}

	// Build cache
	VBACTIVITY_CACHE::build('type');
}

log_cron_action('', $nextitem, 1);