<?php
if (!isset($cache))
{
	$cache = array();
}
switch (THIS_SCRIPT)
{
	case 'member':
		$cache = array_merge(array(
			'dbtech_vbactivity_member_css',
			'dbtech_vbactivity_memberinfo_featurebit',
			'dbtech_vbactivity_memberinfo_pointsbit',
			'dbtech_vbactivity_memberinfo_block_activity',
			'dbtech_vbactivity_bar'
		), $cache);
		break;
		
	case 'usernote':
	case 'showthread':
	case 'showpost':
	case 'private':
	case 'announcement':
	case 'vbcms':
		$cache = array_merge(array(
			'dbtech_vbactivity_bar',
			'dbtech_vbactivity_postbit_reward',
			'dbtech_vbactivity_postbit_rewardbit',
			'dbtech_vbactivity_postbit_stats',
			'dbtech_vbactivity_postbit_stats_level',
			'dbtech_vbactivity_postbit_stats_points',
			'dbtech_vbactivity_postbit_stats_activitybit',
		), $cache);
		break;
}

if ($vbulletin->userinfo['permissions']['dbtech_vbactivitypermissions'] & $vbulletin->bf_ugp_dbtech_vbactivitypermissions['canview'])
{
	// Global templates
	$cache[] = 'dbtech_vbactivity_navbar_link';
	
	if ($vbulletin->options['dbtech_vbactivity_integration'] & 1 OR $vbulletin->options['dbtech_vbactivity_integration'] & 2)
	{
		$cache[] = 'dbtech_vbactivity_quicklinks_link';
	}
}

if (in_array('usercp_nav_folderbit', (array)$cache) OR in_array('usercp_nav_folderbit', (array)$globaltemplates))
{
	// UserCP templates
	$cache[] = 'dbtech_vbactivity_usercp_nav_link';
	$cache[] = 'dbtech_vbactivity_options';
	$cache[] = 'dbtech_vbactivity_options_bit';
	$cache[] = 'dbtech_vbactivity_options_bit_bit';
}

if (intval($vbulletin->versionnumber) == 3)
{
	$cache[] = 'dbtech_vbactivity.css';
	
	$globaltemplates = array_merge($globaltemplates, $cache);
}
?>