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

if (!VBACTIVITY::$permissions['trophy'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'trophy' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_trophy_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_source_name'];
	$headings[] = $vbphrase['dbtech_vbactivity_trophy_name'];
	$headings[] = $vbphrase['dbtech_vbactivity_trophy_holder'];
	$headings[] = $vbphrase['edit'];
	
	print_table_start();	
	print_table_header($vbphrase['dbtech_vbactivity_trophy_management'], count($headings));
	print_description_row($vbphrase['dbtech_vbactivity_trophy_management_descr'], false, count($headings));	
	print_cells_row($headings, 0, 'thead');	
	
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
		// Query all the user info we need
		$users_q = $db->query_read_slave("
			SELECT
				userid,
				username,
				user.usergroupid,
				infractiongroupid,
				displaygroupid
				" . ($vbulletin->products['dbtech_vbshop'] ? ", user.dbtech_vbshop_purchase" : '') . "
			FROM " . TABLE_PREFIX . "user AS user
			LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = user.usergroupid)
			WHERE userid IN(" . implode(',', $queryusers) . ")
		");
		
		$userinfo = array();
		while ($users_r = $db->fetch_array($users_q))
		{
			// Fetch markup username
			fetch_musername($users_r);
			
			// Store the completed users array
			$userinfo["$users_r[userid]"] = $users_r;
		}
		$db->free_result($users_q);
		unset($users_r);
	}
	
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
			construct_link_code($userinfo["$trophy[userid]"]['musername'], '../member.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $trophy['userid'] . '&amp;tab=vbactivity', true) :
			$vbphrase['n_a']
		);
		
		// Table data
		$cell = array();
		$cell[] = $vbphrase["dbtech_vbactivity_condition_{$trophy[typename]}"];
		$cell[] = ($trophy['icon'] ? '<img src="../images/icons/vbactivity/' . $trophy['icon'] . '" alt="' . $trophy['trophyname'] . '" /> ' : '') . $trophy['trophyname'];
		$cell[] = $trophy['user'];
		$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=trophy&action=modify&amp;typeid=' . $typeid);
		
		// Print the data
		print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
	}
	print_table_footer();
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$typeid = $vbulletin->input->clean_gpc('r', 'typeid', TYPE_UINT);
	$type = ($typeid ? VBACTIVITY::$cache['type']["$typeid"] : false);
	
	$typeinfo = $vbulletin->input->clean_gpc('r', 'type', TYPE_ARRAY);
	if (count($typeinfo))
	{
		// Override with saved form data
		$type = $typeinfo;
	}
	
	if (!is_array($type))
	{
		// Non-existing type
		$typeid = 0;
	}
	
	if ($typeid)
	{
		// Ensure this is correct
		$type['typename'] = ($type['typename'] == 'totalpoints' ? $type['typename'] : 'per' . $type['typename']);
		
		// Edit
		$type['trophyname'] = ($type['trophyname'] ? $type['trophyname'] : $vbphrase["dbtech_vbactivity_condition_{$type[typename]}"]);
	
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_trophy'], $type['trophyname'])));
		print_form_header('vbactivity', 'trophy');
		construct_hidden_code('action', 'update');
		construct_hidden_code('typeid', $typeid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_trophy'], $type['trophyname']));
	}
	else
	{
		// This shouldn't happen
		print_cp_message($vbphrase['dbtech_vbactivity_invalid_action']);
	}
	
	$trophyicons = array('' => $vbphrase['none']);
	$d = dir(DIR . '/images/icons/vbactivity/trophies');
	while (false !== ($file = $d->read()))
	{
		if ($file != '.' AND $file != '..' AND $file != 'index.html')
		{
			// Store the icon
			$trophyicons['trophies/' . $file] = $file;
		}
	}
	$d->close();
	
	// Sort the array as a string
	asort($trophyicons, SORT_STRING);
	
	print_input_row($vbphrase['dbtech_vbactivity_trophy_name'], 	'type[trophyname]',					$type['trophyname']);
	print_select_row($vbphrase['dbtech_vbactivity_trophy_icon'], 	'type[icon]',		$trophyicons, 	$type['icon']);
	//(rint_input_row($vbphrase['icon'], 							'icon', 		$type['icon']);
	
	print_submit_row($vbphrase['save'], false);
	VBACTIVITY::js('_admin');
	VBACTIVITY::js("vBActivity_Admin.image_preview('type\\\[icon\\\]', '{$vbulletin->options['bburl']}/images/icons/vbactivity/');", false);	
}


// #############################################################################
if ($_POST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'typeid' 	=> TYPE_UINT,
		'type' 		=> TYPE_ARRAY,
	));
	
		
	// set existing info if this is an update
	if (!$existing = VBACTIVITY::$cache['type']["{$vbulletin->GPC[typeid]}"])
	{
		// Couldn't find the type
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_type'], $vbulletin->GPC['typeid']);
	}	
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Type', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
			
	// type fields
	foreach ($vbulletin->GPC['type'] AS $key => $val)
	{
		if ($existing["$key"] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	// Edited
	$phrase = $vbphrase['dbtech_vbactivity_edited'];
			
	define('CP_REDIRECT', 'vbactivity.php?do=trophy');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_trophy'], $phrase);
}

print_cp_footer();