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

if (!VBACTIVITY::$permissions['achievement'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'achievement' OR empty($_REQUEST['action']))
{
	$achievements_by_category 	= array();
	foreach ((array)VBACTIVITY::$cache['achievement'] as $achievementid => $achievement)
	{
		// Index by categoryid
		$achievements_by_category["$achievement[categoryid]"]["$achievementid"] = $achievement;
	}
	
	print_cp_header($vbphrase['dbtech_vbactivity_achievement_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['title'];
	$headings[] = $vbphrase['dbtech_vbactivity_parent'];
	$headings[] = $vbphrase['description'];
	$headings[] = $vbphrase['dbtech_vbactivity_criteria'];
	$headings[] = $vbphrase['display_order'];
	$headings[] = $vbphrase['dbtech_vbactivity_sticky'];
	$headings[] = $vbphrase['copy'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	if (count($achievements_by_category))
	{
		print_form_header('vbactivity', 'achievement');	
		construct_hidden_code('action', 'displayorder');
		print_table_header($vbphrase['dbtech_vbactivity_achievement_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_achievement_management_descr'], false, count($headings));	
		
		foreach ($achievements_by_category as $categoryid => $achievements)
		{
			print_description_row(VBACTIVITY::$cache['category']["$categoryid"]['title'], false, count($headings), 'optiontitle');
			print_description_row(VBACTIVITY::$cache['category']["$categoryid"]['description'], false, count($headings));	
			print_cells_row($headings, 0, 'thead');
			
			foreach ($achievements as $achievementid => $achievement)
			{
				// Condition data
				$conditions = array();
				
				// Fetch all conditions
				$conditioninfo = VBACTIVITY_FILTER::filter(VBACTIVITY::$cache['conditionbridge'], 'feature', 'achievement');
				$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'featureid', $achievementid);
				foreach ($conditioninfo as $condition)
				{
					// Shorthand
					$condition 	= VBACTIVITY::$cache['condition']["$condition[conditionid]"];
					$typename 	= VBACTIVITY::$cache['type']["$condition[typeid]"]['typename'];
					$typename	= ($condition['type'] == 'points' ? 'per' . $typename  : $typename);
					
					// Add this condition
					$conditions[] = $vbphrase["dbtech_vbactivity_condition_{$typename}"] . ' ' . $condition['comparison'] . ' ' . $condition['value'] . ($condition['forumid'] ? ' (' . $vbulletin->forumcache[$condition['forumid']]['title'] . ')' : '');
				}
				
				// Shorthand
				$parent = VBACTIVITY::$cache['achievement']["$achievement[parentid]"];
				$parent['title'] = ($parent['title'] ? $parent['title'] : $vbphrase['dbtech_vbactivity_no_parent']);
				
				// Table data
				$cell = array();
				$cell[] = ($achievement['icon'] ? '<img src="../images/icons/vbactivity/' . $achievement['icon'] . '" alt="' . $achievement['title'] . '" /> ' : '') . $achievement['title'];
				$cell[] = ($parent['icon'] ? '<img src="../images/icons/vbactivity/' . $parent['icon'] . '" alt="' . $parent['title'] . '" /> ' : '') . $parent['title'];
				$cell[] = $achievement['description'];
				$cell[] = implode('<br />', $conditions);				
				$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$achievementid]\" value=\"$achievement[displayorder]\" tabindex=\"1\" size=\"3\"" . iif($vbulletin->debug, " title=\"name=&quot;order[$tabid]&quot;\"") . " />";
				$cell[] = ($achievement['sticky'] ? $vbphrase['yes'] : $vbphrase['no']);
				$cell[] = construct_link_code($vbphrase['copy'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=achievement&amp;action=modify&amp;copyachievementid=' . $achievementid);
				$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=achievement&amp;action=modify&amp;achievementid=' . $achievementid);
				$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=achievement&amp;action=delete&amp;achievementid=' . $achievementid);
				
				// Print the data
				print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
			}
		}
		
		print_submit_row($vbphrase['save_display_order'], false, count($headings), false, "<input type=\"button\" id=\"addnew\" class=\"button\" value=\"" . str_pad($vbphrase['dbtech_vbactivity_add_new_achievement'], 8, ' ', STR_PAD_BOTH) . "\" tabindex=\"1\" onclick=\"window.location = 'vbactivity.php?do=achievement&amp;action=modify'\" />");	
	}
	else
	{
		print_form_header('vbactivity', 'achievement');
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_achievement_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_no_achievements'], false, count($headings));
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_achievement'], false, count($headings));	
	}
	
	print_cp_footer();
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	require_once(DIR . '/dbtech/vbactivity/includes/functions.php');
	
	$achievementid = $vbulletin->input->clean_gpc('r', 'achievementid', TYPE_UINT);
	$achievement = ($achievementid ? VBACTIVITY::$cache['achievement'][$achievementid] : false);
	
	$achievementinfo = $vbulletin->input->clean_gpc('r', 'achievement', TYPE_ARRAY);
	if (!empty($achievementinfo))
	{
		// Override with saved form data
		$achievement = $achievementinfo;
	}
	
	// Get number of extra conditions
	$numconditions = $vbulletin->input->clean_gpc('r', 'extraconditions', TYPE_UINT);
	
	// Get conditions from URL
	$conditioninfo = $vbulletin->input->clean_gpc('r', 'condition', TYPE_ARRAY);
	
	$achievements = array();
	foreach (VBACTIVITY::$cache['achievement'] as $achievement_id => $achievementinfo)
	{
		// Add the phrased achievement name to the array
		$achievements[$achievement_id] = $achievementinfo['title'];
	}
	
	$categorys = array();
	foreach (VBACTIVITY::$cache['category'] as $categoryid => $category)
	{
		// Add the phrased category name to the array
		$categorys[$categoryid] = $category['title'];
	}
	
	$achievements = array();
	$achievements[0] = $vbphrase['dbtech_vbactivity_no_parent'];
	foreach (VBACTIVITY::$cache['achievement'] as $achievement_id => $achievementinfo)
	{
		// Add the phrased category name to the array
		$achievements[$achievement_id] = $achievementinfo['title'];
	}
	
	// Sort the array as a string
	asort($categorys, SORT_STRING);
	
	if (!count($categorys))
	{
		// Missing categories
		print_stop_message('dbtech_vbactivity_missing_x',
			$vbphrase['dbtech_vbactivity_category'],
			$vbulletin->session->vars['sessionurl'],
			'category',
			'modify'
		);
	}
	
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
	
	if (!is_array($achievement))
	{
		// Non-existing achievement
		$achievementid = 0;
	}
	
	if ($achievementid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_achievement'], $achievement['title'])));
		print_form_header('vbactivity', 'achievement');
		construct_hidden_code('action', 'update');
		construct_hidden_code('achievementid', $achievementid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_achievement'], $achievement['title']));

		if (!$vbulletin->debug)
		{
			$vbphrase['title']  		= $vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_achievement_{$achievementid}_title]") . '</dfn>';
			$vbphrase['description'] 	= $vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_achievement_{$achievementid}_description]") . '</dfn>';
		}
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_achievement']);

		if (count($achievements) > 1)
		{
			print_table_start();
			print_description_row('
				<dfn>' . $vbphrase['dbtech_vbactivity_copy_from'] . ': <select id="copysettings">' . construct_select_options($achievements) . '</select>
				<input type="submit" value="' . $vbphrase['go'] . '" onclick="window.location.href = \'vbactivity.php?do=achievement&amp;action=modify&amp;copyachievementid=\' + fetch_object(\'copysettings\').options[fetch_object(\'copysettings\').options.selectedIndex].value;" />
				</dfn>
			');
			print_table_footer();
		}

		print_form_header('vbactivity', 'achievement');	
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_achievement']);

		$copyachievementid = $vbulletin->input->clean_gpc('r', 'copyachievementid', TYPE_UINT);
		$copyachievement = ($copyachievementid ? VBACTIVITY::$cache['achievement'][$copyachievementid] : false);
		
		if ($copyachievement)
		{
			// We're copying
			$achievement = $copyachievement;
		}
		else
		{
			// Start with the default data
			$achievement = $defaults;
		}		
	}
	
	$achievicons = array('' => $vbphrase['none']);
	$d = dir(DIR . '/images/icons/vbactivity/achievements');
	while (false !== ($file = $d->read()))
	{
		if ($file != '.' AND $file != '..' AND $file != 'index.html')
		{
			// Store the icon
			$achievicons['achievements/' . $file] = $file;
		}
	}
	$d->close();
	
	// Sort the array as a string
	asort($achievicons, SORT_STRING);
	
	print_input_row($vbphrase['title'], 								'achievement[title]', 						$achievement['title']);
	print_select_row($vbphrase['dbtech_vbactivity_category'], 			'achievement[categoryid]', 	$categorys, 	$achievement['categoryid']);
	print_select_row($vbphrase['dbtech_vbactivity_parent'], 			'achievement[parentid]', 	$achievements, 	$achievement['parentid']);
	print_textarea_row($vbphrase['description'], 						'achievement[description]', 				$achievement['description']);
	print_input_row($vbphrase['dbtech_vbactivity_display_order'], 		'achievement[displayorder]', 				(isset($achievement['displayorder']) ? $achievement['displayorder'] : 10));
	print_select_row($vbphrase['dbtech_vbactivity_achievement_icon'], 	'achievement[icon]',		$achievicons,	$achievement['icon']);
	print_yes_no_row($vbphrase['dbtech_vbactivity_sticky_achiev'],		'achievement[sticky]', 						$achievement['sticky']);
	
	print_table_header($vbphrase['dbtech_vbactivity_achievement_criteria']);
	print_description_row(construct_phrase($vbphrase['dbtech_vbactivity_achievement_criteria_descr'], $vbulletin->session->vars['sessionurl']));
	
	if (!$numconditions AND !count($conditioninfo) AND !$achievementid)
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
	else if ($achievementid)
	{
		// We're editing an achievement, print out all the conditions
		$conditioninfo = VBACTIVITY_FILTER::filter(VBACTIVITY::$cache['conditionbridge'], 'feature', 'achievement');
		$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'featureid', $achievementid);
		
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
	
	print_submit_row(($achievementid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_achievement']), false);
	VBACTIVITY::js('_admin');
	VBACTIVITY::js("vBActivity_Admin.image_preview('achievement\\\[icon\\\]', '{$vbulletin->options['bburl']}/images/icons/vbactivity/');", false);
}

// #############################################################################
if ($_REQUEST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'achievementid' 	=> TYPE_UINT,
		'achievement' 		=> TYPE_ARRAY,
		'condition' 		=> TYPE_ARRAY_UINT,
		'removecondition' 	=> TYPE_ARRAY_UINT,
		'extraconditions'	=> TYPE_UINT
	));
	
	if ($vbulletin->GPC['extraconditions'])
	{
		$conditions = array();
		$conditions[] = 'extraconditions=' . $vbulletin->GPC['extraconditions'];
		$conditions[] = 'achievement[title]=' . urlencode($vbulletin->GPC['achievement']['title']);
		$conditions[] = 'achievement[categoryid]=' . $vbulletin->GPC['achievement']['categoryid'];
		$conditions[] = 'achievement[parentid]=' . $vbulletin->GPC['achievement']['parentid'];
		$conditions[] = 'achievement[description]=' . urlencode($vbulletin->GPC['achievement']['description']);
		$conditions[] = 'achievement[displayorder]=' . $vbulletin->GPC['achievement']['displayorder'];
		$conditions[] = 'achievement[icon]=' . urlencode($vbulletin->GPC['achievement']['icon']);
		$conditions[] = 'achievement[sticky]=' . urlencode($vbulletin->GPC['achievement']['sticky']);
		
		if ($vbulletin->GPC['achievementid'])
		{
			// We were editing
			$conditions[] = 'achievementid=' . $vbulletin->GPC['achievementid'];
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
		define('CP_REDIRECT', 'vbactivity.php?do=achievement&action=modify&' . implode('&', $conditions));
		print_stop_message('dbtech_vbactivity_adding_conditions');
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Achievement', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['achievementid'])
	{
		if (!$existing = VBACTIVITY::$cache['achievement']["{$vbulletin->GPC[achievementid]}"])
		{
			// Couldn't find the achievement
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_achievement'], $vbulletin->GPC['achievementid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
		
		if ($vbulletin->GPC['achievement']['parentid'] == $vbulletin->GPC['achievementid'])
		{
			// Ensure we don't create loops in the time-space continuum
			$vbulletin->GPC['achievement']['parentid'] = 0;
		}
				
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_edited'];
	}
	else
	{
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_added'];
	}
	
	// achievement fields
	foreach ($vbulletin->GPC['achievement'] AS $key => $val)
	{
		if (!$vbulletin->GPC['achievementid'] OR $existing["$key"] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	define('CP_REDIRECT', 'vbactivity.php?do=achievement');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_achievement'], $phrase);
}

// #############################################################################
if ($_POST['action'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));
	
	if (is_array($vbulletin->GPC['order']))
	{
		foreach ($vbulletin->GPC['order'] as $achievementid => $displayorder)
		{
			if (!$existing = VBACTIVITY::$cache['achievement']["$achievementid"])
			{
				// Couldn't find the achievement
				continue;
			}
			
			if ($existing['displayorder'] == $displayorder)
			{
				// No change
				continue;
			}
			
			// init data manager
			$dm =& VBACTIVITY::initDataManager('Achievement', $vbulletin, ERRTYPE_CP);
				$dm->set_existing($existing);
				$dm->set('displayorder', $displayorder);
			$dm->save();
			unset($dm);	
		}
	}
	
	define('CP_REDIRECT', 'vbactivity.php?do=achievement');
	print_stop_message('saved_display_order_successfully');	
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'achievementid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_achievement']));
	print_delete_confirmation('dbtech_vbactivity_achievement', $vbulletin->GPC['achievementid'], 'vbactivity', 'achievement', 'dbtech_vbactivity_achievement', array('action' => 'kill'), '', 'title');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'achievementid' => TYPE_UINT,
		'kill' 		 => TYPE_BOOL
	));
	
	if (!$existing = VBACTIVITY::$cache['achievement']["{$vbulletin->GPC[achievementid]}"])
	{
		// Couldn't find the achievement
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_achievement'], $vbulletin->GPC['achievementid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Achievement', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=achievement');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_achievement'], $vbphrase['dbtech_vbactivity_deleted']);	
}

print_cp_footer();