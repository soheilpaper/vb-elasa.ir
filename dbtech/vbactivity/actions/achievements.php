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

if (!$vbulletin->options['dbtech_vbactivity_enable_achievements'])
{
	// This feature is disabled
	print_no_permission();
}

// Add to the navbits
$navbits[''] = $pagetitle = $vbphrase['dbtech_vbactivity_achievements'];

// draw cp nav bar
VBACTIVITY::setNavClass('achievements');

$vbulletin->input->clean_array_gpc('r', array(
	'pagenumber'  	=> TYPE_UINT,
	'perpage'     	=> TYPE_UINT,
	'sorttype'    	=> TYPE_STR,
	'sortorder'   	=> TYPE_STR,
));

$pagenumber = $vbulletin->GPC['pagenumber'];
$perpage = $vbulletin->GPC['perpage'];

// Set the excluded parameters
VBACTIVITY::set_excluded_param();

// Count number of entries
$entries = $db->query_first_slave("
	SELECT COUNT(*) AS totalentries
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards AS rewards
	LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = rewards.userid)
	WHERE user.dbtech_vbactivity_excluded_tmp = '0'
		AND feature = 'achievement'
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
	'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . "do=achievements",
	"&amp;perpage=$perpage"
);

// Init this array
$rewards = array();

// Fetch rewards
$rewards_q = $db->query_read_slave("
	SELECT 
		rewards.*,
		user.*
		" . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : '') . "			
		" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards AS rewards
	LEFT JOIN " . TABLE_PREFIX . "user AS user ON (user.userid = rewards.userid)
	" . ($vbulletin->options['avatarenabled'] ? "
	LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid)
	LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid)
	" : '') . "
	WHERE $typeids
		user.dbtech_vbactivity_excluded_tmp = '0'
		AND feature = 'achievement'
	ORDER BY dateline DESC
	LIMIT $startat, " . $perpage
);

if (!function_exists('fetch_avatar_from_userinfo'))
{
	// Get the avatar function
	require_once(DIR . '/includes/functions_user.php');
}

while ($rewards_r = $db->fetch_array($rewards_q))
{
	if (!is_array(VBACTIVITY::$cache["$rewards_r[feature]"]["$rewards_r[featureid]"]))
	{
		// Rebuild the cache automatically
		VBACTIVITY_CACHE::build("dbtech_vbactivity_{$rewards_r[feature]}");
		continue;
	}
	
	// Grab the extended username
	fetch_musername($rewards_r);	
	
	// Shorthand
	$userinfo = $rewards_r;
	
	// grab avatar from userinfo
	fetch_avatar_from_userinfo($userinfo, true);
	
	// Set musername
	$userinfo['musername'] = ($vbulletin->options['avatarenabled'] ? '<img border="0" src="' . $userinfo['avatarurl'] . '" alt="width="20" height="20" /> ' . $userinfo['musername'] : $userinfo['musername']);
	
	// Store a cache of the rewards
	$rewards[vbdate($vbulletin->options['dateformat'], $userinfo['dateline'])][] = $userinfo;
}
$db->free_result($rewards_q);
unset($rewards_r);		

$achievementbits = '';
foreach ($rewards as $day => $achievements)
{
	$achievementbitbits = '';
	foreach ($achievements as $reward)
	{
		// Grab this
		$achievement = VBACTIVITY::$cache['achievement']["$reward[featureid]"];

		// Make a readable date
		$achievement['dateline'] = vbdate($vbulletin->options['timeformat'], $reward['dateline']);
		
		// Clean title
		$achievement['title_clean'] = $achievement['title_translated'];
		
		$feature = 'achievement';
		$featureinfo =& $$feature;
		
		/*DBTECH_PRO_START*/
		require(DIR . '/dbtech/vbactivity_pro/includes/actions/feature.php');
		/*DBTECH_PRO_END*/
		
		// The "has gained" message
		$achievement['phrase'] = construct_phrase($vbphrase['dbtech_vbactivity_user_gained_achievement_x'], 
			'<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $reward['userid'] . '&amp;tab=vbactivity" target="_blank">' . $reward['musername'] . '</a>',
			($achievement['icon'] ? '<img src="images/icons/vbactivity/' . $achievement['icon'] . '" alt="' . $achievement['title_clean'] . '" /> ' : '') . $achievement['title_translated']
		);
		
		// Gained image
		$achievement['image'] = 'gained';
		
		$templaterr = vB_Template::create('dbtech_vbactivity_entrybit');
			$templaterr->register('entry', $achievement);
		$achievementbitbits .= $templaterr->render();
	}
	
	$templater = vB_Template::create('dbtech_vbactivity_daybit');
		//$templater->register('category', VBACTIVITY::$cache['category']["$categoryid"]);
		$templater->register('day', $day);
		$templater->register('entrybits', $achievementbitbits);
	$achievementbits .= $templater->render();
}

// Create the archive template
$templater = vB_Template::create('dbtech_vbactivity_changes');
	$templater->register('pagetitle', 	$pagetitle);
	$templater->register('pagenav', 	$pagenav);
	$templater->register('pagenumber', 	$pagenumber);
	$templater->register('perpage', 	$perpage);
	$templater->register('changes', 	$achievementbits);
$HTML = $templater->render();