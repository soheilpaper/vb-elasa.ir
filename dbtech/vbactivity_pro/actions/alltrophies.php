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

if (!$vbulletin->options['dbtech_vbactivity_enable_trophies'])
{
	// This feature is disabled
	print_no_permission();
}

// Add to the navbits
$navbits[''] = $vbphrase['dbtech_vbactivity_alltrophies'];

// draw cp nav bar
VBACTIVITY::setNavClass('alltrophies');

$vbulletin->input->clean_array_gpc('r', array(
	'trophyid'  	=> TYPE_UINT
));

$queryusers = array();
foreach (VBACTIVITY::$cache['type'] as $typeid => $trophy)
{
	if (!$trophy['active'])
	{
		// Only active types
		continue;
	}
	
	if (!$trophy['userid'])
	{
		// This trophy didn't have an owner
		continue;
	}
	
	// Add this userid to query list
	$queryusers[] = $trophy['userid'];
}

if (count($queryusers))
{
	$cacheResult = VBACTIVITY_CACHE::read('alltrophies', 'alltrophies');
	if (!is_array($cacheResult))
	{
		// Set the excluded parameters
		VBACTIVITY::set_excluded_param();

		$users_q = VBACTIVITY::$db->fetchAll('
			SELECT
				userid,
				username,
				user.usergroupid,
				infractiongroupid,
				displaygroupid
				:vBShop
			FROM $user AS user
			LEFT JOIN $usergroup AS usergroup ON (usergroup.usergroupid = user.usergroupid)
			WHERE userid :queryList
		', array(
			':vBShop' => ($vbulletin->products['dbtech_vbshop'] ? ', user.dbtech_vbshop_purchase' : ''),
			':queryList' => VBACTIVITY::$db->queryList($queryusers)
		));

		if ($cacheResult != -1)
		{
			// Write to the cache
			VBACTIVITY_CACHE::write($users_q, 'alltrophies', 'alltrophies');
		}
	}
	else
	{
		// Set the entry cache
		$users_q = $cacheResult;
	}
	
	$userinfo = array();
	foreach ($users_q as $users_r)
	{
		// Fetch markup username
		fetch_musername($users_r);
		
		// Store the completed users array
		$userinfo[$users_r['userid']] = $users_r;
	}
}

if (intval($vbulletin->versionnumber) == 3)
{
	$HTML = '<table class="tborder" cellpadding="' . $stylevar['cellpadding'] . '" cellspacing="' . $stylevar['cellspacing'] . '" width="100%" border="0">';
}

$templater = vB_Template::create('dbtech_vbactivity_categorybits');
	$templater->register('expand', true);
	$templater->register('category', array('categoryid' => 1, 'title' => $vbphrase['dbtech_vbactivity_alltrophies'], 'description' => $vbphrase['dbtech_vbactivity_alltrophies']));

foreach (VBACTIVITY::$cache['type'] as $typeid => $trophy)
{
	if (!$trophy['active'] OR !($trophy['settings'] & 4))
	{
		// Only active types
		continue;
	}
	
	// Ensure this is correct
	$trophy['typename'] = ($trophy['typename'] == 'totalpoints' ? $trophy['typename'] : 'per' . $trophy['typename']);
	
	// Ensure this is set
	$trophy['trophyname'] 	= ($trophy['trophyname'] ? $trophy['trophyname'] : $vbphrase["dbtech_vbactivity_condition_{$trophy[typename]}"]);
	$trophy['user'] 		= ($trophy['userid'] ? 
		'<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $trophy['userid'] . '&amp;tab=vbactivity">' . $userinfo[$trophy['userid']]['musername'] . '</a>' :
		$vbphrase['n_a']
	);
	
	$templaterr = vB_Template::create('dbtech_vbactivity_contentbits');
		$templaterr->register('icon', ($trophy['icon'] ? '<img src="images/icons/vbactivity/' . $trophy['icon'] . '" alt="' . $trophy['trophyname'] . '" /> ' : '') . '<b>' . $trophy['trophyname'] . '</b>');
		$templaterr->register('description', $vbphrase["dbtech_vbactivity_condition_{$trophy[typename]}"]);
		$templaterr->register('conditions', $trophy['user']);
		$templaterr->register('extracss', ($trophyid == $typeid ? ' highlight' : ''));
	$contents .= $templaterr->render();
}
	$templater->register('contents', $contents);
$HTML .= $templater->render();

if (intval($vbulletin->versionnumber) == 3)
{
	$HTML .= '</table>';
}