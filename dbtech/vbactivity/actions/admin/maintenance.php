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
@set_time_limit(0);
ignore_user_abort(1);

if (!VBACTIVITY::$permissions['maintenance'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'maintenance' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_maintenance']);
	
	print_form_header('vbactivity', 'maintenance');
	construct_hidden_code('action', 'recalcpoints');
	print_table_header($vbphrase['dbtech_vbactivity_recalculate_points'], 2, 0);
	print_description_row($vbphrase['dbtech_vbactivity_recalculate_points_descr']);
	print_yes_no_row($vbphrase['dbtech_vbactivity_are_you_sure_recalc'], 'dorecalcpoints', 0);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle'], 'perpage', 50);
	if (VBACTIVITY::$isPro) print_time_row($vbphrase['start_date'], 'startdate', 0, false);
	print_description_row($vbphrase['note_server_intensive']);
	print_submit_row($vbphrase['dbtech_vbactivity_recalculate_points']);
	
	print_form_header('vbactivity', 'maintenance');
	construct_hidden_code('action', 'resetpoints');
	print_table_header($vbphrase['dbtech_vbactivity_reset_points'], 2, 0);
	print_description_row($vbphrase['dbtech_vbactivity_reset_points_descr']);
	print_yes_no_row($vbphrase['dbtech_vbactivity_are_you_sure_reset'], 'doresetpoints', 0);
	print_yes_no_row($vbphrase['dbtech_vbactivity_also_currency'], 'alsocurrency', 0);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle'], 'perpage', 50);
	print_submit_row($vbphrase['dbtech_vbactivity_reset_points']);
	
	print_form_header('vbactivity', 'maintenance');
	construct_hidden_code('action', 'recalcachievements');
	print_table_header($vbphrase['dbtech_vbactivity_recalculate_achievements'], 2, 0);
	print_description_row($vbphrase['dbtech_vbactivity_recalculate_achievements_descr']);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle'], 'perpage', 50);
	print_submit_row($vbphrase['dbtech_vbactivity_recalculate_achievements']);
	
	/*DBTECH_PRO_START*/
	print_form_header('vbactivity', 'maintenance');
	construct_hidden_code('action', 'recalcpromotions');
	print_table_header($vbphrase['dbtech_vbactivity_recalculate_promotions'], 2, 0);
	print_description_row($vbphrase['dbtech_vbactivity_recalculate_promotions_descr']);
	print_input_row($vbphrase['number_of_users_to_process_per_cycle'], 'perpage', 1000);
	print_submit_row($vbphrase['dbtech_vbactivity_recalculate_promotions']);
	/*DBTECH_PRO_END*/
}

// #############################################################################
if ($_REQUEST['action'] == 'recalcpoints')
{
	print_cp_header($vbphrase['dbtech_vbactivity_maintenance']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'dorecalcpoints' => TYPE_BOOL,
		'perpage' => TYPE_UINT,
		'startat' => TYPE_UINT,
		'startdate' => TYPE_UNIXTIME
	));
	
	if (!$vbulletin->GPC['dorecalcpoints'])
	{
		// Nothing to do
		print_stop_message('nothing_to_do');
	}
	
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 50;
	}
	
	if (empty($vbulletin->GPC['startat']))
	{
		// Remove points log entries
		$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_vbactivity_contestprogress");
		$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_vbactivity_points");
		$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_vbactivity_pointslog");
	}
	
	echo '<p>' . $vbphrase['dbtech_vbactivity_recalculating_points'] . '</p>';
	
	$users = $db->query_read_slave("
		SELECT user.*, userfield.*
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "userfield AS userfield USING(userid)
		WHERE user.userid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY user.userid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	
	$finishat = $vbulletin->GPC['startat'];
	
	while ($user = $db->fetch_array($users))
	{
		echo construct_phrase($vbphrase['processing_x'], $user['userid']);
		vbflush();
		
		// Shorthand
		$userid = intval($user['userid']);
		
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_points
				(userid)
			VALUES (
				'$userid'
			)
		");
		
		foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
		{
			echo '<div style="padding-left:20px;"><em>' . $type['typename'] . '</em></div> ';
			vbflush();
			
			// Inititalise this type
			VBACTIVITY::init_type($type);
			
			// Recalculate all points based on the type name
			VBACTIVITY::$types["$type[typename]"]->recalculate_points($user);
		}
		
		$curday = date('N');
		if ($curday != 1)
		{
			// This is not a monday
			$timestamp = strtotime(($curday * -1) . ' day');
		}
		else
		{
			// This is a monday
			$timestamp = mktime(0, 0, 0);
		}
		
		// Fetch various timestamps
		$today 	= (mktime(0, 0, 0));
		$week 	= (mktime(0, 0, 0, date('n', $timestamp), date('j', $timestamp)));
		$month 	= (mktime(0, 0, 0, date('n'), 1));
		
		$totalpoints = $db->query_first_slave("
			SELECT SUM(points) AS numpoints
			FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog 
			WHERE userid = '$userid'
		");
		$todayspoints = $db->query_first_slave("
			SELECT SUM(points) AS numpoints
			FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
			WHERE userid = '$userid'
				AND dateline >= $today
		");
		$weekspoints = $db->query_first_slave("
			SELECT SUM(points) AS numpoints
			FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
			WHERE userid = '$userid'
				AND dateline >= $week
		");
		$monthspoints = $db->query_first_slave("
			SELECT SUM(points) AS numpoints
			FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
			WHERE userid = '$userid'
				AND dateline >= $month
		");
		
		// Update poinst
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET
				dbtech_vbactivity_points 			= '" . round(doubleval($totalpoints['numpoints']), 2) . "',
				dbtech_vbactivity_pointscache 		= '" . round(doubleval($totalpoints['numpoints']), 2) . "',
				dbtech_vbactivity_pointscache_day 	= '" . round(doubleval($todayspoints['numpoints']), 2) . "',
				dbtech_vbactivity_pointscache_week 	= '" . round(doubleval($weekspoints['numpoints']), 2) . "',
				dbtech_vbactivity_pointscache_month = '" . round(doubleval($monthspoints['numpoints']), 2) . "'
			WHERE userid = '$userid'
		");
			
		echo '<div style="padding-left:20px;"><strong>' . $vbphrase['done'] . '</strong></div>';
		vbflush();
	
		$finishat = ($user['userid'] > $finishat ? $user['userid'] : $finishat);
	}
	
	$finishat++;
	
	if ($checkmore = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect(
			"vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&action=recalcpoints&startat=$finishat&pp=" . $vbulletin->GPC['perpage'] . "&dorecalcpoints=" . $vbulletin->GPC['dorecalcpoints'] . "&startdate=" . $vbulletin->GPC['startdate']
		);
		echo "<p>
			<a href=\"vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&amp;action=recalcpoints&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;dorecalcpoints=" . $vbulletin->GPC['dorecalcpoints'] . "&amp;startdate=" . $vbulletin->GPC['startdate'] . "
			\">" . $vbphrase['click_here_to_continue_processing'] . "</a>
		</p>";
	}
	else
	{
		// Update points cache
		VBACTIVITY::build_points_cache();
		
		define('CP_REDIRECT', 'vbactivity.php?do=maintenance');
		print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_points'], $vbphrase['dbtech_vbactivity_recalculated']);
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'resetpoints')
{
	print_cp_header($vbphrase['dbtech_vbactivity_maintenance']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'doresetpoints' => TYPE_BOOL,
		'alsocurrency' 	=> TYPE_BOOL,
		'perpage' 		=> TYPE_UINT,
		'startat' 		=> TYPE_UINT
	));
	
	if (!$vbulletin->GPC['doresetpoints'])
	{
		// Nothing to do
		print_stop_message('nothing_to_do');
	}
	
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}
	
	if (empty($vbulletin->GPC['startat']))
	{
		// Remove points log entries
		$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_vbactivity_contestprogress");
	}

	echo '<p>' . $vbphrase['dbtech_vbactivity_resetting_points'] . '</p>';
	
	$users = $db->query_read_slave("
		SELECT userid
		FROM " . TABLE_PREFIX . "user
		WHERE userid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY userid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	
	$finishat = $vbulletin->GPC['startat'];
	
	while ($user = $db->fetch_array($users))
	{
		echo construct_phrase($vbphrase['processing_x'], $user['userid']);
		vbflush();
		
		// Shorthand
		$userid = intval($user['userid']);
			
		// Update poinst
		$db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET dbtech_vbactivity_points = 0,
			" . ($vbulletin->GPC['alsocurrency'] ? " dbtech_vbactivity_pointscache = 0, " : '') . "
			dbtech_vbactivity_pointscache_day = 0,
			dbtech_vbactivity_pointscache_week = 0,
			dbtech_vbactivity_pointscache_month = 0
			WHERE userid = '$userid'
		");
		
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_points
			WHERE userid = '$userid'
		");
			
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_points
				(userid)
			VALUES (
				'$userid'
			)
		");
			
		$db->query_write("
			DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
			WHERE userid = '$userid'
		");
	
		$finishat = ($user['userid'] > $finishat ? $user['userid'] : $finishat);
	}
	
	$finishat++;
	
	if ($checkmore = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&action=resetpoints&startat=$finishat&pp=" . $vbulletin->GPC['perpage'] . "&doresetpoints=" . $vbulletin->GPC['doresetpoints'] . "&alsocurrency=" . $vbulletin->GPC['alsocurrency']);
		echo "<p><a href=\"vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&amp;action=resetpoints&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "&amp;doresetpoints=" . $vbulletin->GPC['doresetpoints'] . "&amp;alsocurrency=" . $vbulletin->GPC['alsocurrency'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		define('CP_REDIRECT', 'vbactivity.php?do=maintenance');
		print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_points'], $vbphrase['dbtech_vbactivity_reset']);
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'recalcachievements')
{
	print_cp_header($vbphrase['dbtech_vbactivity_maintenance']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage' => TYPE_UINT,
		'startat' => TYPE_UINT
	));
	
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}
	
	if (empty(VBACTIVITY::$cache['achievement']))
	{
		// Nothing to do
		print_stop_message('nothing_to_do');	
	}
	
	echo '<p>' . $vbphrase['dbtech_vbactivity_recalculating_achievements'] . '</p>';
	
	$users = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "user AS user
		WHERE userid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY userid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	
	$finishat = $vbulletin->GPC['startat'];
	
	while ($user = $db->fetch_array($users))
	{
		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();

		// Shorthand
		$userid = intval($user['userid']);
		
		// Ensure the cache is valid
		VBACTIVITY::verify_rewards_cache($user, false);
		
		foreach (VBACTIVITY::$cache['achievement'] as $achievementid => $achievement)
		{
			echo '<div style="padding-left:20px;"><em>' . $achievement['title'] . '</em></div> ';
			vbflush();
			
			try
			{
				// Check the achievement
				$allconditions = VBACTIVITY::check_feature('achievement', $achievementid, $user);
			}
			catch (Exception $e)
			{
				// We didn't meet the criteria. saedfaec
				$allconditions = false;
			}

			if (!$allconditions)
			{
				if (!is_array($user['dbtech_vbactivity_rewardscache']))
				{
					// Ensure the cache is valid
					VBACTIVITY::verify_rewards_cache($user, false);				
				}
				
				// We didn't meet the criteria. saedfaec
				foreach ((array)$user['dbtech_vbactivity_rewardscache'] as $rewardid => $reward)
				{
					if ($reward['feature'] == 'achievement' AND $reward['featureid'] == $achievementid)
					{
						// we had this reward, let's kill it
						$db->query_first_slave("DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards WHERE rewardid = " . intval($rewardid));
					}
				}
				
				continue;
			}
			
			// Add the reward
			VBACTIVITY::add_reward('achievement', $achievementid, $user, '', true);
		}
		
		// Build the rewards cache
		VBACTIVITY::build_rewards_cache($user);
	
		$finishat = ($user['userid'] > $finishat ? $user['userid'] : $finishat);
	}
	
	$finishat++;
	
	if ($checkmore = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&action=recalcachievements&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href=\"vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&amp;action=recalcachievements&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{	
		define('CP_REDIRECT', 'vbactivity.php?do=maintenance');
		print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_achievement'], $vbphrase['dbtech_vbactivity_recalculated']);
	}	
}

// #############################################################################
if ($_REQUEST['action'] == 'recalcpromotions')
{
	print_cp_header($vbphrase['dbtech_vbactivity_maintenance']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage' => TYPE_UINT,
		'startat' => TYPE_UINT
	));
	
	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 1000;
	}
	
	echo '<p>' . $vbphrase['dbtech_vbactivity_recalculating_promotions'] . '</p>';
	
	$users = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "user AS user
		WHERE userid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY userid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	
	$finishat = $vbulletin->GPC['startat'];
	
	while ($user = $db->fetch_array($users))
	{
		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();

		// Shorthand
		$userid = intval($user['userid']);
		
		foreach (VBACTIVITY::$cache['promotion'] as $promotionid => $promotion)
		{
			echo '<div style="padding-left:20px;"><em>' . $promotion['title'] . '</em></div> ';
			vbflush();

			try
			{
				// Check the promotion
				$allconditions = VBACTIVITY::check_feature('promotion', $promotionid, $user);
			}
			catch (Exception $e)
			{
				// We didn't meet the criteria. saedfaec
				$allconditions = false;
			}

			if (!$allconditions)
			{
				// We didn't meet the criteria. saedfaec
				continue;
			}
			
			// Add the reward
			VBACTIVITY::add_reward('promotion', $promotionid, $user, '', true);
		}
	
		$finishat = ($user['userid'] > $finishat ? $user['userid'] : $finishat);
	}
	
	$finishat++;
	
	if ($checkmore = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
	{
		print_cp_redirect("vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&action=recalcpromotions&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
		echo "<p><a href=\"vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=maintenance&amp;action=recalcpromotions&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{	
		define('CP_REDIRECT', 'vbactivity.php?do=maintenance');
		print_stop_message('dbtech_vbshout_promotions_recalculated');
	}
}

print_cp_footer();