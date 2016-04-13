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

if (!VBACTIVITY::$permissions['snapshot'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'snapshot' OR empty($_REQUEST['action']))
{
	$snapshots = array();
	$snapshots_q = $db->query_read_slave("SELECT snapshotid, title, description, active FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot");
	while ($snapshots_r = $db->fetch_array($snapshots_q))
	{
		// Store the snapshot
		$snapshots["$snapshots_r[snapshotid]"] = $snapshots_r;
	}
	$db->free_result($snapshots_q);
	unset($snapshots_r);
	
	print_cp_header($vbphrase['dbtech_vbactivity_snapshot_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['title'];
	$headings[] = $vbphrase['description'];
	$headings[] = $vbphrase['active'];
	$headings[] = $vbphrase['load'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	if (count($snapshots))
	{
		print_form_header('vbactivity', 'snapshot');	
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_snapshot_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_snapshot_management_descr'], false, count($headings));	
		
		print_cells_row($headings, 0, 'thead');
		
		foreach ($snapshots as $snapshotid => $snapshot)
		{
			// Table data
			$cell = array();
			$cell[] = $snapshot['title'];
			$cell[] = $snapshot['description'];
			$cell[] = ($snapshot['active'] ? '<span class="col-i"><strong>' . $vbphrase['yes'] . '</strong></span>' : $vbphrase['no']);		
			$cell[] = construct_link_code($vbphrase['dbtech_vbactivity_load_snapshot'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=snapshot&amp;action=load&amp;snapshotid=' . $snapshotid);
			$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=snapshot&amp;action=modify&amp;snapshotid=' . $snapshotid);
			$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=snapshot&amp;action=delete&amp;snapshotid=' . $snapshotid);
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
		}
		
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_snapshot'], false, count($headings));	
	}
	else
	{
		print_form_header('vbactivity', 'snapshot');	
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_vbactivity_snapshot_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_no_snapshots'], false, count($headings));
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_snapshot'], false, count($headings));	
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$snapshotid = $vbulletin->input->clean_gpc('r', 'snapshotid', TYPE_UINT);
	$snapshot = ($snapshotid ? $snapshots_q = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot WHERE snapshotid = " . intval($snapshotid)) : false);
	
	if (!is_array($snapshot))
	{
		// Non-existing snapshot
		$snapshotid = 0;
	}
	
	if ($snapshotid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_snapshot'], $snapshot['title'])));
		print_form_header('vbactivity', 'snapshot');	
		construct_hidden_code('action', 'update');
		construct_hidden_code('snapshotid', $snapshotid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_snapshot'], $snapshot['title']));
		
		// Get the snapshot data
		$snapshot['data'] = unserialize($snapshot['data']);
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_snapshot']);
		print_form_header('vbactivity', 'snapshot');	
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_snapshot']);
		
		// Fake the snapshot data
		$snapshot['data'] = array();
	}
	
	foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
	{
		if (!$type['active'] OR !($type['settings'] & 2))
		{
			// This type wasn't even active
			continue;
		}
		
		if (isset($snapshot['data']["$type[typename]"]))
		{
			// Already defined
			continue;
		}
		
		// Default data
		$snapshot['data']["$type[typename]"] = $type['points'];
	}
	
	print_input_row($vbphrase['title'], 			'snapshot[title]', 			$snapshot['title']);
	print_textarea_row($vbphrase['description'], 	'snapshot[description]', 	$snapshot['description']);
	
	print_table_header($vbphrase['dbtech_vbactivity_snapshot_points_data']);
	print_description_row($vbphrase['dbtech_vbactivity_snapshot_points_data_descr']);
	foreach ($snapshot['data'] as $typename => $points)
	{
		// Print the input row
		print_input_row($vbphrase["dbtech_vbactivity_condition_per{$typename}"], "snapshot[data][$typename]", $points);
	}
	
	print_submit_row(($snapshotid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_snapshot']));
}

// #############################################################################
if ($_POST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'snapshotid' 	=> TYPE_UINT,
		'snapshot' 		=> TYPE_ARRAY,
	));
	
	foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
	{
		if (!$type['active'] OR !($type['settings'] & 2))
		{
			// This type wasn't even active
			continue;
		}
		
		if ($vbulletin->GPC['snapshot']['data']["$type[typename]"] != '')
		{
			// Already defined
			continue;
		}
		
		// Default data
		$vbulletin->GPC['snapshot']['data']["$type[typename]"] = $type['points'];
	}	
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Snapshot', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['snapshotid'])
	{
		if (!$existing = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot WHERE snapshotid = " . $db->sql_prepare($vbulletin->GPC['snapshotid'])))
		{
			// Couldn't find the snapshot
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_snapshot'], $vbulletin->GPC['snapshotid']);
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
	
	// snapshot fields
	foreach ($vbulletin->GPC['snapshot'] AS $key => $val)
	{
		if (!$vbulletin->GPC['snapshotid'] OR $existing["$key"] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
	
	define('CP_REDIRECT', 'vbactivity.php?do=snapshot');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_snapshot'], $phrase);
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'snapshotid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_snapshot']));
	print_delete_confirmation('dbtech_vbactivity_snapshot', $vbulletin->GPC['snapshotid'], 'vbactivity', 'snapshot', 'dbtech_vbactivity_snapshot', array('action' => 'kill'), '', 'title');
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_gpc('r', 'snapshotid', TYPE_UINT);
	
	if (!$existing = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot WHERE snapshotid = " . $db->sql_prepare($vbulletin->GPC['snapshotid'])))
	{
		// Couldn't find the snapshot
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_snapshot'], $vbulletin->GPC['snapshotid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Snapshot', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=snapshot');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_snapshot'], $vbphrase['dbtech_vbactivity_deleted']);	
}

// #############################################################################
if ($_REQUEST['action'] == 'load')
{
	require_once(DIR . '/dbtech/vbactivity/includes/functions.php');
	
	$vbulletin->input->clean_gpc('r', 'snapshotid', TYPE_UINT);	
	$snapshot = $db->query_first_slave("SELECT snapshotid, title FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot WHERE snapshotid = " . $db->sql_prepare($vbulletin->GPC['snapshotid']));	
	
	print_cp_header($vbphrase['dbtech_vbactivity_load_snapshot']);
	print_confirmation(construct_phrase($vbphrase['are_you_sure_want_to_load_snapshot_x'], $snapshot['title']), 'vbactivity', 'snapshot', array('action' => 'doload', 'snapshotid' => $vbulletin->GPC['snapshotid']));
}

// #############################################################################
if ($_POST['action'] == 'doload')
{
	$vbulletin->input->clean_gpc('r', 'snapshotid', TYPE_UINT);
	
	$snapshot = $db->query_first_slave("SELECT data FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot WHERE snapshotid = " . $db->sql_prepare($vbulletin->GPC['snapshotid']));
	$data = unserialize($snapshot['data']);
	
	foreach ($data as $varname => $value)
	{
		if (!$existing = VBACTIVITY::$cache['type'][VBACTIVITY::fetch_type($varname)])
		{
			// Non-existant type
			continue;
		}
		
		// init data manager
		$dm =& VBACTIVITY::initDataManager('Type', $vbulletin, ERRTYPE_CP);
			$dm->set_existing($existing);
			$dm->set('points', $value);
		$dm->save();
		unset($dm);
	}
	
	// Set the Active flag
	$db->query_write("UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_snapshot SET active = IF(snapshotid = " . $db->sql_prepare($vbulletin->GPC['snapshotid']) . ", 1, 0)");
	
	define('CP_REDIRECT', 'vbactivity.php?do=snapshot');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_snapshot'], $vbphrase['dbtech_vbactivity_loaded']);	
}

// #############################################################################
if ($_REQUEST['action'] == 'schedule')
{
	$snapshotschedules = array();
	$snapshotschedules_q = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshotschedule");
	while ($snapshotschedules_r = $db->fetch_array($snapshotschedules_q))
	{
		// Store the snapshotschedule
		$snapshotschedules["$snapshotschedules_r[snapshotscheduleid]"] = $snapshotschedules_r;
	}
	$db->free_result($snapshotschedules_q);
	unset($snapshotschedules_r);
	
	$snapshots = array();
	$snapshots_q = $db->query_read_slave("SELECT snapshotid, title, description, active FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot");
	while ($snapshots_r = $db->fetch_array($snapshots_q))
	{
		// Store the snapshot
		$snapshots["$snapshots_r[snapshotid]"] = $snapshots_r;
	}
	$db->free_result($snapshots_q);
	unset($snapshots_r);
	
	print_cp_header($vbphrase['dbtech_vbactivity_snapshotschedule_management']);
	
	if (count($snapshots) < 2)
	{
		// need 2 snapshots
		print_stop_message('dbtech_vbactivity_missing_x',
			$vbphrase['dbtech_vbactivity_snapshot'],
			$vbulletin->session->vars['sessionurl'],
			'snapshot',
			'modify'
		);	
	}
	
	// Set days
	$days = array(
		0 => $vbphrase['sunday'],
		1 => $vbphrase['monday'],
		2 => $vbphrase['tuesday'],
		3 => $vbphrase['wednesday'],
		4 => $vbphrase['thursday'],
		5 => $vbphrase['friday'],
		6 => $vbphrase['saturday'],
	);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_start_hour'];
	$headings[] = $vbphrase['dbtech_vbactivity_start_day'];
	$headings[] = $vbphrase['dbtech_vbactivity_end_hour'];
	$headings[] = $vbphrase['dbtech_vbactivity_end_day'];
	$headings[] = $vbphrase['dbtech_vbactivity_load_snapshot'];
	$headings[] = $vbphrase['dbtech_vbactivity_revert_snapshot'];
	$headings[] = $vbphrase['active'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	if (count($snapshotschedules))
	{
		print_form_header('vbactivity', 'snapshot');
		construct_hidden_code('action', 'modifyschedule');
		print_table_header($vbphrase['dbtech_vbactivity_snapshotschedule_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_snapshotschedule_management_descr'], false, count($headings));	
		
		print_cells_row($headings, 0, 'thead');
		
		foreach ($snapshotschedules as $snapshotscheduleid => $snapshotschedule)
		{
			// Table data
			$cell = array();
			$cell[] = $snapshotschedule['start_hour'];
			$cell[] = $days["$snapshotschedule[start_day]"];
			$cell[] = $snapshotschedule['end_hour'];
			$cell[] = $days["$snapshotschedule[end_day]"];
			$cell[] = $snapshots["$snapshotschedule[loadsnapshotid]"]['title'];
			$cell[] = $snapshots["$snapshotschedule[revertsnapshotid]"]['title'];
			$cell[] = ($snapshotschedule['active'] ? $vbphrase['yes'] : $vbphrase['no']);		
			$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=snapshot&amp;action=modifyschedule&amp;snapshotscheduleid=' . $snapshotscheduleid);
			$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=snapshot&amp;action=deleteschedule&amp;snapshotscheduleid=' . $snapshotscheduleid);
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
		}
		
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_snapshotschedule'], false, count($headings));	
	}
	else
	{
		print_form_header('vbactivity', 'snapshot');
		construct_hidden_code('action', 'modifyschedule');
		print_table_header($vbphrase['dbtech_vbactivity_snapshotschedule_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_no_snapshotschedules'], false, count($headings));
		print_submit_row($vbphrase['dbtech_vbactivity_add_new_snapshotschedule'], false, count($headings));	
	}	
}

// #############################################################################
if ($_REQUEST['action'] == 'modifyschedule')
{
	$snapshotscheduleid = $vbulletin->input->clean_gpc('r', 'snapshotscheduleid', TYPE_UINT);
	$snapshotschedule = ($snapshotscheduleid ? $snapshotschedules_q = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshotschedule WHERE snapshotscheduleid = " . intval($snapshotscheduleid)) : false);
	
	if (!is_array($snapshotschedule))
	{
		// Non-existing snapshotschedule
		$snapshotscheduleid = 0;
	}
	
	$snapshots = array();
	$snapshots_q = $db->query_read_slave("SELECT snapshotid, title FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot");
	while ($snapshots_r = $db->fetch_array($snapshots_q))
	{
		// Store the snapshot
		$snapshots["$snapshots_r[snapshotid]"] = $snapshots_r['title'];
	}
	$db->free_result($snapshots_q);
	unset($snapshots_r);
	
	// Set days
	$days = array(
		0 => $vbphrase['sunday'],
		1 => $vbphrase['monday'],
		2 => $vbphrase['tuesday'],
		3 => $vbphrase['wednesday'],
		4 => $vbphrase['thursday'],
		5 => $vbphrase['friday'],
		6 => $vbphrase['saturday'],
	);
	
	// Set hours
	$hours = array();
	for ($i = 0; $i <= 23; $i++)
	{
		// Set the hour
		$hours["$i"] = $i;
	}
	
	if ($snapshotscheduleid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_snapshotschedule'], $snapshotschedule['snapshotscheduleid'])));
		print_form_header('vbactivity', 'snapshot');
		construct_hidden_code('action', 'updateschedule');
		construct_hidden_code('snapshotscheduleid', $snapshotscheduleid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_snapshotschedule'], $snapshotschedule['snapshotscheduleid']));
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_snapshotschedule']);
		print_form_header('vbactivity', 'snapshot');
		construct_hidden_code('action', 'updateschedule');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_snapshotschedule']);
	}
	
	print_select_row($vbphrase['dbtech_vbactivity_start_hour'], 	'snapshotschedule[start_hour]', 		$hours, 	$snapshotschedule['start_hour']);
	print_select_row($vbphrase['dbtech_vbactivity_start_day'], 		'snapshotschedule[start_day]', 			$days, 		$snapshotschedule['start_day']);
	print_select_row($vbphrase['dbtech_vbactivity_end_hour'], 		'snapshotschedule[end_hour]', 			$hours, 	$snapshotschedule['end_hour']);
	print_select_row($vbphrase['dbtech_vbactivity_end_day'], 		'snapshotschedule[end_day]', 			$days, 		$snapshotschedule['end_day']);
	print_select_row($vbphrase['dbtech_vbactivity_load_snapshot'], 	'snapshotschedule[loadsnapshotid]', 	$snapshots, $snapshotschedule['loadsnapshotid']);
	print_select_row($vbphrase['dbtech_vbactivity_revert_snapshot'],'snapshotschedule[revertsnapshotid]', 	$snapshots, $snapshotschedule['revertsnapshotid']);
	print_yes_no_row($vbphrase['active'],							'snapshotschedule[active]', 						$snapshotschedule['active']);
	
	print_submit_row(($snapshotscheduleid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_snapshotschedule']));
}

// #############################################################################
if ($_POST['action'] == 'updateschedule')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'snapshotscheduleid' 	=> TYPE_UINT,
		'snapshotschedule' 		=> TYPE_ARRAY,
	));
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Snapshotschedule', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['snapshotscheduleid'])
	{
		if (!$existing = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshotschedule WHERE snapshotscheduleid = " . $db->sql_prepare($vbulletin->GPC['snapshotscheduleid'])))
		{
			// Couldn't find the snapshotschedule
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_snapshotschedule'], $vbulletin->GPC['snapshotscheduleid']);
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
	
	// snapshotschedule fields
	foreach ($vbulletin->GPC['snapshotschedule'] AS $key => $val)
	{
		if (!$vbulletin->GPC['snapshotscheduleid'] OR $existing["$key"] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
	
	define('CP_REDIRECT', 'vbactivity.php?do=snapshot&action=schedule');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_snapshotschedule'], $phrase);
}

// #############################################################################
if ($_REQUEST['action'] == 'deleteschedule')
{
	$vbulletin->input->clean_gpc('r', 'snapshotscheduleid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_snapshotschedule']));
	print_delete_confirmation('dbtech_vbactivity_snapshotschedule', $vbulletin->GPC['snapshotscheduleid'], 'vbactivity', 'snapshot', 'dbtech_vbactivity_snapshotschedule', array('action' => 'killschedule'), '', 'snapshotscheduleid');
}

// #############################################################################
if ($_POST['action'] == 'killschedule')
{
	$vbulletin->input->clean_gpc('r', 'snapshotscheduleid', TYPE_UINT);
	
	if (!$existing = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshotschedule WHERE snapshotscheduleid = " . $db->sql_prepare($vbulletin->GPC['snapshotscheduleid'])))
	{
		// Couldn't find the snapshot
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_snapshotschedule'], $vbulletin->GPC['snapshotscheduleid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Snapshotschedule', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=snapshot&action=schedule');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_snapshotschedule'], $vbphrase['dbtech_vbactivity_deleted']);	
}

print_cp_footer();