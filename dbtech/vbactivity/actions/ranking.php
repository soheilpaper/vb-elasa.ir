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
$navbits[''] = $vbphrase['dbtech_vbactivity_ranking'];

// draw cp nav bar
VBACTIVITY::setNavClass('ranking');

$vbulletin->input->clean_array_gpc('r', array(
	'pagenumber'  	=> TYPE_UINT,
	'perpage'     	=> TYPE_UINT,
	'sorttype'    	=> TYPE_STR,
	'sortorder'   	=> TYPE_STR,
));

// Ensure there's no errors or out of bounds with the page variables
if ($vbulletin->GPC['pagenumber'] < 1)
{
	$vbulletin->GPC['pagenumber'] = 1;
}
$pagenumber = $vbulletin->GPC['pagenumber']; // $vbulletin->options['dbtech_vbshout_maxshouts']
$perpage = (!$vbulletin->GPC['perpage'] OR $vbulletin->GPC['perpage'] > 25) ? 25 : $vbulletin->GPC['perpage'];
$sortorder 	= (!in_array(strtolower($vbulletin->GPC['sortorder']), array('asc', 'desc')) ? 'desc' : strtolower($vbulletin->GPC['sortorder']));

// Shorthands to faciliate easy copypaste
$reverseorder = ($sortorder == 'asc' ? 'desc' : 'asc');

switch ($vbulletin->GPC['sorttype'])
{
	case 'username':
		$sorttype = 'username';
		$sqlsort = 'username';
		break;
		
	case 'daily':
		$sorttype = 'daily';
		$sqlsort = 'pointscache_day';
		break;
		
	case 'weekly':
		$sorttype = 'weekly';
		$sqlsort = 'pointscache_week';
		break;
		
	case 'monthly':
		$sorttype = 'monthly';
		$sqlsort = 'pointscache_month';
		break;
		
	case 'points':
		$sorttype = 'points';
		$sqlsort = 'points';
		break;
	
	case 'activitylevel':
	default:
		$sorttype = 'activitylevel';
		$sqlsort = 'pointscache';
		break;
}

// Set the excluded parameters
VBACTIVITY::set_excluded_param();

// Count number of users
$users = $db->query_first_slave("
	SELECT COUNT(*) AS totalusers
	FROM " . TABLE_PREFIX . "user AS user
	WHERE user.dbtech_vbactivity_excluded_tmp = '0'
");

// Ensure every result is as it should be
sanitize_pageresults($users['totalusers'], $pagenumber, $perpage);

// Find out where to start
$startat = ($pagenumber - 1) * $perpage;

// Constructs the page navigation
$pagenav = construct_page_nav(
	$pagenumber,
	$perpage,
	$users['totalusers'],
	'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . "do=ranking",
	"&amp;perpage=$perpage&amp;sortorder=$sortorder&amp;sorttype=$sorttype"
);

// Fetch users
$users_q = $db->query_read_slave("
	SELECT 
		dbtech_vbactivity_points AS points,
		dbtech_vbactivity_pointscache AS pointscache,
		dbtech_vbactivity_pointscache_day AS pointscache_day,
		dbtech_vbactivity_pointscache_week AS pointscache_week,
		dbtech_vbactivity_pointscache_month AS pointscache_month,
		user.*
		" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : '') . "			
		" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
	FROM " . TABLE_PREFIX . "user AS user
	" . ($vbulletin->options['avatarenabled'] ? "
	LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid)
	LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid)
	" : '') . "
	WHERE user.dbtech_vbactivity_excluded_tmp = '0'
	ORDER BY $sqlsort $sortorder
	LIMIT $startat, " . $perpage
);

if (!function_exists('fetch_avatar_from_userinfo'))
{
	// Get the avatar function
	require_once(DIR . '/includes/functions_user.php');
}

$sortusers = array();
$userinfo_list = array();
while ($users_r = $db->fetch_array($users_q))
{
	// Attempt rounding
	$users_r['points'] = doubleval($users_r['points']);
	
	// Grab the extended username
	fetch_musername($users_r);	
	
	// Shorthand
	$userinfo = $users_r;
	
	// Fetch activity level
	VBACTIVITY::fetch_activity_level($userinfo);
	
	// Fetch activity rating
	VBACTIVITY::fetch_activity_rating($userinfo);
	
	// grab avatar from userinfo
	fetch_avatar_from_userinfo($userinfo, true);
	
	// Set musername
	$userinfo['musername'] = ($vbulletin->options['avatarenabled'] ? '<img border="0" src="' . $userinfo['avatarurl'] . '" alt="width="20" height="20" /> ' . $userinfo['musername'] : $userinfo['musername']);
		
	$userinfo['target']['daily'] 	= vb_number_format($userinfo['target']['daily'], 2);
	$userinfo['target']['weekly'] 	= vb_number_format($userinfo['target']['weekly'], 2);
	$userinfo['target']['monthly'] 	= vb_number_format($userinfo['target']['monthly'], 2);
	$userinfo['points'] 			= vb_number_format($userinfo['points'], 2);
	
	// Storage
	$sortusers["$userinfo[userid]"] = $userinfo["$sorttype"];
	$userinfo_list["$userinfo[userid]"] = $userinfo;
}
$db->free_result($users_q);
unset($users_r);		

if ($sortorder == 'asc')
{
	// Ascending order
	//asort($sortusers);
}
else
{
	// Descending order
	//arsort($sortusers);
}

$userbits = '';
foreach ($sortusers as $userid => $user)
{
	// Make usernames link to member profile
	$userinfo_list["$userid"]['musername'] = '<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userid . '&amp;tab=vbactivity" target="_blank">' . $userinfo_list["$userid"]['musername'] . '</a>';
	
	$templater = vB_Template::create('dbtech_vbactivity_ranking_userbit');
		$templater->register('userinfo', $userinfo_list["$userid"]);
	$userbits .= $templater->render();
}

// Create the archive template
$templater = vB_Template::create('dbtech_vbactivity_ranking');
	$templater->register('pagenav', 		$pagenav);
	$templater->register('pagenumber', 		$pagenumber);
	$templater->register('perpage', 		$perpage);
	$templater->register('reverseorder', 	$reverseorder);
	$templater->register('ranking', 		$userbits);
$HTML = $templater->render();