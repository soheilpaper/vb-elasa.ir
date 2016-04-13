<?php
if (!class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/livewall/includes/class_template.php');
}

// Create our nav template
$dbtech_livewall_nav = vB_Template::create('dbtech_livewall_usercp_nav_link');

//if (!$vbulletin->userinfo['dbtech_vbshout_banned'] AND $vbulletin->options['dbtech_vbshout_active'])
//{
	// We're not banned and shoutbox is active
	$cells[] = 'dbtech_livewall_options';
//}
?>