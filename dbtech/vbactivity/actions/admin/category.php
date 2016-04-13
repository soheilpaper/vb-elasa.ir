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

if (!VBACTIVITY::$permissions['category'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'category' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_category_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['title'];
	$headings[] = $vbphrase['description'];
	$headings[] = $vbphrase['display_order'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	
	if (count(VBACTIVITY::$cache['category']))
	{
		print_form_header('vbactivity', 'category');	
		construct_hidden_code('action', 'displayorder');
		print_table_header($vbphrase['dbtech_vbactivity_category_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_category_management_descr'], false, count($headings));	
		print_cells_row($headings, 0, 'thead');
		
		foreach ((array)VBACTIVITY::$cache['category'] as $categoryid => $category)
		{
			// Table data
			$cell = array();
			$cell[] = $category['title'];
			$cell[] = $category['description'];
			$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$categoryid]\" value=\"$category[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";
			$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=category&amp;action=modify&amp;categoryid=' . $categoryid);
			$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=category&amp;action=delete&amp;categoryid=' . $categoryid);
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
		}
		print_submit_row($vbphrase['save_display_order'], false, count($headings), false, "<input type=\"button\" id=\"addnew\" class=\"button\" value=\"" . str_pad($vbphrase['dbtech_vbactivity_add_new_category'], 8, ' ', STR_PAD_BOTH) . "\" tabindex=\"1\" onclick=\"window.location = 'vbactivity.php?do=category&amp;action=modify'\" />");	
	}
	else
	{
		print_form_header('vbactivity', 'category');	
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_category_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_no_categories'], false, count($headings));
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_category'], false, count($headings));	
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$categoryid = $vbulletin->input->clean_gpc('r', 'categoryid', TYPE_UINT);
	$category = ($categoryid ? VBACTIVITY::$cache['category']["$categoryid"] : false);
	
	if (!is_array($category))
	{
		// Non-existing category
		$categoryid = 0;
	}
	
	if ($categoryid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_category'], $category['title'])));
		print_form_header('vbactivity', 'category');
		construct_hidden_code('action', 'update');
		construct_hidden_code('categoryid', $categoryid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_category'], $category['title']));

		if (!$vbulletin->debug)
		{
			$vbphrase['title']  		= $vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_category_{$categoryid}_title]") . '</dfn>';
			$vbphrase['description'] 	= $vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_category_{$categoryid}_description]") . '</dfn>';
		}
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_category']);
		print_form_header('vbactivity', 'category');
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_category']);
	}
	
	print_input_row($vbphrase['title'], 			'category[title]', 			$category['title']);
	print_textarea_row($vbphrase['description'], 	'category[description]', 	$category['description']);
	print_input_row($vbphrase['display_order'], 	'category[displayorder]', 	$category['displayorder']);
	
	print_submit_row(($categoryid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_category']));	
}

// #############################################################################
if ($_REQUEST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'categoryid' 	=> TYPE_UINT,
		'category' 		=> TYPE_ARRAY,
	));
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Category', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['categoryid'])
	{
		if (!$existing = VBACTIVITY::$cache['category']["{$vbulletin->GPC[categoryid]}"])
		{
			// Couldn't find the category
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_category'], $vbulletin->GPC['categoryid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
				
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_edited'];
	}
	else
	{
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_added'];
	}
	
	// category fields
	foreach ($vbulletin->GPC['category'] AS $key => $val)
	{
		if (!$vbulletin->GPC['categoryid'] OR $existing["$key"] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	define('CP_REDIRECT', 'vbactivity.php?do=category');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_category'], $phrase);
}

// #############################################################################
if ($_POST['action'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));
	
	if (is_array($vbulletin->GPC['order']))
	{
		foreach ($vbulletin->GPC['order'] as $categoryid => $displayorder)
		{
			if (!$existing = VBACTIVITY::$cache['category']["$categoryid"])
			{
				// Couldn't find the category
				continue;
			}
			
			if ($existing['displayorder'] == $displayorder)
			{
				// No change
				continue;
			}
			
			// init data manager
			$dm =& VBACTIVITY::initDataManager('Category', $vbulletin, ERRTYPE_CP);
				$dm->set_existing($existing);
				$dm->set('displayorder', $displayorder);
			$dm->save();
			unset($dm);	
		}
	}
	
	define('CP_REDIRECT', 'vbactivity.php?do=category');
	print_stop_message('saved_display_order_successfully');	
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'categoryid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_category']));
	print_delete_confirmation('dbtech_vbactivity_category', $vbulletin->GPC['categoryid'], 'vbactivity', 'category', 'dbtech_vbactivity_category', array('action' => 'kill'), '', 'title');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_gpc('r', 'categoryid', TYPE_UINT);
	
	if (!$existing = VBACTIVITY::$cache['category']["{$vbulletin->GPC[categoryid]}"])
	{
		// Couldn't find the category
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_category'], $vbulletin->GPC['categoryid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Category', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=category');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_category'], $vbphrase['dbtech_vbactivity_deleted']);	
}

print_cp_footer();