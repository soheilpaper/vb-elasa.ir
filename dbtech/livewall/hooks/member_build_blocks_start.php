<?php
if ($vbulletin->options['dbtech_livewall_enable_profileblock'])
{
	// Fetch the profile blocks we need
	require_once(DIR . '/dbtech/livewall/includes/class_profileblock.php');
	
	$show['hidecommententry'] = true;
	
	$blocklist['livewall'] = array(
		'class' => 'LiveWall_UserWall',
		'title' => $vbphrase['dbtech_livewall_userwall'],
		'options' => array(
			'perpage' => $vbulletin->GPC['perpage'],
			'pagenumber' => $vbulletin->GPC['pagenumber']
		),
		'hook_location' => (intval($vbulletin->versionnumber) == 3 ? 'profile_left_last' : 'profile_tabs_last')
	);
	
	if (intval($vbulletin->versionnumber) == 3)
	{
		// Begin the monster template
		$headinclude .= vB_Template::create('dbtech_livewall.css')->render();
	}
	else
	{
		// Sneak the CSS into the headinclude
		$templater = vB_Template::create('dbtech_livewall_css');
			$templater->register('jQueryVersion', 	LIVEWALL::$jQueryVersion);
			$templater->register('versionnumber', 	LIVEWALL::$versionnumber);
		$headinclude .= $templater->render();
	}
}

/*
$show['vb4compat'] = version_compare($vbulletin->versionnumber, '4.0.8', '>=');
$headinclude .= vB_Template::create('dbtech_thanks_member_css')->render();
*/
?>