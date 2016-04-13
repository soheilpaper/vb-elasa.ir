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
$navbits[''] = $vbphrase['dbtech_vbactivity_activity_target_winners'];

// draw cp nav bar
VBACTIVITY::setNavClass('activitystats');

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

// Count number of entries
$entries = $db->query_first_slave("
	SELECT COUNT(*) AS totalentries
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_activitystats AS activitystats
	LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
	WHERE user.dbtech_vbactivity_excluded_tmp = '0'
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
	'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . "do=activitystats",
	"&amp;perpage=$perpage"
);

// Init this
$activitystatbits = '';

// Fetch rewards
$rewards_q = $db->query_read_slave("
	SELECT
		activitystats.*,
		username,
		user.usergroupid,
		infractiongroupid,
		displaygroupid
		" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_activitystats AS activitystats
	LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
	WHERE user.dbtech_vbactivity_excluded_tmp = '0'
	ORDER BY dateline DESC
	LIMIT $startat, " . $perpage
);
while ($rewards_r = $db->fetch_array($rewards_q))
{
	// Grab markup username
	fetch_musername($rewards_r);
	
	$link = 'member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $rewards_r['userid'] . '&amp;tab=vbactivity';
	
	// Create a link to profile
	$link = '<a href="' . $link . '" target="_blank">' . $rewards_r['musername'] . '</a>';
	
	$templater = vB_Template::create('dbtech_vbactivity_activitystats_bit');
		$templater->register('user', 	$link);
		$templater->register('points', 	$rewards_r['points']);
		$templater->register('type', 	$vbphrase["dbtech_vbactivity_{$rewards_r[type]}_activity"]);
		$templater->register('date', 	vbdate($vbulletin->options['dateformat'], $rewards_r['dateline']));
	$activitystatbits .= $templater->render();
}
$db->free_result($rewards_q);
unset($rewards_r);

// Create the archive template
$templater = vB_Template::create('dbtech_vbactivity_activitystats');
	$templater->register('pagenav', 		$pagenav);
	$templater->register('pagenumber', 		$pagenumber);
	$templater->register('perpage', 		$perpage);
	$templater->register('activitystats', 	$activitystatbits);
$HTML = $templater->render();