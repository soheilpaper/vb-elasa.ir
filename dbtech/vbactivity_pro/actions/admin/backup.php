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

if (!VBACTIVITY::$permissions['backup'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'backup' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_vbactivity_backup_management']);
	
	$backups = array();
	$backups_q = $db->query_read_slave("SELECT backupid, title, dateline FROM " . TABLE_PREFIX . "dbtech_vbactivity_backup ORDER BY dateline DESC");
	while ($backups_r = $db->fetch_array($backups_q))
	{
		// Set the backup array
		$backups["$backups_r[backupid]"] = $backups_r;
	}
	$db->free_result($backups_q);
	unset($backups_r);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['title'];
	$headings[] = $vbphrase['dbtech_vbactivity_date_generated'];
	$headings[] = $vbphrase['dbtech_vbactivity_load'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	if (count($backups))
	{
		print_form_header('vbactivity', 'backup');
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_backup_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_backup_management_descr'], false, count($headings));	
		print_cells_row($headings, 0, 'thead');
		
		foreach ($backups as $backupid => $backup)
		{
			// Table data
			$cell = array();
			$cell[] = $backup['title'];
			$cell[] = vbdate($vbulletin->options['dateformat'], $backup['dateline']) . ' ' . vbdate($vbulletin->options['timeformat'], $backup['dateline']);
			$cell[] = construct_link_code($vbphrase['dbtech_vbactivity_load'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=backup&amp;action=load&amp;backupid=' . $backupid);
			$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=backup&amp;action=modify&amp;backupid=' . $backupid);
			$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=backup&amp;action=delete&amp;backupid=' . $backupid);
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
		}
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_backup'], false, count($headings));	
	}
	else
	{
		print_form_header('vbactivity', 'backup');
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_backup_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_no_backups'], false, count($headings));
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_backup'], false, count($headings));	
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$backupid = $vbulletin->input->clean_gpc('r', 'backupid', TYPE_UINT);
	$backup = $db->query_first_slave("SELECT backupid, title FROM " . TABLE_PREFIX . "dbtech_vbactivity_backup WHERE backupid = " . $db->sql_prepare($backupid));
	
	if (!is_array($backup))
	{
		// Non-existingbackup backup
		$backupid = 0;
	}
	
	if ($backupid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_backup'], $backup['title'])));
		print_form_header('vbactivity', 'backup');
		construct_hidden_code('action', 'update');
		construct_hidden_code('backupid', $backupid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_backup'], $backup['title']));
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_backup']);
		print_form_header('vbactivity', 'backup');
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_backup']);
	}
	
	print_input_row($vbphrase['title'], 'backup[title]', $backup['title']);
	print_submit_row(($backupid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_backup']));
}

// #############################################################################
if ($_POST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'backupid' 	=> TYPE_UINT,
		'backup' 	=> TYPE_ARRAY,
	));
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Backup', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['backupid'])
	{
		if (!$existing = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_backup WHERE backupid = " . $db->sql_prepare($vbulletin->GPC['backupid'])))
		{
			// Couldn't find the backup
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_backup'], $vbulletin->GPC['backupid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
				
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_edited'];
	}
	else
	{
		// Start the data arrays
		$vbulletin->GPC['backup']['backupdata'] = array();
		//$tables = array('achievement', /*'activitylevel', */'category', 'condition', 'conditionbridge', /*'pointslog', */'promotion', 'rewards', 'type', 'medal');
		$tables = array('achievement', 'category', 'condition', 'conditionbridge', 'promotion', 'type', 'medal');
		
		foreach ($tables as $tablename)
		{
			if (VBACTIVITY::$cache["$tablename"])
			{
				// This data was cached
				$vbulletin->GPC['backup']['backupdata']["$tablename"] = VBACTIVITY::$cache["$tablename"];
				continue;
			}
			
			// Query the missing data
			$data_q = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_{$tablename}");
			
			$i = 0;
			while ($data_r = $db->fetch_array($data_q))
			{
				// Store the table name
				$vbulletin->GPC['backup']['backupdata']["$tablename"]["$i"] = $data_r;
				$i++;
			}
			$db->free_result($data_q);
			unset($data_r);
		}
		
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_added'];
	}
	
	// backup fields
	foreach ($vbulletin->GPC['backup'] AS $key => $val)
	{
		if (!$vbulletin->GPC['backupid'] OR $existing["$key"] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	define('CP_REDIRECT', 'vbactivity.php?do=backup');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_backup'], $phrase);	
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$backupid = $vbulletin->input->clean_gpc('r', 'backupid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_backup']));
	print_delete_confirmation('dbtech_vbactivity_backup', $backupid, 'vbactivity', 'backup', 'dbtech_vbactivity_backup', array('action' => 'kill'), '', 'title');
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_gpc('r', 'backupid', TYPE_UINT);
	
	if (!$existing = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_backup WHERE backupid = " . $db->sql_prepare($vbulletin->GPC['backupid'])))
	{
		// Couldn't find the backup
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_backup'], $vbulletin->GPC['backupid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Backup', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=backup');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_backup'], $vbphrase['dbtech_vbactivity_deleted']);	
}

// #############################################################################
if ($_REQUEST['action'] == 'load')
{
	$backupid = $vbulletin->input->clean_gpc('r', 'backupid', TYPE_UINT);
	$backup = $db->query_first_slave("SELECT backupid, title FROM " . TABLE_PREFIX . "dbtech_vbactivity_backup WHERE backupid = " . $db->sql_prepare($backupid));	
	
	print_cp_header($vbphrase['dbtech_vbactivity_load_backup']);
	print_confirmation(construct_phrase($vbphrase['are_you_sure_want_to_load_backup_x'], $backup['title']), 'vbactivity', 'backup', array('action' => 'doload', 'backupid' => $backupid));
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'doload')
{
	$backupid = $vbulletin->input->clean_gpc('p', 'backupid', TYPE_UINT);
	$backup = $db->query_first_slave("SELECT backupdata FROM " . TABLE_PREFIX . "dbtech_vbactivity_backup WHERE backupid = " . $db->sql_prepare($backupid));
	$data = unserialize($backup['backupdata']);
	
	foreach ($data as $tablename => $rows)
	{
		// First truncate the table
		$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_vbactivity_{$tablename}");
		
		$SQL = array();
		$i = 0;
		foreach ($rows as $row)
		{
			$SQL["$i"] = '(';
			$tmp = array();
			$keys = array();
			foreach ($row as $key => $value)
			{
				// Add the keys and the rows to arrays (keys will be overwritten but who cares, they come out right)
				$keys[] = $key;
				$tmp[] = "'" . addslashes($value) . "'";
			}
			$SQL["$i"] .= implode(',', $tmp);
			$SQL["$i"] .= ')';
			
			$i++;
		}
		
		if (count($SQL))
		{
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dbtech_vbactivity_{$tablename}
					(" . implode(',', $keys) . ")
				VALUES 
					" . implode(',', $SQL)
			);
		}
	}
	
	define('CP_REDIRECT', 'vbactivity.php?do=repaircache');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_backup'], $vbphrase['dbtech_vbactivity_loaded']);	
}


print_cp_footer();