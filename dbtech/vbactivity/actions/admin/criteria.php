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

if (!VBACTIVITY::$permissions['criteria'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'criteria' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_condition_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_criteria'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	
	if (count(VBACTIVITY::$cache['condition']))
	{
		print_form_header('vbactivity', 'criteria');
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_condition_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_condition_management_descr'], false, count($headings));	
		print_cells_row($headings, 0, 'thead');
		
		foreach (VBACTIVITY::$cache['condition'] as $conditionid => $condition)
		{
			// Shorthand
			$typename = VBACTIVITY::$cache['type']["$condition[typeid]"]['typename'];
			$typename = ($condition['type'] == 'points' ? 'per' . $typename  : $typename);
			
			// Table data
			$cell = array();
			$cell[] = $vbphrase["dbtech_vbactivity_condition_{$typename}"] . ' ' . $condition['comparison'] . ' ' . $condition['value'] . ($condition['forumid'] ? ' (' . $vbulletin->forumcache[$condition['forumid']]['title'] . ')' : '');
			$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=criteria&amp;action=modify&amp;conditionid=' . $conditionid);
			$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=criteria&amp;action=delete&amp;conditionid=' . $conditionid);
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
		}
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_condition'], false, count($headings));	
	}
	else
	{
		print_form_header('vbactivity', 'criteria');
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_condition_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_no_conditions'], false, count($headings));
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_condition'], false, count($headings));	
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$conditionid = $vbulletin->input->clean_gpc('r', 'conditionid', TYPE_UINT);
	$condition = ($conditionid ? VBACTIVITY::$cache['condition']["$conditionid"] : false);
	
	if (!is_array($condition))
	{
		// Non-existingcondition condition
		$conditionid = 0;
	}
	
	$types = array();
	foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
	{
		if (!$type['active'])
		{
			// Unsupported
			continue;
		}
		
		// Add the phrased type name to the array
		$types["$typeid"] = ($vbphrase["dbtech_vbactivity_condition_{$type[typename]}"] ? $vbphrase["dbtech_vbactivity_condition_{$type[typename]}"] : "dbtech_vbactivity_condition_{$type[typename]}");
	}
	
	// Sort the array as a string
	asort($types, SORT_STRING);
	
	$types2 = array(
		'value' 	=> $vbphrase['dbtech_vbactivity_value'],
		'points' 	=> $vbphrase['dbtech_vbactivity_points']
	);
	
	$comparisons = array(
		'<' 	=> '<',
		'<=' 	=> '<=',
		'='		=> '=',
		'>='	=> '>=',
		'>'		=> '>'
	);
	if ($conditionid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_condition'], $condition['conditionid'])));
		print_form_header('vbactivity', 'criteria');
		construct_hidden_code('action', 'update');
		construct_hidden_code('conditionid', $conditionid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_condition'], $condition['conditionid']));
		
		print_select_row($vbphrase['dbtech_vbactivity_field'], 			'condition[typeid]', 		$types, 		$condition['typeid']);
		print_select_row($vbphrase['dbtech_vbactivity_type'], 			'condition[type]', 			$types2, 		$condition['type']);
		print_select_row($vbphrase['dbtech_vbactivity_comparison'], 	'condition[comparison]', 	$comparisons, 	$condition['comparison']);
		print_input_row($vbphrase['dbtech_vbactivity_value'],			'condition[value]', 						$condition['value']);
		/*DBTECH_PRO_START*/
		print_forum_chooser($vbphrase['dbtech_vbactivity_limitforum'], 	'condition[forumid]', 						$condition['forumid'], null, true);
		/*DBTECH_PRO_END*/
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_condition']);
		print_form_header('vbactivity', 'criteria');
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_condition']);
		
		for ($i = 0; $i <= 50; $i++)
		{
			print_description_row($vbphrase['dbtech_vbactivity_condition'], false, 2, 'optiontitle');
			print_select_row($vbphrase['dbtech_vbactivity_field'], 			'condition[' . $i . '][typeid]', 		$types, 		$condition['typeid']);
			print_select_row($vbphrase['dbtech_vbactivity_type'], 			'condition[' . $i . '][type]', 			$types2, 		$condition['type']);
			print_select_row($vbphrase['dbtech_vbactivity_comparison'], 	'condition[' . $i . '][comparison]', 	$comparisons, 	$condition['comparison']);
			print_input_row($vbphrase['dbtech_vbactivity_value'],			'condition[' . $i . '][value]', 						$condition['value']);
			/*DBTECH_PRO_START*/
			print_forum_chooser($vbphrase['dbtech_vbactivity_limitforum'], 	'condition[' . $i . '][forumid]', 						$condition['forumid'], null, true);
			/*DBTECH_PRO_END*/
		}
	}
	
	print_submit_row(($conditionid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_condition']));
}

// #############################################################################
if ($_REQUEST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'conditionid' 	=> TYPE_UINT,
		'condition' 	=> TYPE_ARRAY,
	));
	
	// set existing info if this is an update
	if ($vbulletin->GPC['conditionid'])
	{
		// init data manager
		$dm =& VBACTIVITY::initDataManager('Condition', $vbulletin, ERRTYPE_CP);
			
		if (!$existing = VBACTIVITY::$cache['condition']["{$vbulletin->GPC[conditionid]}"])
		{
			// Couldn't find the condition
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_condition'], $vbulletin->GPC['conditionid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
		
		// condition fields
		foreach ($vbulletin->GPC['condition'] AS $key => $val)
		{
			if ($existing["$key"] != $val)
			{
				// Only set changed values
				$dm->set($key, $val);
			}
		}
		
		// Save! Hopefully.
		$dm->save();		
				
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_edited'];
	}
	else
	{
		// condition fields
		foreach ($vbulletin->GPC['condition'] AS $condition)
		{
			if (strlen($condition['value']) == 0)
			{
				// We didn't set a value
				continue;
			}
			
			// init data manager
			$dm =& VBACTIVITY::initDataManager('Condition', $vbulletin, ERRTYPE_CP);
			
			foreach ($condition AS $key => $val)
			{
				// Only set changed values
				$dm->set($key, $val);
			}
			
			// Save! Hopefully.
			$dm->save();
			unset($dm);
		}
				
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_added'];
	}
		
	define('CP_REDIRECT', 'vbactivity.php?do=criteria');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_condition'], $phrase);
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'conditionid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_condition']));
	print_delete_confirmation('dbtech_vbactivity_condition', $vbulletin->GPC['conditionid'], 'vbactivity', 'criteria', 'dbtech_vbactivity_condition', array('action' => 'kill'), '', 'conditionid');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'conditionid' => TYPE_UINT,
		'kill' 		 => TYPE_BOOL
	));
	
	if (!$existing = VBACTIVITY::$cache['condition']["{$vbulletin->GPC[conditionid]}"])
	{
		// Couldn't find the condition
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_condition'], $vbulletin->GPC['conditionid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Condition', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=criteria');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_condition'], $vbphrase['dbtech_vbactivity_deleted']);	
}

print_cp_footer();