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

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// Add to the navbits
$navbits[''] = $vbphrase['dbtech_vbactivity_leaderboards'];

// draw cp nav bar
VBACTIVITY::setNavClass('leaderboards');

// Set the limit on number of users to fetch
$limit = 5;

/*DBTECH_PRO_START*/
$limit = $vbulletin->options['dbtech_vbactivity_leaderboard_topx'];
/*DBTECH_PRO_END*/

// Init this array
$leaders = array();	

$cacheResult = VBACTIVITY_CACHE::read('leaderboards', 'leaderboards');
if (!is_array($cacheResult))
{
	// Set the excluded parameters
	VBACTIVITY::set_excluded_param();

	// totalpoints
	$totalpoints = VBACTIVITY::fetch_type('totalpoints');

	// Fetch rewards
	$leaders_q = VBACTIVITY::$db->fetchAll('
		SELECT 
			:typeName AS value,
			:typeId AS typeid,
			user.userid,
			username,
			user.usergroupid,
			infractiongroupid,
			displaygroupid
			:vBShop
		FROM $user AS user
		WHERE user.dbtech_vbactivity_excluded_tmp = \'0\'
			AND :typeName <> 0
		ORDER BY value :sortOrder
		LIMIT :limit
	', array(
		':vBShop' 		=> ($vbulletin->products['dbtech_vbshop'] ? ', user.dbtech_vbshop_purchase' : ''),
		':typeName' 	=> 'dbtech_vbactivity_points',
		':typeId' 		=> $totalpoints,
		':sortOrder' 	=> 'DESC',
		':limit' 		=> $limit,
	));
		
	foreach ($leaders_q as $leaders_r)
	{		
		// Store a cache of the leaders
		$leaders[$leaders_r['typeid']][] = $leaders_r;
	}

	foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
	{
		if (!$type['active'] OR !($type['display'] & 4))
		{
			// This type wasn't even active
			continue;
		}

		if ($type['typename'] == 'totalpoints')
		{
			// Avoid dupe
			continue;
		}

		// Fetch rewards
		$leaders_q = VBACTIVITY::$db->fetchAll('
			SELECT 
				:typeName AS value,
				:typeId AS typeid,
				user.userid,
				username,
				user.usergroupid,
				infractiongroupid,
				displaygroupid
				:vBShop
			FROM $dbtech_vbactivity_points AS points
			LEFT JOIN $user AS user ON (user.userid = points.userid)
			WHERE user.dbtech_vbactivity_excluded_tmp = \'0\'
				AND :typeName <> 0
			ORDER BY value :sortOrder
			LIMIT :limit
		', array(
			':vBShop' 		=> ($vbulletin->products['dbtech_vbshop'] ? ', user.dbtech_vbshop_purchase' : ''),
			':typeName' 	=> $type['typename'],
			':typeId' 		=> $type['typeid'],
			':sortOrder' 	=> (!$type['sortorder'] ? 'ASC' : 'DESC'),
			':limit' 		=> $limit,
		));
			
		foreach ($leaders_q as $leaders_r)
		{
			// Store a cache of the leaders
			$leaders[$leaders_r['typeid']][] = $leaders_r;
		}
	}

	if ($cacheResult != -1)
	{
		// Write to the cache
		VBACTIVITY_CACHE::write($leaders, 'leaderboards', 'leaderboards');
	}
}
else
{
	// Set the entry cache
	$leaders = $cacheResult;
}

// Init an array for the two leader board types
$leaderboardbits = '';

foreach ($leaders as $typeid => $leaderList)
{
	if (!$type = VBACTIVITY::$cache['type'][$typeid])
	{
		// Invalid type
		continue;
	}

	if (!$type['active'] OR !($type['display'] & 4))
	{
		// Inactive type
		continue;
	}

	// Init this
	$userbits = array();
	$xmlbits = array();
	
	foreach ($leaderList as $key => $userinfo)
	{
		// Grab the musername
		fetch_musername($userinfo);

		// Round the points because SUM sucks
		$userinfo['value_xml'] = $userinfo['value'];
		$userinfo['value'] = vb_number_format(round($userinfo['value'], 2));

		// Update musername with link to profile
		$userinfo['musername'] = '<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . '&amp;tab=vbactivity" target="_blank">' . $userinfo['musername'] . '</a>';
		
		$templater = vB_Template::create('dbtech_vbactivity_leaderboards_userbit');
			$templater->register('userinfo', $userinfo);
		$userbits[($key + 1)] = $templater->render();

		if ($_REQUEST['xml'])
		{
			// For the Flash object
			$xmlbits[] = array($userinfo['username'], $member_seo, $userinfo['value_xml']);
		}
	}
	
	for ($k = 1; $k <= $limit; $k++)
	{
		if (!$userbits[$k])
		{
			// Didn't have this point
			$userbits[$k] = vB_Template::create('dbtech_vbactivity_leaderboards_userbit')->render();
		}
	}
	
	// Make sure we also got the phrase
	$phrase = ($type['typename'] == 'totalpoints' ? $vbphrase["dbtech_vbactivity_condition_{$type[typename]}"] : $vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"]);

	if ($_REQUEST['xml'])
	{
		// For the Flash object
		$xml[] = array(
			'phrase'	=> $phrase,
			'bits'		=> $xmlbits,
		);
	}
	
	$templater = vB_Template::create('dbtech_vbactivity_leaderboards_leaderboardbit');
		$templater->register('phrase', $phrase);
		$templater->register('userbits', implode('', $userbits));
	$leaderboardbits .= $templater->render();
}

// Display XML Feed
if ($_REQUEST['xml'])
{
	require_once(DIR . '/includes/class_xml.php');

	$xmlbuilder = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xmlbuilder->add_group('vbactivity');

	foreach ($xml as $group)
	{
		$xmlbuilder->add_group('group', array('phrase' => $group['phrase']));

		foreach ($group['bits'] as $user)
		{
			$xmlbuilder->add_tag('user', $user[0], array('seo' => $user[1], 'points' => $user[2]));
		}

		$xmlbuilder->close_group();
	}

	$xmlbuilder->close_group();
	$xmlbuilder->print_xml();
	exit;
}
else
{
	/*DBTECH_PRO_START*/
	if ($vbulletin->options['dbtech_vbactivity_leaderboard_flash'])
	{
		$leaderboardbits = vB_Template::create('dbtech_vbactivity_leaderboards_flash')->render() . $leaderboardbits;
	}
	/*DBTECH_PRO_END*/
	
	// Create the archive template
	$templater = vB_Template::create('dbtech_vbactivity_leaderboards');
		$templater->register('leaderboards', $leaderboardbits);
	$HTML = $templater->render();
}