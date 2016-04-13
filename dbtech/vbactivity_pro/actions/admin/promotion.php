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

if (!VBACTIVITY::$permissions['promotion'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'promotion' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_promotion_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_from_usergroup'];
	$headings[] = $vbphrase['dbtech_vbactivity_to_usergroup'];
	$headings[] = $vbphrase['dbtech_vbactivity_criteria'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	
	if (count(VBACTIVITY::$cache['promotion']))
	{
		print_form_header('vbactivity', 'promotion');	
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_promotion_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_promotion_management_descr'], false, count($headings));	
		print_cells_row($headings, 0, 'thead');	
		
		foreach (VBACTIVITY::$cache['promotion'] as $promotionid => $promotion)
		{
			// Condition data
			$conditions = array();
			
			// Fetch all conditions
			$conditioninfo = VBACTIVITY_FILTER::filter(VBACTIVITY::$cache['conditionbridge'], 'feature', 'promotion');
			$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'featureid', $promotionid);
			foreach ($conditioninfo as $condition)
			{
				// Shorthand
				$condition 	= VBACTIVITY::$cache['condition']["$condition[conditionid]"];
				$typename 	= VBACTIVITY::$cache['type']["$condition[typeid]"]['typename'];
				
				// Add this condition
				$conditions[] = $vbphrase["dbtech_vbactivity_condition_{$typename}"] . ' ' . $condition['comparison'] . ' ' . $condition['value'] . ($condition['forumid'] ? ' (' . $vbulletin->forumcache[$condition['forumid']]['title'] . ')' : '');
			}
			
			// Table data
			$cell = array();
			$cell[] = $vbulletin->usergroupcache["$promotion[fromusergroupid]"]['title'];
			$cell[] = $vbulletin->usergroupcache["$promotion[tousergroupid]"]['title'];
			$cell[] = implode('<br />', $conditions);		
			$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=promotion&amp;action=modify&amp;promotionid=' . $promotionid);
			$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=promotion&amp;action=delete&amp;promotionid=' . $promotionid);
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
		}
		
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_promotion'], false, count($headings));
	}
	else
	{
		print_form_header('vbactivity', 'promotion');
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_promotion_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_no_promotions'], false, count($headings));
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_promotion'], false, count($headings));	
	}
	
	print_cp_footer();
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	require_once(DIR . '/dbtech/vbactivity/includes/functions.php');
	
	$promotionid = $vbulletin->input->clean_gpc('r', 'promotionid', TYPE_UINT);
	$promotion = ($promotionid ? VBACTIVITY::$cache['promotion'][$promotionid] : false);
	
	$promotioninfo = $vbulletin->input->clean_gpc('r', 'promotion', TYPE_ARRAY);
	if (!empty($promotioninfo))
	{
		// Override with saved form data
		$promotion = $promotioninfo;
	}
	
	// Get number of extra conditions
	$numconditions = $vbulletin->input->clean_gpc('r', 'extraconditions', TYPE_UINT);
	
	// Get conditions from URL
	$conditioninfo = $vbulletin->input->clean_gpc('r', 'condition', TYPE_ARRAY);
		
	$conditions = array();
	foreach (VBACTIVITY::$cache['condition'] as $conditionid => $condition)
	{
		// Shorthand
		$typename = VBACTIVITY::$cache['type']["$condition[typeid]"]['typename'];
		$typename = ($condition['type'] == 'points' ? 'per' . $typename  : $typename);
		
		// Add the phrased condition name to the array
		$conditions["$conditionid"] = $vbphrase["dbtech_vbactivity_condition_{$typename}"] . ' ' . $condition['comparison'] . ' ' . $condition['value'] . ($condition['forumid'] ? ' (' . $vbulletin->forumcache[$condition['forumid']]['title'] . ')' : '');
	}
	
	// Sort the array as a string
	asort($conditions, SORT_STRING);
	
	if (!count($conditions))
	{
		// Missing categories
		print_stop_message('dbtech_vbactivity_missing_x',
			$vbphrase['dbtech_vbactivity_condition'],
			$vbulletin->session->vars['sessionurl'],
			'criteria',
			'modify'
		);
	}
	
	$usergroups = array();
	foreach ($vbulletin->usergroupcache as $usergroupid => $usergroup)
	{
		if (in_array($usergroupid, array(1, 3, 4)))
		{
			// We can't promote to these usergroups
			continue;
		}
		
		// Add the usergroup
		$usergroups["$usergroupid"] = $usergroup['title'];
	}
	
	if (!is_array($promotion))
	{
		// Non-existing promotion
		$promotionid = 0;
	}
	
	if ($promotionid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_promotion'], $promotion['promotionid'])));
		print_form_header('vbactivity', 'promotion');
		construct_hidden_code('action', 'update');
		construct_hidden_code('promotionid', $promotionid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_promotion'], $promotion['promotionid']));
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_promotion']);
		print_form_header('vbactivity', 'promotion');	
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_promotion']);
	}
	
	print_select_row($vbphrase['dbtech_vbactivity_from_usergroup'], 'promotion[fromusergroupid]', 	$usergroups, $promotion['fromusergroupid']);
	print_select_row($vbphrase['dbtech_vbactivity_to_usergroup'], 	'promotion[tousergroupid]', 	$usergroups, $promotion['tousergroupid']);
	
	print_table_header($vbphrase['dbtech_vbactivity_promotion_criteria']);
	print_description_row(construct_phrase($vbphrase['dbtech_vbactivity_promotion_criteria_descr'], $vbulletin->session->vars['sessionurl']));
	
	if (!$numconditions AND !count($conditioninfo) AND !$promotionid)
	{
		// We need at least one condition field
		print_condition_row(0, $conditions, '', false);
	}
	
	if (count($conditioninfo))
	{
		foreach ($conditioninfo as $key => $condition)
		{
			// Print the condition
			print_condition_row($key, $conditions, $condition, ($key > 0 ? true : false));
		}
	}
	else if ($promotionid)
	{
		// We're editing an promotion, print out all the conditions
		$conditioninfo = VBACTIVITY_FILTER::filter(VBACTIVITY::$cache['conditionbridge'], 'feature', 'promotion');
		$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'featureid', $promotionid);
		
		if (!count($conditioninfo))
		{
			// We need at least one condition field
			print_condition_row(0, $conditions, '', false);		
		}
		else
		{
			$i = 0;
			foreach ($conditioninfo as $condition)
			{
				// Shorthand
				$condition = $condition['conditionid'];
				
				// Print the condition
				print_condition_row($key, $conditions, $condition, ($i > 0 ? true : false));
				
				$i++;
			}
		}
	}
	
	if (!count($conditioninfo))
	{
		// We need at least one condition field
		$conditioninfo[0] = 0;
	}
	
	if ($numconditions)
	{
		$i = count($conditioninfo);
		$j = ($i + $numconditions);
		
		// Needs moar new conditions
		for ($i; $i < $j; $i++)
		{
			print_condition_row($i, $conditions);
		}
	}
	print_input_row($vbphrase['dbtech_vbactivity_extra_conditions'], 'extraconditions', 0);
	
	print_submit_row(($promotionid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_promotion']), false);
}

// #############################################################################
if ($_REQUEST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'promotionid' 		=> TYPE_UINT,
		'promotion' 		=> TYPE_ARRAY,
		'condition' 		=> TYPE_ARRAY_UINT,
		'removecondition' 	=> TYPE_ARRAY_UINT,
		'extraconditions'	=> TYPE_UINT
	));
	
	if ($vbulletin->GPC['extraconditions'])
	{
		$conditions = array();
		$conditions[] = 'extraconditions=' . $vbulletin->GPC['extraconditions'];
		$conditions[] = 'promotion[fromusergroupid]=' . urlencode($vbulletin->GPC['promotion']['fromusergroupid']);
		$conditions[] = 'promotion[tousergroupid]=' . $vbulletin->GPC['promotion']['tousergroupid'];
		
		if ($vbulletin->GPC['promotionid'])
		{
			// We were editing
			$conditions[] = 'promotionid=' . $vbulletin->GPC['promotionid'];
		}
		
		$i = 0;	
		$added = array();
		foreach ($vbulletin->GPC['condition'] as $key => $conditionid)
		{
			if (!$vbulletin->GPC['removecondition']["$key"] AND !$added["$conditionid"])
			{
				$conditions[] = "condition[$i]=$conditionid";
				$added["$conditionid"] = true;
				$i++;
			}
		}
		
		// We need to go back. DO NOT ADD LINE BREAKS TO PRETTIFY THE CODE, FOR GODS SAKE
		define('CP_REDIRECT', 'vbactivity.php?do=promotion&action=modify&' . implode('&', $conditions));
		print_stop_message('dbtech_vbactivity_adding_conditions');
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Promotion', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['promotionid'])
	{
		if (!$existing = VBACTIVITY::$cache['promotion']["{$vbulletin->GPC[promotionid]}"])
		{
			// Couldn't find the promotion
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_promotion'], $vbulletin->GPC['promotionid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
		
		if ($vbulletin->GPC['promotion']['parentid'] == $vbulletin->GPC['promotionid'])
		{
			// Ensure we don't create loops in the time-space continuum
			$vbulletin->GPC['promotion']['parentid'] = 0;
		}
				
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_edited'];
	}
	else
	{
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_added'];
	}
	
	// promotion fields
	foreach ($vbulletin->GPC['promotion'] AS $key => $val)
	{
		if (!$vbulletin->GPC['promotionid'] OR $existing["$key"] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	define('CP_REDIRECT', 'vbactivity.php?do=promotion');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_promotion'], $phrase);
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'promotionid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_promotion']));
	print_delete_confirmation('dbtech_vbactivity_promotion', $vbulletin->GPC['promotionid'], 'vbactivity', 'promotion', 'dbtech_vbactivity_promotion', array('action' => 'kill'), '', 'promotionid');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'promotionid' 	=> TYPE_UINT,
		'kill' 		 	=> TYPE_BOOL
	));
	
	if (!$existing = VBACTIVITY::$cache['promotion']["{$vbulletin->GPC[promotionid]}"])
	{
		// Couldn't find the promotion
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_promotion'], $vbulletin->GPC['promotionid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Promotion', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=promotion');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_promotion'], $vbphrase['dbtech_vbactivity_deleted']);	
}

print_cp_footer();