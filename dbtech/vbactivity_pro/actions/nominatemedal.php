<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
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

if (!$vbulletin->options['dbtech_vbactivity_enable_medals'])
{
	// This feature is disabled
	print_no_permission();
}

// Grab the id
$medalid = $vbulletin->input->clean_gpc('r', 'medalid', TYPE_UINT);

if (!$info = VBACTIVITY::$cache['medal'][$medalid])
{
	eval(standard_error(fetch_error('dbtech_vbactivity_error_x', $vbphrase['dbtech_vbactivity_invalid_feature_or_featureid'])));
}

if (!($info['availability'] & 2))
{
	// This feature is disabled
	print_no_permission();
}

// Add to the navbits
$navbits[''] = $pagetitle = construct_phrase($vbphrase['dbtech_vbactivity_nominating_medal'], $info['title_translated']);

// draw cp nav bar
VBACTIVITY::setNavClass('allawards');

$requests = array(
	'request' => 0,
	'nominate' => 0
);
$requests_q = $db->query_read_slave("
	SELECT targetuserid, medalid, dateline
	FROM " . TABLE_PREFIX . "dbtech_vbactivity_medalrequest
	WHERE userid = " . $vbulletin->userinfo['userid'] . "
	ORDER BY dateline ASC		
");
while ($requests_r = $db->fetch_array($requests_q))
{
	$type = ($requests_r['targetuserid'] == $vbulletin->userinfo['userid'] ? 'request' : 'nominate');
	$requests[$type] = $requests_r['dateline'];
}
$db->free_result($requests_q);
unset($requests_r);

if (!(($vbulletin->userinfo['permissions']['dbtech_vbactivitypermissions'] & $vbulletin->bf_ugp_dbtech_vbactivitypermissions['cannominatemedal']) AND 
	!($vbulletin->userinfo['permissions']['dbtech_vbactivity_nominatedelay'] AND $requests['nominate'] >= (TIMENOW - ($vbulletin->userinfo['permissions']['dbtech_vbactivity_nominatedelay'] * 86400))))
)
{
	// Throw error from invalid action
	eval(standard_error(fetch_error('dbtech_vbactivity_error_x', $vbphrase['dbtech_vbactivity_may_not_nominate'])));
}

// #######################################################################
if ($_REQUEST['action'] == 'main' OR !$_REQUEST['action'])
{
	// Begin the page template
	$page_templater = vB_Template::create('dbtech_vbactivity_nominate');
		$page_templater->register('pagetitle', $pagetitle);
		$page_templater->register('medalinfo', $info);
	$HTML = $page_templater->render();
}

// #######################################################################
if ($_REQUEST['action'] == 'donominate')
{
	// Grab the username
	$username = $vbulletin->input->clean_gpc('p', 'username', TYPE_STR);
	$reason = $vbulletin->input->clean_gpc('p', 'reason', TYPE_NOHTML);
	
	if (trim($username) == $vbulletin->userinfo['username'])
	{
		eval(standard_error(fetch_error('invalid_user_specified')));
	}
	
	if (!$target = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = " . $db->sql_prepare(htmlspecialchars_uni(trim($username)))))
	{
		eval(standard_error(fetch_error('invalid_user_specified')));
	}
	
	// Request a new medal
	VBACTIVITY::$db->insert('dbtech_vbactivity_medalrequest', array(
		'userid' 		=> $vbulletin->userinfo['userid'],
		'targetuserid' 	=> $target['userid'],
		'medalid' 		=> $medalid,
		'dateline' 		=> TIMENOW,
		'reason' 		=> $reason
	));
	
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "user AS user
		LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = user.usergroupid)
		SET dbtech_vbactivity_medalmoderatecount = dbtech_vbactivity_medalmoderatecount + 1
		WHERE dbtech_vbactivitypermissions & " . $vbulletin->bf_ugp_dbtech_vbactivitypermissions['ismanager'] . "
	");
	
	$vbulletin->url = 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=allawards&medalid=' . $medalid;
	eval(print_standard_redirect('redirect_dbtech_vbactivity_user_nominated'));	
}