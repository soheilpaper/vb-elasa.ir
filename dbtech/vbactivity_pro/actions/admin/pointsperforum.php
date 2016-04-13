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
if ($_REQUEST['action'] == 'pointsperforum' OR empty($_REQUEST['action']))
{
	$typeid = $vbulletin->input->clean_gpc('r', 'typeid', TYPE_UINT);
	if (!$type = VBACTIVITY::$cache['type'][$typeid])
	{
		// Couldn't find the type
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_type'], $typeid);
	}
	
	print_cp_header(strip_tags($vbphrase['dbtech_vbactivity_per_forum_points_settings'] . ': ' . ($vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] ? $vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] : "dbtech_vbactivity_condition_per{$type[typename]}")));
	print_form_header('vbactivity', 'points');
	construct_hidden_code('action', 'dopoints');
	print_table_header($vbphrase['dbtech_vbactivity_per_forum_points_settings'] . ': ' . ($vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] ? $vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] : "dbtech_vbactivity_condition_per{$type[typename]}"));
	print_description_row($vbphrase['dbtech_vbactivity_per_forum_points_settings_descr']);
	print_description_row($vbphrase['forums'], false, 2, 'optiontitle');	
	foreach ((array)$vbulletin->forumcache as $forumid => $forum)
	{
		// Ensure this is set
		$type['pointsperforum'][$forumid] = (isset($type['pointsperforum'][$forumid]) ? $type['pointsperforum'][$forumid] : -1);
		
		// Show the input form
		print_input_row(construct_depth_mark($forum['depth'],'- - ') . $forum['title'], "type[$typeid][pointsperforum][$forumid]", $type['pointsperforum'][$forumid]);
	}
	print_submit_row($vbphrase['save']);
}

print_cp_footer();