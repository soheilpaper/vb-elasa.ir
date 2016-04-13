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

if (!VBACTIVITY::$permissions['points'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'points' OR empty($_REQUEST['action']))
{
	require_once(DIR . '/dbtech/vbactivity/includes/functions.php');
	
	$points = array();
	
	$types = VBACTIVITY::$cache['type'];
	$i = 0;
	print_cp_header($vbphrase['dbtech_vbactivity_points_settings']);
	print_form_header('vbactivity', 'points');
	construct_hidden_code('action', 'dopoints');
	print_table_header($vbphrase['dbtech_vbactivity_points_settings']);
	foreach ($types as $typeid => $type)
	{
		if (!($type['settings'] & 1) AND !$vbulletin->debug)
		{
			// We're not showing this points type
			continue;
		}
		
		$typename = $type['typename'];
		$type['settings'] = intval($type['settings']);

		// Each point value
		print_description_row(($vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] ? $vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] : "dbtech_vbactivity_condition_per{$type[typename]}"), false, 2, 'optiontitle');
		print_yes_no_row($vbphrase['dbtech_vbactivity_points_active'], "type[$typeid][active]", $type['active']);
		print_select_row($vbphrase['dbtech_vbactivity_points_sortorder'], "type[$typeid][sortorder]", array(0 => $vbphrase['ascending'], 1 => $vbphrase['descending']), $type['sortorder']);
		print_input_row($vbphrase['dbtech_vbactivity_points'] . ((VBACTIVITY::$isPro AND ($type['settings'] & 32)) ? '<span class="smallfont" style="float:right;">' . construct_link_code($vbphrase['dbtech_vbactivity_per_forum_points_settings'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=pointsperforum&amp;typeid=' . $typeid) . '</span>' : ''), "type[$typeid][points]", $type['points']);
		print_bitfield_row($vbphrase['dbtech_vbactivity_display_settings'], "type[$typeid][display]", 'nocache|dbtech_vbactivity_points_display', $type['display']);
		if ($vbulletin->debug)
		{
			// Action settings are only for devs
			print_bitfield_row($vbphrase['dbtech_vbactivity_action_settings'], "type[$typeid][settings]", 'nocache|dbtech_vbactivity_points_settings', $type['settings']);
		}
	}
	
	print_submit_row($vbphrase['save']);
}

// #############################################################################
if ($_REQUEST['action'] == 'dopoints')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'type' 	=> TYPE_ARRAY,
	));
	
	foreach ($vbulletin->GPC['type'] as $typeid => $type)
	{
		if (isset($type['display']))
		{
			$bit = 0;
			foreach ($type['display'] as $val)
			{
				$bit += $val;
			}
			$type['display'] = $bit;
		}
		
		if ($vbulletin->debug AND isset($type['settings']))
		{
			$bit = 0;
			foreach ($type['settings'] as $val)
			{
				$bit += $val;
			}
			$type['settings'] = $bit;
		}
		
		// set existing info if this is an update
		if (!$existing = VBACTIVITY::$cache['type'][$typeid])
		{
			// Couldn't find the type
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_type'], $typeid);
		}
		
		// init data manager
		$dm =& VBACTIVITY::initDataManager('Type', $vbulletin, ERRTYPE_CP);
			$dm->set_existing($existing);
				
		// type fields
		foreach ($type AS $key => $val)
		{
			if ($existing[$key] != $val)
			{
				// Only set changed values
				$dm->set($key, $val);
			}
		}
		
		// Save! Hopefully.
		$dm->save();
		unset($dm);
	}
	
	define('CP_REDIRECT', 'vbactivity.php?do=points');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_points_settings'], $vbphrase['dbtech_vbactivity_edited']);	
}

print_cp_footer();