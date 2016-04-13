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

// #############################################################################
if ($_REQUEST['action'] == 'statistics' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_statistics']);
	
	print_form_header('vbactivity', 'statistics');
	construct_hidden_code('action', 'level');
	print_table_header($vbphrase['dbtech_vbactivity_level_statistics'], 2, 0);
	print_description_row($vbphrase['dbtech_vbactivity_level_statistics_descr']);
	print_submit_row($vbphrase['dbtech_vbactivity_show_level_statistics'], false);
	
	print_form_header('vbactivity', 'statistics');
	construct_hidden_code('action', 'achievement');
	print_table_header($vbphrase['dbtech_vbactivity_achievement_statistics'], 2, 0);
	print_description_row($vbphrase['dbtech_vbactivity_achievement_statistics_descr']);
	print_submit_row($vbphrase['dbtech_vbactivity_show_achievement_statistics'], false);
	
	print_form_header('vbactivity', 'statistics');
	construct_hidden_code('action', 'award');
	print_table_header($vbphrase['dbtech_vbactivity_medal_statistics'], 2, 0);
	print_description_row($vbphrase['dbtech_vbactivity_medal_statistics_descr']);
	print_submit_row($vbphrase['dbtech_vbactivity_show_medal_statistics'], false);
}

// #############################################################################
if ($_POST['action'] == 'level')
{
	print_cp_header($vbphrase['dbtech_vbactivity_level_statistics']);
	
	// Set the excluded parameters
	VBACTIVITY::set_excluded_param();
	
	// Fetch users
	$users_q = $db->query_read_slave("
		SELECT 
			dbtech_vbactivity_pointscache AS pointscache,
			userid,
			username,
			user.usergroupid,
			infractiongroupid,
			displaygroupid
			" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
		FROM " . TABLE_PREFIX . "user AS user
		WHERE user.dbtech_vbactivity_excluded_tmp = '0'
		ORDER BY username ASC
	");
			
	$users = array();
	while ($users_r = $db->fetch_array($users_q))
	{
		// Shorthand
		$userinfo = $users_r;
		
		// Fetch activity level
		VBACTIVITY::fetch_activity_level($userinfo);
		
		// Grab the extended username
		//fetch_musername($userinfo);
		$userinfo['musername'] = $userinfo['username'];
		
		// Store the array sorted by level
		$users[$userinfo['activitylevel']][$userinfo['userid']] = $userinfo;
	}
	$db->free_result($users_q);
	unset($users_r, $userinfo);
	
	// Sort the key
	ksort($users, SORT_NUMERIC);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_level'];
	$headings[] = $vbphrase['dbtech_vbactivity_user_count'];
	$headings[] = $vbphrase['dbtech_vbactivity_users'];
	
	print_table_start();
	print_table_header($vbphrase['dbtech_vbactivity_level_statistics'], count($headings));
	print_cells_row($headings, 0, 'thead');
	
	foreach ($users as $level => $userlist)
	{
		// Table data
		$cell = array();
		$cell[] = $level;
		$cell[] = count($userlist);
		
		// Begin listing usernames
		$usernames = array();
		foreach ($userlist as $userid => $userinfo)
		{
			// Store the musername
			$usernames[] = construct_link_code($userinfo['musername'], '../member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userid . '&amp;tab=vbactivity', true);
		}
		
		// Add the list of usernames
		$cell[] = implode(', ', $usernames);		
		
		// Print the data
		print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);	
	}
	
	print_table_footer();	
}

// #############################################################################
if ($_POST['action'] == 'achievement')
{
	print_cp_header($vbphrase['dbtech_vbactivity_achievement_statistics']);
	
	// Set the excluded parameters
	VBACTIVITY::set_excluded_param();
	
	// Fetch users
	$achievements_q = $db->query_read_slave("
		SELECT 
			featureid AS achievementid,
			user.userid,
			username,
			user.usergroupid,
			infractiongroupid,
			displaygroupid
			" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
		FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards AS rewards
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = rewards.userid)
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = user.usergroupid)
		WHERE user.dbtech_vbactivity_excluded_tmp = '0'
			AND feature = 'achievement'
		ORDER BY username ASC
	");
	
	$achievements = array();
	$userinfo = array();
	while ($achievements_r = $db->fetch_array($achievements_q))
	{
		// Grab the extended username
		//fetch_musername($achievements_r);
		$achievements_r['musername'] = $achievements_r['username'];
		
		// Fetch the category id
		$categoryid = VBACTIVITY::$cache['achievement'][$achievements_r['achievementid']]['categoryid'];
		
		// Ensure we don't duplicate this
		$userinfo[$achievements_r['userid']] = $achievements_r;
		
		// Store the array sorted by level
		$achievements[$categoryid][$achievements_r['achievementid']][] = $achievements_r['userid'];
	}
	$db->free_result($achievements_q);
	unset($achievements_r);
	
	$achievements_by_category = array();
	foreach (VBACTIVITY::$cache['achievement'] as $achievementid => $achievement)
	{
		// Index by categoryid
		$achievements_by_category[$achievement['categoryid']][$achievementid] = $achievement;
	}
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_achievement'];
	$headings[] = $vbphrase['dbtech_vbactivity_user_count'];
	$headings[] = $vbphrase['dbtech_vbactivity_users'];
	
	print_table_start();	
	print_table_header($vbphrase['dbtech_vbactivity_achievement_statistics'], count($headings));
	
	foreach ($achievements_by_category as $categoryid => $achievementss)
	{
		if (!$achievements[$categoryid])
		{
			// This category didn't have any users
			continue;
		}

		print_table_header(VBACTIVITY::$cache['category'][$categoryid]['title'], count($headings));
		print_description_row(VBACTIVITY::$cache['category'][$categoryid]['description'], false, count($headings));	
		print_cells_row($headings, 0, 'thead');

		foreach ($achievementss as $achievementid => $achievement)
		{
			if (!$achievements[$categoryid][$achievementid])
			{
				// This achievement didn't have any users
				continue;
			}

			// Table data
			$cell = array();
			$cell[] = ($achievement['icon'] ? '<img src="../images/icons/vbactivity/' . $achievement['icon'] . '" alt="' . $achievement['title_translated'] . '" /> ' : '') . $achievement['title_translated'];
			$cell[] = count($achievements[$categoryid][$achievementid]);		
				
			// Begin listing usernames
			$usernames = array();
			foreach ($achievements[$categoryid][$achievementid] as $key => $userid)
			{
				// Store the musername
				$usernames[] = construct_link_code($userinfo[$userid]['musername'], '../member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userid . '&amp;tab=vbactivity', true);
			}
			
			// Add the list of usernames
			$cell[] = implode(', ', $usernames);		
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);	
		}
	}
	print_table_footer();
}

