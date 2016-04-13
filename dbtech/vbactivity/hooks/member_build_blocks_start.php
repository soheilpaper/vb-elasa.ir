<?php
if (!class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/vbactivity/includes/class_template.php');
}

// Fetch the profile blocks we need
require_once(DIR . '/dbtech/vbactivity/includes/class_profileblock.php');

$blocklist['vbactivity'] = array(
	'class' => 'vBActivity',
	'title' => $vbphrase['dbtech_vbactivity_vbactivity'],
	'options' => array(
		'numachievements' => $vbulletin->options['dbtech_vbactivity_numachievements'],
		'nummedals' => $vbulletin->options['dbtech_vbactivity_nummedals'],
	),
	'hook_location' => (intval($vbulletin->versionnumber) == 3 ? 'profile_left_last' : 'profile_tabs_last')
);

$headinclude .= vB_Template::create('dbtech_vbactivity_member_css')->render();
?>