<?php
if (!class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/vbactivity/includes/class_template.php');
}

if ($vbulletin->options['dbtech_vbactivity_active'])
{
	// We're not banned and activitybox is active
	$show['dbtech_vbactivity_menu'] = true;
}

$cells[] = 'dbtech_vbactivity_options';

// Create our nav template
$dbtech_vbactivity_nav = vB_Template::create('dbtech_vbactivity_usercp_nav_link');
?>