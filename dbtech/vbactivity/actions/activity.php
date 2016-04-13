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

if (!$vbulletin->options['dbtech_vbactivity_enable_feed'])
{
	// This feature is disabled
	print_no_permission();
}

// Add to the navbits
$navbits[''] = $pagetitle = $vbphrase['dbtech_vbactivity_activity'];

// draw cp nav bar
VBACTIVITY::setNavClass('activity');

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

// Set the excluded parameters
VBACTIVITY::set_excluded_param();

$typeids = array();
foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
{
	if (!(int)$type['display'] & 2)
	{
		// Hide this type
		$typeids[] = $typeid;
		continue;
	}
}

// Ensure we have this working
$typeids = (count($typeids) ? 'pointslog.typeid NOT IN(' . implode(',', $typeids) . ') AND ' : '');

// Count number of users
$entries = $db->query_first_slave("
	SELECT COUNT(*) AS totalentries
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog AS pointslog
	LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = pointslog.userid)
	WHERE $typeids
		user.dbtech_vbactivity_excluded_tmp = '0'
");

// Ensure every result is as it should be
sanitize_pageresults($entries['totalentries'], $pagenumber, $perpage);

// Find out where to start
$startat = ($pagenumber - 1) * $perpage;

// Constructs the page navigation
$pagenav = construct_page_nav(
	$pagenumber,
	$perpage,
	$entries['totalentries'],
	'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . "do=activity",
	"&amp;perpage=$perpage"
);

// Fetch users
$entries_q = $db->query_read_slave("
	SELECT
		pointslog.*,
		user.*
		" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : '') . "			
		" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog AS pointslog
	LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = pointslog.userid)
	" . ($vbulletin->options['avatarenabled'] ? "
	LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid)
	LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid)
	" : '') . "
	WHERE $typeids
		user.dbtech_vbactivity_excluded_tmp = '0'
	ORDER BY dateline DESC			
	LIMIT $startat, " . $perpage
);

if (!function_exists('fetch_avatar_from_userinfo'))
{
	// Get the avatar function
	require_once(DIR . '/includes/functions_user.php');
}

$pointslogentries = array();
while ($entries_r = $db->fetch_array($entries_q))
{
	// Fetch the type name
	$typename = VBACTIVITY::$cache['type']["$entries_r[typeid]"]['typename'];
	
	// Grab the extended username
	fetch_musername($entries_r);	
	
	// Shorthand
	$userinfo = $entries_r;
	
	// grab avatar from userinfo
	fetch_avatar_from_userinfo($userinfo, true);
	
	// Set musername
	$userinfo['musername'] = ($vbulletin->options['avatarenabled'] ? '<img border="0" src="' . $userinfo['avatarurl'] . '" alt="width="20" height="20" /> ' . $userinfo['musername'] : $userinfo['musername']);
	
	$userinfo['phrase'] = ($userinfo['points'] < 0 ? 
		// Negative points
		construct_phrase($vbphrase['dbtech_vbactivity_user_lost_x_points_for_y'], 
			'<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . '&amp;tab=vbactivity" target="_blank">' . $userinfo['musername'] . '</a>',
			abs($userinfo['points']),
			$vbphrase["dbtech_vbactivity_condition_per{$typename}"]
		) : 
		
		// Positive points
		construct_phrase($vbphrase['dbtech_vbactivity_user_gained_x_points_for_y'],
			'<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $userinfo['userid'] . '&amp;tab=vbactivity" target="_blank">' . $userinfo['musername'] . '</a>',
			$userinfo['points'],
			$vbphrase["dbtech_vbactivity_condition_per{$typename}"]
		)
	);
	
	$userinfo['image'] = ($userinfo['points'] < 0 ? 
		// Negative points
		'lost' : 
		
		// Positive points
		'gained'
	);
	
	// Add the entry to the list, grouped by day
	$pointslogentries[vbdate($vbulletin->options['dateformat'], $userinfo['dateline'])]["$userinfo[pointslogid]"] = $userinfo;
}
$db->free_result($entries_q);
unset($entries_r);		

$entrybits = '';
foreach ($pointslogentries as $day => $entries)
{
	$entrybitbits = '';
	foreach ($entries as $entry)
	{
		$entry['dateline'] = vbdate($vbulletin->options['timeformat'], $entry['dateline']);
		
		$templaterr = vB_Template::create('dbtech_vbactivity_entrybit');
			$templaterr->register('entry', $entry);
		$entrybitbits .= $templaterr->render();
	}
	
	$templater = vB_Template::create('dbtech_vbactivity_daybit');
		$templater->register('day', $day);
		$templater->register('entrybits', $entrybitbits);				
	$entrybits .= $templater->render();
}

// Create the archive template
$templater = vB_Template::create('dbtech_vbactivity_changes');
	$templater->register('pagetitle', 	$pagetitle);
	$templater->register('pagenav', 	$pagenav);
	$templater->register('pagenumber', 	$pagenumber);
	$templater->register('perpage', 	$perpage);
	$templater->register('changes', 	$entrybits);
$HTML = $templater->render();