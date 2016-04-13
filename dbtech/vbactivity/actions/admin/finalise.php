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

print_cp_header($vbphrase['dbtech_vbactivity_maintenance']);

$vbulletin->input->clean_array_gpc('r', array(
	'perpage' => TYPE_UINT,
	'startat' => TYPE_UINT,
	'version' => TYPE_UINT
));

if (empty($vbulletin->GPC['perpage']))
{
	$vbulletin->GPC['perpage'] = 250;
}

echo '<p>Finalising Install...</p>';

if ($vbulletin->GPC['version'] == 2000)
{
	$users = $db->query_read_slave("
		SELECT user.*
		FROM " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "dbtech_vbactivity_points AS points USING(userid)
		WHERE user.userid >= " . $vbulletin->GPC['startat'] . "
		ORDER BY user.userid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	
	$finishat = $vbulletin->GPC['startat'];
	
	$types = $db->query_read_slave("SELECT * FROM `" . TABLE_PREFIX . "dbtech_vbactivity_type` WHERE typename NOT IN('totalpoints', 'activitylevel')");
	$typenames = array();
	$sums = array();
	while ($type = $db->fetch_array($types))
	{
		if (!in_array($type['typename'], $typenames))
		{		
			$typenames[] = $type['typename'];
			$sums[] = "(
				SELECT IFNULL(SUM(points), 0) AS points
				FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
				WHERE userid = user.userid
					AND typeid = $type[typeid]
			) AS $type[typename]";
		}
	}
	
	while ($user = $db->fetch_array($users))
	{
		// Shorthand
		$userid = intval($user['userid']);
		
		$db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "dbtech_vbactivity_points
				(userid, " . implode(',', $typenames) . ")
			SELECT 
				userid,
				" . implode(',', $sums) . "
			FROM " . TABLE_PREFIX . "user AS user
			WHERE userid = $userid
			ORDER BY userid
		");	
		
		echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
		vbflush();
	
		$finishat = ($user['userid'] > $finishat ? $user['userid'] : $finishat);
	}
	$finishat++;
}

if ($checkmore = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
{
	print_cp_redirect("vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=finalise&version=" . $vbulletin->GPC['version'] . "&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
	echo "<p><a href=\"vbactivity.php?" . $vbulletin->session->vars['sessionurl'] . "do=finalise&amp;version=" . $vbulletin->GPC['version'] . "&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
}
else
{	
	define('CP_REDIRECT', 'index.php?loc=' . urlencode('plugin.php?do=product'));
	print_stop_message('dbtech_vbactivity_points_recalculated');
}

print_cp_footer();