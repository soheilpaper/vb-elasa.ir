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

if (!VBACTIVITY::$permissions['contest'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

$contesttypes = array();
foreach ((array)VBACTIVITY::$cache['contesttype'] as $contesttypeid => $contesttype)
{
	if (!$contesttype['active'])
	{
		// Skip this contest type
		continue;
	}
	
	// Store the contesttype
	$contesttypes[$contesttypeid] = $contesttype['title'];
}

// #############################################################################
if ($_REQUEST['action'] == 'contest' OR empty($_REQUEST['action']))
{
	$contests_by_status = array();
	foreach ((array)VBACTIVITY::$cache['contest'] as $contestid => $contest)
	{
		// Index by upcoming, ongoing or previous
		$contests_by_status[($contest['end'] > TIMENOW ? ($contest['start'] > TIMENOW ? '1' : '2') : '3')][$contestid] = $contest;
	}
	ksort($contests_by_status);

	print_cp_header($vbphrase['dbtech_vbactivity_contest_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_contest_type'];
	$headings[] = $vbphrase['title'];
	$headings[] = $vbphrase['description'];
	$headings[] = $vbphrase['dbtech_vbactivity_contest_start'];
	$headings[] = $vbphrase['dbtech_vbactivity_contest_end'];
	$headings[] = $vbphrase['dbtech_vbactivity_prizes'];
	$headings[] = $vbphrase['dbtech_vbactivity_winners'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	print_form_header('vbactivity', 'contest');
	construct_hidden_code('action', 'modify');			
	print_table_header($vbphrase['dbtech_vbactivity_add_new_contest']);
	print_select_row($vbphrase['dbtech_vbactivity_contesttype'], 	'contesttypeid', 	$contesttypes,	1);
	print_input_row($vbphrase['dbtech_vbactivity_numwinners'], 		'numwinners', 						5);
	print_submit_row($vbphrase['dbtech_vbactivity_add_new_contest'], false);
	
	if (count($contests_by_status))
	{
		print_table_start();
		foreach ($contests_by_status as $status => $contests)
		{
			print_table_header($vbphrase['dbtech_vbactivity_conteststatus_' . $status], count($headings));		
			print_cells_row($headings, 0, 'thead');
			
			foreach ($contests as $contestid => $contest)
			{
				// Grab some important arrays
				$prizes 	= (is_array($contest['prizes']) 	? $contest['prizes'] 	: array());
				$prizes2 	= (is_array($contest['prizes2']) 	? $contest['prizes2'] 	: array());
				$winners 	= (is_array($contest['winners']) 	? $contest['winners'] 	: array());
				
				// Query winner info
				$winarr = array();
				$prizearr = array();
				foreach ($prizes as $place => $prize)
				{
					// Store medal info
					$medal = VBACTIVITY::$cache['medal'][$prizes[$place]];
					
					// Add the winner info
					$prizearr[] = $place . '. ' . 
						($medal ? construct_link_code($medal['title'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=user&amp;medalid=' . $medal['medalid']) : $vbphrase['n_a']) . 
						' / ' . construct_phrase($vbphrase['dbtech_vbactivity_x_points'], intval($prizes2[$place]));

					$winner = $winners[$place];
					if (!$winner['userid'])
					{
						// We had no winner yet
						continue;
					}
					
					$userinfo = fetch_userinfo($winner['userid']);
					if (!$userinfo)
					{
						// User didn't exist
						continue;
					}
					
					// Add the winner info
					$winarr[] = $place . '. ' . $userinfo['username'] . ' (' . construct_phrase($vbphrase['dbtech_vbactivity_x_points'], $winner['points']) . ')';
				}
				
				// Table data
				$cell = array();
				$cell[] = $contesttypes[$contest['contesttypeid']];
				$cell[] = $contest['title'];
				$cell[] = nl2br($contest['description']);
				$cell[] = vbdate($vbulletin->options['logdateformat'], $contest['start']);
				$cell[] = vbdate($vbulletin->options['logdateformat'], $contest['end']);
				$cell[] = ($prizearr 	? implode('<br />', $prizearr) 	: $vbphrase['n_a']);
				$cell[] = ($winarr 		? implode('<br />', $winarr) 	: (
					$contest['end'] < TIMENOW ?
						construct_link_code($vbphrase['dbtech_vbactivity_recalculate_winners'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contest&amp;action=recalculate&amp;contestid=' . $contest['contestid']) :
						$vbphrase['n_a']
				));
				$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contest&amp;action=modify&amp;contestid=' . $contest['contestid']);
				$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contest&amp;action=delete&amp;contestid=' . $contest['contestid']);
				
				// Print the data
				print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
			}
		}
		print_table_footer();
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$contestid = $vbulletin->input->clean_gpc('r', 'contestid', TYPE_UINT);
	
	// The contest
	$contest = VBACTIVITY::$cache['contest'][$contestid];
	
	if (!is_array($contest))
	{
		// Non-existing contest
		$contestid = 0;
	}

	$timespans = array(
		0 			=> $vbphrase['never'],
		86400 		=> $vbphrase['daily'],
		604800 		=> $vbphrase['weekly'],
		2419200 	=> $vbphrase['monthly'],
		29030400 	=> $vbphrase['yearly'],
	);

	$notifs = array(
		0 			=> $vbphrase['none'],
		1 			=> $vbphrase['email'],
		2 			=> $vbphrase['private_message'],
		3 			=> $vbphrase['dbtech_vbactivity_both'],
	);

	$medals = array(0 => $vbphrase['n_a']);
	foreach ((array)VBACTIVITY::$cache['medal'] as $medalid => $medal)
	{
		// Store the medal
		$medals[$medalid] = $medal['title'];
	}
	
	$criteria = array();
	foreach ((array)VBACTIVITY::$cache['type'] as $typeid => $type)
	{
		if (!$type['active'])
		{
			// Inactive points type
			continue;
		}
		
		if (!($type['settings'] & 8))
		{
			// We're not showing this points type
			continue;
		}
		
		// Store the medal
		$criteria[$typeid] = ($vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] ? $vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] : "dbtech_vbactivity_condition_per{$type[typename]}");
	}
	
	$forums = construct_forum_chooser_options();
	
	$vbulletin->input->clean_array_gpc('r', array(
		'numwinners' 	=> TYPE_UINT,
		'contesttypeid' => TYPE_UINT,
	));
	$defaults = array(
		'title' 		=> 'Cool Contest',
		'description' 	=> 'This is a cool contest with fabulous prizes!',
		'numwinners' 	=> $vbulletin->GPC['numwinners'],
		'numusers' 		=> $vbulletin->GPC['numwinners'],
		'contesttypeid' => $vbulletin->GPC['contesttypeid'],
		'start' 		=> TIMENOW,
		'end' 			=> (TIMENOW + 604800),
		'is_public' 	=> 1,
		'show_criteria' => 1,
		'show_progress' => 0,
	);
	if ($contestid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_contest'], $contest['title'])));
		print_form_header('vbactivity', 'contest');
		construct_hidden_code('action', 'update');			
		construct_hidden_code('contestid', $contestid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_contest'], $contest['title']));

		if (!$vbulletin->debug)
		{
			$vbphrase['title']  		= $vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_contest_{$contestid}_title]") . '</dfn>';
			$vbphrase['description'] 	= $vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_contest_{$contestid}_description]") . '</dfn>';
		}
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_contest']);
		print_form_header('vbactivity', 'contest');
		construct_hidden_code('action', 'update');			
		print_table_header($vbphrase['dbtech_vbactivity_add_new_contest']);
		
		// Set some defaults
		$contest = $defaults;
	}
	
	if (!$contest['numwinners'] OR !$contest['contesttypeid'])
	{
		// Missing categories
		print_stop_message('dbtech_vbactivity_missing_x',
			$vbphrase['dbtech_vbactivity_contest_info'],
			$vbulletin->session->vars['sessionurl'],
			'contest',
			'contest'
		);
	}
	
	// Grab the functions
	require_once(DIR . '/dbtech/vbactivity/includes/functions.php');
		
	// Detect winners
	$winners = (is_array($contest['winners']) ? $contest['winners'] : array());
	
	// Load the contest type info
	$contestObj = VBACTIVITY::initContest($contest);
	
	construct_hidden_code('contest[contesttypeid]', $contest['contesttypeid']);
	print_input_row($vbphrase['title'], 										'contest[title]', 								$contest['title']);
	print_textarea_row($vbphrase['description'], 								'contest[description]', 						$contest['description']);
	print_description_row($vbphrase['dbtech_vbactivity_contest_settings'], false, 2, 'optiontitle');
	print_time_row($vbphrase['dbtech_vbactivity_contest_start'], 				'contest[start]', 								$contest['start'], 						true);	
	print_time_row($vbphrase['dbtech_vbactivity_contest_end'], 					'contest[end]', 								$contest['end'], 						true);	
	print_select_row($vbphrase['dbtech_vbactivity_contest_recurring'], 			'contest[recurring]', 			$timespans, 	$contest['recurring']);
	print_input_row($vbphrase['dbtech_vbactivity_contest_link'], 				'contest[link]', 								$contest['link']);
	print_input_row($vbphrase['dbtech_vbactivity_contest_banner_full'], 		'contest[banner]', 								$contest['banner']);
	print_input_row($vbphrase['dbtech_vbactivity_contest_banner_small'], 		'contest[banner_small]', 						$contest['banner_small']);
	print_textarea_row($vbphrase['dbtech_vbactivity_contest_admin_notifs'],	 	'contest[admin_notifs]', 						$contest['admin_notifs']);
	print_yes_no_row($vbphrase['dbtech_vbactivity_contest_is_public'], 			'contest[is_public]', 							$contest['is_public']);
	print_yes_no_row($vbphrase['dbtech_vbactivity_contest_show_criteria'], 		'contest[show_criteria]', 						$contest['show_criteria']);
	print_yes_no_row($vbphrase['dbtech_vbactivity_contest_show_progress'], 		'contest[show_progress]', 						$contest['show_progress']);
	print_input_row($vbphrase['dbtech_vbactivity_contest_progress_users'], 		'contest[numusers]', 							$contest['numusers']);
	print_select_row($vbphrase['dbtech_vbactivity_contest_winner_notifs'], 		'contest[winner_notifs]', 		$notifs, 		$contest['winner_notifs']);
	if (method_exists($contestObj, 'adminForm'))
	{
		// Item info
		print_table_header($contesttypes[$contest['contesttypeid']]);
		$contestObj->adminForm();
	}
	print_table_header($vbphrase['dbtech_vbactivity_prize_settings']);
	for ($i = 1; $i <= $contest['numwinners']; $i++)
	{
		print_description_row($vbphrase['dbtech_vbactivity_prize'] . ' #' . $i, false, 2, 'optiontitle');		
		if ($winners[$i])
		{
			$tmp = VBACTIVITY::$cache['medal'][$contest['prizes'][$i]['medal']]['title'];
			$tmp = ($tmp ? $tmp : $vbphrase['n_a']);
			
			construct_hidden_code("contest[prizes][$i]", 	$contest['prizes'][$i]);
			construct_hidden_code("contest[prizes2][$i]", 	$contest['prizes2'][$i]);
			print_label_row($vbphrase['dbtech_vbactivity_prize_medal'] . ' #' . $i, 	$tmp);
			print_label_row($vbphrase['dbtech_vbactivity_prize_points'] . ' #' . $i, 	construct_phrase($vbphrase['dbtech_vbactivity_x_points'], intval($contest['prizes2'][$i])));
		}
		else
		{
			// Each prize
			print_select_row($vbphrase['dbtech_vbactivity_prize_medal'] . ' #' . $i, 	"contest[prizes][$i]", 	$medals, 	$contest['prizes'][$i]);
			print_input_row($vbphrase['dbtech_vbactivity_prize_points'] . ' #' . $i, 	"contest[prizes2][$i]", 			$contest['prizes2'][$i]);
		}
	}
	
	print_table_header($vbphrase['dbtech_vbactivity_criteria_settings']);
	print_label_row('&nbsp;', '<label><input type="checkbox" rel="^-contest[excludedcriteria]" title="' . $vbphrase['check_all'] . '" /> ' . $vbphrase['check_all'] . '</label>');
	print_checkbox_array_row($vbphrase['dbtech_vbactivity_excluded_criteria'],	'contest[excludedcriteria][]',	$criteria, 		(array)$contest['excludedcriteria'], 	true);
	print_table_header($vbphrase['dbtech_vbactivity_forum_settings']);
	print_label_row('&nbsp;', '<input type="checkbox" rel="^-contest[excludedforums]" title="' . $vbphrase['check_all'] . '" /> ' . $vbphrase['check_all']);
	print_checkbox_array_row($vbphrase['dbtech_vbactivity_excluded_forums'],	'contest[excludedforums][]',	$forums, 		(array)$contest['excludedforums'], 		true);
	print_submit_row(($contestid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_contest']));

	echo '<script type="text/javascript" src="' . VBACTIVITY::jQueryPath() . '"></script>';
	VBACTIVITY::js('_admin2');
}

// #############################################################################
if ($_REQUEST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'contestid' 	=> TYPE_UINT,
		'contest' 		=> TYPE_ARRAY,
	));
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Contest', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['contestid'])
	{
		if (!$existing = VBACTIVITY::$cache['contest'][$vbulletin->GPC['contestid']])
		{
			// Couldn't find the contest
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_contest'], $vbulletin->GPC['contestid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
				
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_edited'];
	}
	else
	{
		// Ensure this is set
		$vbulletin->GPC['contest']['numwinners'] = count($vbulletin->GPC['contest']['prizes']);
			
		// Added
		$phrase = $vbphrase['dbtech_vbactivity_added'];
	}
	
	// contest fields
	foreach ($vbulletin->GPC['contest'] AS $key => $val)
	{
		if (!$vbulletin->GPC['contestid'] OR $existing[$key] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	define('CP_REDIRECT', 'vbactivity.php?do=contest');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_contest'], $phrase);
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'contestid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_contest']));
	print_delete_confirmation('dbtech_vbactivity_contest', $vbulletin->GPC['contestid'], 'vbactivity', 'contest', 'dbtech_vbactivity_contest', array('action' => 'kill'), '', 'title');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_gpc('r', 'contestid', TYPE_UINT);
	
	if (!$existing = VBACTIVITY::$cache['contest'][$vbulletin->GPC['contestid']])
	{
		// Couldn't find the contest
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_contest'], $vbulletin->GPC['contestid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Contest', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=contest');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_contest'], $vbphrase['dbtech_vbactivity_deleted']);	
}

// #############################################################################
if ($_REQUEST['action'] == 'recalculate')
{
	$vbulletin->input->clean_gpc('r', 'contestid', TYPE_UINT);
	
	if (!$existing = VBACTIVITY::$cache['contest'][$vbulletin->GPC['contestid']])
	{
		// Couldn't find the contest
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_contest'], $vbulletin->GPC['contestid']);
	}

	// Load the contest type info
	$contestObj = VBACTIVITY::initContest($existing);

	if (method_exists($contestObj, 'recalculate'))
	{
		// Item info
		$contestObj->recalculate();
	}

	define('CP_REDIRECT', 'vbactivity.php?do=contest');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_contest'], $vbphrase['dbtech_vbactivity_edited']);	
}

print_cp_footer();