// #############################################################################
if ($_POST['action'] == 'award')
{
	print_cp_header($vbphrase['dbtech_vbactivity_medal_statistics']);
	
	// Set the excluded parameters
	VBACTIVITY::set_excluded_param();
	
	// Fetch users
	$medals_q = $db->query_read_slave("
		SELECT 
			featureid AS medalid,
			user.userid,
			username,
			user.usergroupid,
			infractiongroupid,
			displaygroupid
			" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
		FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards AS rewards
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = rewards.userid)
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = user.usergroupid)
		WHERE user.dbtech_vbactivity_excluded_tmp = '0'
			AND feature = 'medal'
		ORDER BY username ASC
	");
			
	$medals = array();
	$userinfo = array();
	while ($medals_r = $db->fetch_array($medals_q))
	{
		// Grab the extended username
		//fetch_musername($medals_r);
		$medals_r['musername'] = $medals_r['username'];
		
		// Fetch the category id
		$categoryid = VBACTIVITY::$cache['medal'][$medals_r['medalid']]['categoryid'];
		
		// Ensure we don't duplicate this
		$userinfo[$medals_r['userid']] = $medals_r;
		
		// Store the array sorted by level
		$medals[$categoryid][$medals_r['medalid']][] = $medals_r['userid'];
	}
	$db->free_result($medals_q);
	unset($medals_r);
	
	$medals_by_category = array();
	foreach (VBACTIVITY::$cache['medal'] as $medalid => $medal)
	{
		// Index by categoryid
		$medals_by_category[$medal['categoryid']][$medalid] = $medal;
	}
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_medal'];
	$headings[] = $vbphrase['dbtech_vbactivity_user_count'];
	$headings[] = $vbphrase['dbtech_vbactivity_users'];
	
	print_table_start();
	print_table_header($vbphrase['dbtech_vbactivity_medal_statistics'], count($headings));
	
	foreach ($medals_by_category as $categoryid => $medalss)
	{
		if (!$medals[$categoryid])
		{
			// This category didn't have any users
			continue;
		}

		print_table_header(VBACTIVITY::$cache['category'][$categoryid]['title'], count($headings));
		print_description_row(VBACTIVITY::$cache['category'][$categoryid]['description'], false, count($headings));	
		print_cells_row($headings, 0, 'thead');
		
		foreach ($medalss as $medalid => $medal)
		{
			if (!$medals[$categoryid][$medalid])
			{
				// This medal didn't have any users
				continue;
			}
			
			// Table data
			$cell = array();
			$cell[] = ($medal['icon'] ? '<img src="../images/icons/vbactivity/' . $medal['icon'] . '" alt="' . $medal['title'] . '" /> ' : '') . $medal['title'];
			$cell[] = count($medals[$categoryid][$medalid]);		
				
			// Begin listing usernames
			$usernames = array();
			foreach ($medals[$categoryid][$medalid] as $key => $userid)
			{
				// Store the musername
				$usernames[] = construct_link_code($userinfo[$userid]['musername'], '../member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userid . '&amp;tab=vbactivity', true);
			}
			
			// Add the list of usernames
			$cell[] = implode(', ', $usernames);		
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);	
		}
	}
	print_table_footer();
}
print_cp_footer();