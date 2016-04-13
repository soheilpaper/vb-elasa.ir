<?php
if (VBACTIVITY::$permissions['ismanager'])
{
	if (VBACTIVITY::$permissions['achievement'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_achievements'], 		'vbactivity.php?do=achievement');	
	}
	if (VBACTIVITY::$permissions['category'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_categories'], 			'vbactivity.php?do=category');	
	}
	if (VBACTIVITY::$permissions['criteria'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_conditions'], 			'vbactivity.php?do=criteria');	
	}
	if (VBACTIVITY::$permissions['award'] OR VBACTIVITY::$permissions['grantawards'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_medals'], 				'vbactivity.php?do=award');	
	}
	if (VBACTIVITY::$permissions['grantawards'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_medal_req_nom'], 				'vbactivity.php?do=award&amp;action=requests');	
	}
	if (VBACTIVITY::$permissions['maintenance'])
	{
		construct_nav_option($vbphrase['maintenance'], 									'vbactivity.php?do=maintenance');
	}

	// Everyone can help fix stuff, yay!
	construct_nav_option($vbphrase['dbtech_vbactivity_repair_cache'],					'vbactivity.php?do=repaircache');	
		
	/*DBTECH_PRO_START*/
	if (VBACTIVITY::$permissions['trophy'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_trophies'], 			'vbactivity.php?do=trophy');	
	}
	if (VBACTIVITY::$permissions['contest'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_activity_contests'], 			'vbactivity.php?do=contest');
	}
	if (VBACTIVITY::$permissions['promotion'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_promotions'], 			'vbactivity.php?do=promotion');
	}
	if (VBACTIVITY::$permissions['backup'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_backups'], 			'vbactivity.php?do=backup');
	}
	if (VBACTIVITY::$permissions['snapshot'])
	{
		// Buy two for one, special price for you
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_snapshots'], 			'vbactivity.php?do=snapshot');
		construct_nav_option($vbphrase['dbtech_vbactivity_manage_snapshot_schedules'], 	'vbactivity.php?do=snapshot&amp;action=schedule');
	}
	if (VBACTIVITY::$permissions['impex'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_impex'], 						'vbactivity.php?do=impex');
	}

	// Everyone can view statistics too, z0mg!
	construct_nav_option($vbphrase['dbtech_vbactivity_statistics'], 					'vbactivity.php?do=statistics');
	/*DBTECH_PRO_END*/

	if (VBACTIVITY::$permissions['points'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_points_settings'], 			'vbactivity.php?do=points');
	}
	if (VBACTIVITY::$permissions['permissions'])
	{
		construct_nav_option($vbphrase['dbtech_vbactivity_permissions'], 				'vbactivity.php?do=permissions');
	}
	if (VBACTIVITY::$permissions['options'])
	{
		construct_nav_option('Settings', 												'vbactivity.php?do=options');	
	}

	construct_nav_group($vbphrase['dbtech_vbactivity_short']);
}
?>