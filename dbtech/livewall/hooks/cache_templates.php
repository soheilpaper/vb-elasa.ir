<?php
if (!is_array($cache))
{
	$cache = array();
}

$cache = array_merge($cache, array(
	'dbtech_livewall_block_entry',
	'dbtech_livewall_block_entries',
	'dbtech_livewall_css',
	'dbtech_livewall_comment',
));

switch (THIS_SCRIPT)
{
	case 'showthread':
		$cache[] = 'dbtech_livewall_post_comments';
		break;
}

if ($vbulletin->userinfo['permissions']['dbtech_livewallpermissions'] & $vbulletin->bf_ugp_dbtech_livewallpermissions['canview'])
{
	// Global templates
	$cache[] = 'dbtech_livewall_navbar_link';
	
	if ($vbulletin->options['dbtech_livewall_integration'] & 1 OR $vbulletin->options['dbtech_livewall_integration'] & 2)
	{
		$cache[] = 'dbtech_livewall_quicklinks_link';
	}
}

if (in_array('usercp_nav_folderbit', (array)$cache) OR in_array('usercp_nav_folderbit', (array)$globaltemplates))
{
	// UserCP templates
	$cache[] = 'dbtech_livewall_usercp_nav_link';
	$cache[] = 'dbtech_livewall_options';
	$cache[] = 'dbtech_livewall_options_bit';
	$cache[] = 'dbtech_livewall_options_bit_bit';
	$cache[] = 'dbtech_livewall_options_bit_bit2';
}

if (THIS_SCRIPT == 'member')
{
	$cache[] = 'dbtech_livewall_css';	
	$cache[] = 'dbtech_livewall_memberinfo_block_userwall';
	$cache[] = 'dbtech_livewall_entry';
}

if (intval($vbulletin->versionnumber) == 3)
{
	$cache[] = 'dbtech_livewall.css';
	
	$globaltemplates = array_merge($globaltemplates, $cache);
}
?>