<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2007-2009 Fillip Hannisdal AKA Revan/NeoRevan/Belazor # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

// #############################################################################
if ($_REQUEST['action'] == 'contenttype' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_livewall_contenttype_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['title'];
	$headings[] = $vbphrase['active'];
	
	// Hook goes here
	
	$headings[] = $vbphrase['edit'];
	if ($vbulletin->debug) $headings[] = $vbphrase['delete'];
	
	
	if (count(LIVEWALL::$cache['contenttype']))
	{
		print_form_header('livewall', 'contenttype');	
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_livewall_contenttype_management'], count($headings));
		print_description_row($vbphrase['dbtech_livewall_contenttype_management_descr'], false, count($headings));	
		print_cells_row($headings, 0, 'thead');
		
		foreach ((array)LIVEWALL::$cache['contenttype'] as $contenttypeid => $contenttype)
		{
			if (!$contenttype['enabled'])
			{
				// Pro only and we're in Lite
				continue;
			}
			
			// Table data
			$cell = array();
			$cell[] = $contenttype['title'];
			$cell[] = ($contenttype['active'] ? $vbphrase['yes'] : '<span class="col-i"><strong>' . $vbphrase['no'] . '</strong></span>');
			
			// Hook goes here
			
			$cell[] = construct_link_code($vbphrase['edit'], 'livewall.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contenttype&amp;action=modify&amp;contenttypeid=' . $contenttypeid);
			if ($vbulletin->debug) $cell[] = construct_link_code($vbphrase['delete'], 'livewall.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contenttype&amp;action=delete&amp;contenttypeid=' . $contenttypeid);
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
		}
		if ($vbulletin->debug)
		{
			print_submit_row($vbphrase['dbtech_livewall_add_new_contenttype'], false, count($headings));
		}
		else
		{
			print_table_footer();
		}
	}
	else
	{
		print_form_header('livewall', 'contenttype');	
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_livewall_contenttype_management'], count($headings));
		print_description_row($vbphrase['dbtech_livewall_no_contenttypes'], false, count($headings));
		if ($vbulletin->debug)
		{
			print_submit_row($vbphrase['dbtech_livewall_add_new_contenttype'], false, count($headings));
		}
		else
		{
			print_table_footer();
		}
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$contenttypeid = $vbulletin->input->clean_gpc('r', 'contenttypeid', TYPE_STR);
	$contenttype = ($contenttypeid ? LIVEWALL::$cache['contenttype'][$contenttypeid] : false);
	
	if (!is_array($contenttype))
	{
		// Non-existing contenttype
		$contenttypeid = 0;
	}
	
	$defaults = array(
		'contenttypeid'		=> 'posts',
		'title' 			=> 'Posts',
		'active' 			=> 1,
		'filename' 			=> 'dbtech/livewall/contenttypes/posts.php',
		'preview' 			=> 300,
		'preview_sidebar' 	=> 140,
	);
	
	if ($contenttypeid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_livewall_editing_x_y'], $vbphrase['dbtech_livewall_contenttype'], $contenttype['title'])));
		print_form_header('livewall', 'contenttype');
		construct_hidden_code('action', 'update');
		construct_hidden_code('contenttypeid', $contenttypeid);
		print_table_header(construct_phrase($vbphrase['dbtech_livewall_editing_x_y'], $vbphrase['dbtech_livewall_contenttype'], $contenttype['title']));
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_livewall_add_new_contenttype']);
		print_form_header('livewall', 'contenttype');
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_livewall_add_new_contenttype']);
		
		$contenttype = $defaults;
	}
	
	if ($contenttypeid)
	{
		// Load the contenttype object
		$contentTypeObj = LIVEWALL::initContentType($contenttype);
	}
	
	if ($vbulletin->debug)
	{
		print_input_row($vbphrase['varname'], 							'contenttype[contenttypeid]',	$contenttype['contenttypeid']);
	}
	else
	{
		print_label_row($vbphrase['varname'], 															$contenttype['contenttypeid']);
	}
	print_input_row($vbphrase['title'], 								'contenttype[title]', 			$contenttype['title']);
	print_yes_no_row($vbphrase['active'],								'contenttype[active]',			$contenttype['active']);
	if ($vbulletin->debug)
	{
		print_input_row($vbphrase['filename'], 							'contenttype[filename]',		$contenttype['filename']);
	}
	print_input_row($vbphrase['dbtech_livewall_preview_descr'], 		'contenttype[preview]', 		$contenttype['preview']);
	print_input_row($vbphrase['dbtech_livewall_preview_sidebar_descr'], 'contenttype[preview_sidebar]', $contenttype['preview_sidebar']);
	
	if (method_exists($contentTypeObj, 'printAdminForm'))
	{
		// Content type info
		print_description_row($vbphrase['dbtech_livewall_contenttype_fields'], false, 2, 'optiontitle');			
		$contentTypeObj->printAdminForm($contenttype['code']);
	}
	else if (!$contenttypeid AND $vbulletin->debug)
	{
		print_description_row($vbphrase['dbtech_livewall_contenttype_fields'], false, 2, 'optiontitle');
		print_description_row($vbphrase['dbtech_livewall_contenttype_fields_descr']);
	}
	print_table_break();
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['usergroup'];
	$headings[] = $vbphrase['dbtech_livewall_noaccess'];
	$headings[] = $vbphrase['dbtech_livewall_excluded'];
	
	$cells = array();
	$cells[] = 'noaccess';
	$cells[] = 'excluded';
	
	print_table_header($vbphrase['dbtech_livewall_permissions'], count($headings));
	print_cells_row($headings, 0, 'thead');
	foreach ($vbulletin->usergroupcache as $usergroupid => $usergroup)
	{
		// Table data
		$cell = array();
		$cell[] = $usergroup['title'];
		foreach ($cells as $permtitle)
		{
			$cell[] = '<center>
				<input type="hidden" name="contenttype[permissions][' . $usergroupid . '][' . $permtitle . ']" value="0" />
				<input type="checkbox" name="contenttype[permissions][' . $usergroupid . '][' . $permtitle . ']" value="1"' . ($contenttype['permissions'][$usergroupid][$permtitle] ? ' checked="checked"' : '') . ($vbulletin->debug ? ' title="name=&quot;contenttype[permissions][' . $usergroupid . '][' . $permtitle . ']&quot;"' : '') . '/>
			</center>';
		}
		
		// Print the data
		print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
	}	
	print_submit_row(($contenttypeid ? $vbphrase['save'] : $vbphrase['dbtech_livewall_add_new_contenttype']), false, count($headings));	
}

// #############################################################################
if ($_REQUEST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'contenttypeid' 	=> TYPE_STR,
		'contenttype' 		=> TYPE_ARRAY,
	));
	
	// init data manager
	$dm =& LIVEWALL::initDataManager('Contenttype', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['contenttypeid'])
	{
		if (!$existing = LIVEWALL::$cache['contenttype'][$vbulletin->GPC['contenttypeid']])
		{
			// Couldn't find the contenttype
			print_stop_message('dbtech_livewall_invalid_x', $vbphrase['dbtech_livewall_contenttype'], $vbulletin->GPC['contenttypeid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
				
		// Added
		$phrase = $vbphrase['dbtech_livewall_edited'];
	}
	else
	{
		// Added
		$phrase = $vbphrase['dbtech_livewall_added'];
	}
	
	// contenttype fields
	foreach ($vbulletin->GPC['contenttype'] AS $key => $val)
	{
		if (!$vbulletin->GPC['contenttypeid'] OR $existing[$key] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	define('CP_REDIRECT', 'livewall.php?do=contenttype');
	print_stop_message('dbtech_livewall_x_y', $vbphrase['dbtech_livewall_contenttype'], $phrase);
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'contenttypeid', TYPE_STR);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_livewall_delete_x'], $vbphrase['dbtech_livewall_contenttype']));
	print_delete_confirmation('dbtech_livewall_contenttype', $vbulletin->GPC['contenttypeid'], 'livewall', 'contenttype', 'dbtech_livewall_contenttype', array('action' => 'kill'), '', 'title');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_gpc('r', 'contenttypeid', TYPE_STR);
	
	if (!$existing = LIVEWALL::$cache['contenttype'][$vbulletin->GPC['contenttypeid']])
	{
		// Couldn't find the contenttype
		print_stop_message('dbtech_livewall_invalid_x', $vbphrase['dbtech_livewall_contenttype'], $vbulletin->GPC['contenttypeid']);
	}
	
	// init data manager
	$dm =& LIVEWALL::initDataManager('Contenttype', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'livewall.php?do=contenttype');
	print_stop_message('dbtech_livewall_x_y', $vbphrase['dbtech_livewall_contenttype'], $vbphrase['dbtech_livewall_deleted']);	
}

print_cp_footer();

/*=======================================================================*\
|| ##################################################################### ||
|| # Created: 17:29, Sat Dec 27th 2008                                 # ||
|| # SVN: $RCSfile: contenttype.php,v $ - $Revision: $WCREV$ $
|| ##################################################################### ||
\*=======================================================================*/
?>