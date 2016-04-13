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

if (!VBACTIVITY::$permissions['award'] AND !VBACTIVITY::$permissions['grantawards'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

// #############################################################################
if ($_REQUEST['action'] == 'award' OR empty($_REQUEST['action']))
{
	$medals_by_category = array();
	foreach ((array)VBACTIVITY::$cache['medal'] as $medalid => $medal)
	{
		// Index by categoryid
		$medals_by_category[$medal['categoryid']][$medalid] = $medal;
	}
	
	print_cp_header($vbphrase['dbtech_vbactivity_medal_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['title'];
	$headings[] = $vbphrase['description'];
	if (VBACTIVITY::$permissions['grantawards'])
	{
		$headings[] = $vbphrase['users'];
	}
	$headings[] = $vbphrase['display_order'];
	$headings[] = $vbphrase['dbtech_vbactivity_sticky'];
	/*DBTECH_PRO_START*/
	$headings[] = $vbphrase['dbtech_vbactivity_availability'];
	/*DBTECH_PRO_END*/
	if (VBACTIVITY::$permissions['award'])
	{
		$headings[] = $vbphrase['edit'];
		$headings[] = $vbphrase['delete'];
	}
	
	if (count($medals_by_category))
	{
		if (VBACTIVITY::$permissions['award'])
		{		
		print_form_header('vbactivity', 'award');	
		construct_hidden_code('action', 'displayorder');
		}
		else
		{
			print_table_start();
		}
		print_table_header($vbphrase['dbtech_vbactivity_medal_management'], count($headings));
		print_description_row($vbphrase['dbtech_vbactivity_medal_management_descr'], false, count($headings));	
		
		foreach ($medals_by_category as $categoryid => $medals)
		{
			print_table_header(VBACTIVITY::$cache['category'][$categoryid]['title'], count($headings));
			print_description_row(VBACTIVITY::$cache['category'][$categoryid]['description'], false, count($headings));	
			print_cells_row($headings, 0, 'thead');
			
			foreach ($medals as $medalid => $medal)
			{
				// Table data
				$cell = array();
				$cell[] = ($medal['icon'] ? '<img src="../images/icons/vbactivity/' . $medal['icon'] . '" alt="' . $medal['title'] . '" /> ' : '') . $medal['title'];
				$cell[] = $medal['description'];
				if (VBACTIVITY::$permissions['grantawards'])
				{
					$cell[] = construct_link_code($vbphrase['dbtech_vbactivity_manage_users'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=user&amp;medalid=' . $medalid);
				}
				if (VBACTIVITY::$permissions['award'])
				{
					$cell[] = "<input type=\"text\" class=\"bginput\" name=\"order[$medalid]\" value=\"$medal[displayorder]\" tabindex=\"1\" size=\"3\" title=\"" . $vbphrase['edit_display_order'] . "\" />";
				}
				else
				{
					$cell[] = $medal['displayorder'];
				}
				$cell[] = ($medal['sticky'] ? $vbphrase['yes'] : '<span class="col-i"><strong>' . $vbphrase['no'] . '</strong></span>');
				/*DBTECH_PRO_START*/
				$cell[] = 
					$vbphrase['dbtech_vbactivity_requestable'] . ': ' . (($medal['availability'] & 1) ? $vbphrase['yes'] : '<span class="col-i"><strong>' . $vbphrase['no'] . '</strong></span>') . '<br />' .
					$vbphrase['dbtech_vbactivity_nominatable'] . ': ' . (($medal['availability'] & 2) ? $vbphrase['yes'] : '<span class="col-i"><strong>' . $vbphrase['no'] . '</strong></span>')
				;
				/*DBTECH_PRO_END*/
				if (VBACTIVITY::$permissions['award'])
				{
					$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=modify&amp;medalid=' . $medalid);
					$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=delete&amp;medalid=' . $medalid);
				}
				
				// Print the data
				print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
			}
		}

		$otherButtons = '';
		if (VBACTIVITY::$permissions['award'])
		{
			$otherButtons .= "<input type=\"button\" id=\"addnew\" class=\"button\" value=\"" . str_pad($vbphrase['dbtech_vbactivity_add_new_medal'], 8, ' ', STR_PAD_BOTH) . "\" tabindex=\"1\" onclick=\"window.location = 'vbactivity.php?do=award&amp;action=modify'\" /> ";
		}
		if (VBACTIVITY::$permissions['grantawards'])
		{
			$otherButtons .= "<input type=\"button\" id=\"givenew\" class=\"button\" value=\"" . str_pad($vbphrase['dbtech_vbactivity_award_new_medal'], 8, ' ', STR_PAD_BOTH) . "\" tabindex=\"1\" onclick=\"window.location = 'vbactivity.php?do=award&amp;action=modifyuser'\" />";
		}

		if (VBACTIVITY::$permissions['award'])
		{
			// Add any additional buttons
			print_submit_row($vbphrase['save_display_order'], false, count($headings), false, $otherButtons);
		}
		else
		{
			// Add any additional buttons
			print_table_footer(count($headings), $otherButtons);
		}
	}
	else
	{
		if (VBACTIVITY::$permissions['award'])
		{
			print_form_header('vbactivity', 'award');	
			construct_hidden_code('action', 'modify');
		}
		else
		{
			print_table_start();
		}
		print_description_row($vbphrase['dbtech_vbactivity_no_medals'], false, count($headings));
		if (VBACTIVITY::$permissions['award'])
		{
			print_submit_row($vbphrase['dbtech_vbactivity_add_new_medal'], false, count($headings));
		}
		else
		{
			print_table_footer();
		}
	}
}

if (VBACTIVITY::$permissions['award'])
{
// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	require_once(DIR . '/dbtech/vbactivity/includes/functions.php');
	
	$medalid = $vbulletin->input->clean_gpc('r', 'medalid', TYPE_UINT);
	$medal = ($medalid ? VBACTIVITY::$cache['medal']["$medalid"] : false);
	
	$categorys = array();
	foreach (VBACTIVITY::$cache['category'] as $categoryid => $category)
	{
		// Add the phrased category name to the array
		$categorys[$categoryid] = $category['title'];
	}
	
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
	
	if (!is_array($medal))
	{
		// Non-existing medal
		$medalid = 0;
	}
	
	if ($medalid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_medal'], $medal['title'])));
		print_form_header('vbactivity', 'award');
		construct_hidden_code('action', 'update');
		construct_hidden_code('medalid', $medalid);
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_medal'], $medal['title']));

		if (!$vbulletin->debug)
		{
			$vbphrase['title']  		= $vbphrase['title'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_medal_{$medalid}_title]") . '</dfn>';
			$vbphrase['description'] 	= $vbphrase['description'] . '<dfn>' . construct_link_code($vbphrase['translations'], "phrase.php?do=edit&e[global][dbtech_vbactivity_medal_{$medalid}_description]") . '</dfn>';
		}
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_add_new_medal']);
		print_form_header('vbactivity', 'award');
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_vbactivity_add_new_medal']);
		$userids = array();	
	}
	
	$medalicons_raw = array();
	$d = dir(DIR . '/images/icons/vbactivity/medals');
	while (false !== ($file = $d->read()))
	{
		if ($file != '.' AND $file != '..' AND $file != 'index.html')
		{
			// Store the icon
			$medalicons_raw['medals/' . $file] = $file;
		}
	}
	$d->close();
	
	$medalicons = array('' => $vbphrase['none']) + $medalicons_raw;
	$medalicons2 = array('' => $vbphrase['dbtech_vbactivity_use_fullsize']) + $medalicons_raw;
	
	// Sort the array as a string
	asort($medalicons, SORT_STRING);
	
	print_input_row($vbphrase['title'], 									'medal[title]', 						$medal['title']);
	print_select_row($vbphrase['dbtech_vbactivity_category'], 				'medal[categoryid]', 	$categorys, 	$medal['categoryid']);
	print_textarea_row($vbphrase['description'], 							'medal[description]', 					$medal['description']);
	print_input_row($vbphrase['display_order'], 							'medal[displayorder]', 					(isset($medal['displayorder']) ? $medal['displayorder'] : 10));
	print_select_row($vbphrase['dbtech_vbactivity_medal_icon'], 			'medal[icon]',			$medalicons, 	$medal['icon']);
	if (VBACTIVITY::$isPro)
	{
		print_select_row($vbphrase['dbtech_vbactivity_medal_icon_small'], 	'medal[icon_small]',	$medalicons2, 	$medal['icon_small']);
	}
	print_yes_no_row($vbphrase['dbtech_vbactivity_sticky_medal'],			'medal[sticky]', 						$medal['sticky']);
	if (VBACTIVITY::$isPro)
	{
		print_bitfield_row($vbphrase['dbtech_vbactivity_availability'], 'medal[availability]', 'nocache|dbtech_vbactivity_medal_availability', $medal['availability']);
	}
	
	print_submit_row(($medalid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_add_new_medal']));
	VBACTIVITY::js('_admin');
	VBACTIVITY::js("vBActivity_Admin.image_preview('medal\\\[icon\\\]', '{$vbulletin->options['bburl']}/images/icons/vbactivity/');", false);
	VBACTIVITY::js("vBActivity_Admin.image_preview('medal\\\[icon_small\\\]', '{$vbulletin->options['bburl']}/images/icons/vbactivity/');", false);
}


// #############################################################################
if ($_POST['action'] == 'update')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'medalid' 	=> TYPE_UINT,
		'medal' 		=> TYPE_ARRAY,
	));
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Medal', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['medalid'])
	{
		if (!$existing = VBACTIVITY::$cache['medal']["{$vbulletin->GPC[medalid]}"])
		{
			// Couldn't find the medal
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_medal'], $vbulletin->GPC['medalid']);
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
	
	// medal fields
	foreach ($vbulletin->GPC['medal'] AS $key => $val)
	{
		if (!$vbulletin->GPC['medalid'] OR $existing[$key] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
		
	define('CP_REDIRECT', 'vbactivity.php?do=award');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_medal'], $phrase);
}

// #############################################################################
if ($_POST['action'] == 'displayorder')
{
	$vbulletin->input->clean_array_gpc('p', array('order' => TYPE_ARRAY));
	
	if (is_array($vbulletin->GPC['order']))
	{
		foreach ($vbulletin->GPC['order'] as $medalid => $displayorder)
		{
			if (!$existing = VBACTIVITY::$cache['medal']["$medalid"])
			{
				// Couldn't find the medal
				continue;
			}
			
			if ($existing['displayorder'] == $displayorder)
			{
				// No change
				continue;
			}
			
			// init data manager
			$dm =& VBACTIVITY::initDataManager('Medal', $vbulletin, ERRTYPE_CP);
				$dm->set_existing($existing);
				$dm->set('displayorder', $displayorder);
			$dm->save();
			unset($dm);	
		}
	}
	
	define('CP_REDIRECT', 'vbactivity.php?do=award');
	print_stop_message('saved_display_order_successfully');	
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'medalid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_medal']));
	print_delete_confirmation('dbtech_vbactivity_medal', $vbulletin->GPC['medalid'], 'vbactivity', 'award', 'dbtech_vbactivity_medal', array('action' => 'kill'), '', 'title');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'medalid' => TYPE_UINT,
		'kill' 		 => TYPE_BOOL
	));
	
	if (!$existing = VBACTIVITY::$cache['medal']["{$vbulletin->GPC[medalid]}"])
	{
		// Couldn't find the medal
		print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_medal'], $vbulletin->GPC['medalid']);
	}
	
	// init data manager
	$dm =& VBACTIVITY::initDataManager('Medal', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'vbactivity.php?do=award');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_medal'], $vbphrase['dbtech_vbactivity_deleted']);	
}
} // End "Can Manage Awards"

if (VBACTIVITY::$permissions['grantawards'])
{
// #############################################################################
if ($_REQUEST['action'] == 'user')
{
	if (empty(VBACTIVITY::$cache['medal']))
	{
		// Missing categories
		print_stop_message('dbtech_vbactivity_missing_x',
			$vbphrase['dbtech_vbactivity_medal'],
			$vbulletin->session->vars['sessionurl'],
			'award',
			'modify'
		);
	}
	
	$medalid = $vbulletin->input->clean_gpc('r', 'medalid', TYPE_UINT);
	$medal = ($medalid ? VBACTIVITY::$cache['medal']["$medalid"] : false);
	
	// Grab all medals
	$rewards_q = $db->query_read_slave("
		SELECT rewards.*, user.username
		FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards AS rewards
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		WHERE feature = 'medal'
			" . ($medalid ? 'AND featureid = ' . $db->sql_prepare($medalid) : '') . "
		ORDER BY dateline DESC
	");
	
	$users_by_reward	= array();
	while ($rewards_r = $db->fetch_array($rewards_q))
	{
		// Index by userid
		$users_by_reward["$rewards_r[featureid]"]["$rewards_r[rewardid]"] = $rewards_r;
	}
	$db->free_result($rewards_q);
	unset($rewards_r);
	
	print_cp_header($vbphrase['dbtech_vbactivity_award_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['user_name'];
	$headings[] = $vbphrase['dbtech_vbactivity_awarded_on'];
	$headings[] = $vbphrase['reason'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	
	print_form_header('vbactivity', 'award');
	construct_hidden_code('action', 'modifyuser');
	construct_hidden_code('medalid', $medalid);
	print_table_header($vbphrase['dbtech_vbactivity_award_management'], count($headings));
	if (count($users_by_reward))
	{
		print_description_row($vbphrase['dbtech_vbactivity_award_management_descr'], false, count($headings));		
		foreach ($users_by_reward as $featureid => $rewards)
		{
			print_description_row(VBACTIVITY::$cache['medal']["$featureid"]['title'], false, count($headings), 'optiontitle');
			print_description_row(VBACTIVITY::$cache['medal']["$featureid"]['description'], false, count($headings));	
			print_cells_row($headings, 0, 'thead');
			
			foreach ($rewards as $rewardid => $reward)
			{
				// Table data
				$cell = array();
				$cell[] = $reward['username'];
				$cell[] = vbdate($vbulletin->options['dateformat'], $reward['dateline']);				
				$cell[] = $reward['reason'];
 				$cell[] = construct_link_code($vbphrase['edit'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=modifyuser&amp;rewardid=' . $rewardid);
				$cell[] = construct_link_code($vbphrase['delete'], 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=deleteuser&amp;rewardid=' . $rewardid);
				
				// Print the data
				print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
				
			}
		}
	}
	else
	{
		print_description_row($vbphrase['dbtech_vbactivity_no_awarded_medals'], false, count($headings));
	}
	print_submit_row($vbphrase['dbtech_vbactivity_award_new_medal'], false, count($headings));	
}

// #############################################################################
if ($_REQUEST['action'] == 'modifyuser')
{
	$rewardid = $vbulletin->input->clean_gpc('r', 'rewardid', TYPE_UINT);
	if (!$reward = $db->query_first_slave("
		SELECT rewards.*, user.username
		FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards AS rewards
		LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
		WHERE rewardid = ". $db->sql_prepare($rewardid)
	))
	{
		// Non-existingcondition condition
		$rewardid = 0;
	}
	
	$medalid = $vbulletin->input->clean_gpc('r', 'medalid', TYPE_UINT);
	if (!$medal = VBACTIVITY::$cache['medal']["$medalid"])
	{
		// Non-existingcondition condition
		$medalid = 0;
	}
	
	$medals = array();
	foreach ((array)VBACTIVITY::$cache['medal'] as $featureid => $feature)
	{
		// Store the medal info
		$medals["$featureid"] = $feature['title'];
	}
	
	// Sort the array as a string
	asort($medals, SORT_STRING);
	
	if ($rewardid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_medal'], VBACTIVITY::$cache['medal']["$reward[featureid]"]['title'])));
		print_form_header('vbactivity', 'award');
		construct_hidden_code('action', 'updateuser');
		construct_hidden_code('feature', 'medal');
		construct_hidden_code('userid', $reward['userid']);
		construct_hidden_code('rewardid', $rewardid);
		
		print_table_header(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_medal'], VBACTIVITY::$cache['medal']["$reward[featureid]"]['title']));
		print_label_row($vbphrase['username'],	$reward['username']);
		print_time_row($vbphrase['dbtech_vbactivity_award_date'], 	'dateline', $reward['dateline'], true);	
		print_select_row($vbphrase['dbtech_vbactivity_medal'], 		'featureid', $medals, $reward['featureid']);	
		print_textarea_row($vbphrase['reason'],						'reason', $reward['reason']);		
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_vbactivity_award_new_medal']);
		print_form_header('vbactivity', 'award');
		construct_hidden_code('action', 'updateuser');
		construct_hidden_code('feature', 'medal');
		print_table_header($vbphrase['dbtech_vbactivity_award_new_medal']);
		
		for ($i = 0; $i <= 10; $i++)
		{
			print_description_row($vbphrase['dbtech_vbactivity_medal'], false, 2, 'optiontitle');
			print_input_row($vbphrase['userid'],						'userid[' . $i . ']');
			print_description_row('<strong><em>-' . strtoupper($vbphrase['or']) . '-</em></strong>');
			print_input_row($vbphrase['username'],						'username[' . $i . ']');
			print_time_row($vbphrase['dbtech_vbactivity_award_date'], 	'dateline[' . $i . ']', TIMENOW, true);	
			print_select_row($vbphrase['dbtech_vbactivity_medal'], 		'featureid[' . $i . ']', $medals, $medalid);
			print_input_row($vbphrase['dbtech_vbactivity_bonuspoints'],	'bonuspoints[' . $i . ']');
			print_textarea_row($vbphrase['reason'],						'reason[' . $i . ']');		
		}
		
	}
	
	print_submit_row(($rewardid ? $vbphrase['save'] : $vbphrase['dbtech_vbactivity_award_new_medal']));
}

// #############################################################################
if ($_POST['action'] == 'updateuser')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'rewardid' 	=> TYPE_UINT,
	));
	
	if ($vbulletin->GPC['rewardid'])
	{
		$vbulletin->input->clean_array_gpc('p', array(	
			'userid' 	=> TYPE_UINT,
			'dateline'	=> TYPE_UNIXTIME,
			'featureid' => TYPE_UINT,
			'reason' 	=> TYPE_STR
		));
		
		if (!$existing = VBACTIVITY::$cache['medal']["{$vbulletin->GPC[featureid]}"])
		{
			// Editing ID doesn't exist
			print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_medal'], $vbulletin->GPC['featureid']);
		}
		
		VBACTIVITY::$db->update('dbtech_vbactivity_rewards', array(
			'dateline' => $vbulletin->GPC['dateline'],
			'featureid' => $vbulletin->GPC['featureid'],
			'reason' => $vbulletin->GPC['reason'],
		), 'WHERE `rewardid` = ' . $db->sql_prepare($vbulletin->GPC['rewardid']));
		
		// For rewards cache
		$userinfo['userid'] = $vbulletin->GPC['userid'];
		
		// Build the rewards cache for this user
		VBACTIVITY::build_rewards_cache($userinfo);
		
		// Set redirect phrase
		$phrase = $vbphrase['dbtech_vbactivity_edited'];	
	}
	else
	{
		$vbulletin->input->clean_array_gpc('p', array(	
			'userid' 	=> TYPE_ARRAY_UINT,
			'username' 	=> TYPE_ARRAY_STR,
			'dateline'	=> TYPE_ARRAY_UNIXTIME,
			'featureid' => TYPE_ARRAY_UINT,
			'bonuspoints' => TYPE_ARRAY_UNUM,
			'reason' 	=> TYPE_ARRAY_STR			
		));

		// Get type id
		$typeid = VBACTIVITY::fetch_type('awards');

		$usernamecache = array();
		foreach ($vbulletin->GPC['userid'] as $key => $userid)
		{
			// Shorthand
			$medalid = $vbulletin->GPC['featureid'][$key];
			
			if (!$vbulletin->GPC['userid'][$key] AND !$vbulletin->GPC['username'][$key])
			{
				// Userid and username was missing
				continue;
			}
			
			if (!$vbulletin->GPC['userid'][$key])
			{
				// Shorthand
				$username = $vbulletin->GPC['username'][$key];
				
				if (!$usernamecache["$username"])
				{
					// Grab user name
					if (!$userid = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = " . $db->sql_prepare($username)))
					{
						// Bad username
						continue;
					}
					
					// Set cache
					$usernamecache[$username] = $userid['userid'];
				}
				
				// Set array
				$vbulletin->GPC['userid'][$key] = $usernamecache[$username];
			}
	
			if (!$existing = VBACTIVITY::$cache['medal'][$medalid])
			{
				// Editing ID doesn't exist
				print_stop_message('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_medal'], $medalid);
			}
	
			// For rewards cache building
			$userinfo['userid'] = $vbulletin->GPC['userid'][$key];
			
			// Grant the reward to the user
			$rewardid = VBACTIVITY::$db->insert('dbtech_vbactivity_rewards', array(
				'userid' 	=> $vbulletin->GPC['userid'][$key],
				'feature' 	=> 'medal',
				'featureid' => $vbulletin->GPC['featureid'][$key],
				'dateline' 	=> $vbulletin->GPC['dateline'][$key],
				'reason' 	=> $vbulletin->GPC['reason'][$key],
			));
			
			($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_add_medal_admin')) ? eval($hook) : false;
			
			// Add a notification
			VBACTIVITY::add_notification('medal', $vbulletin->GPC['featureid'][$key], $vbulletin->GPC['userid'][$key]);
			
			// Build the rewards cache for this user
			VBACTIVITY::build_rewards_cache($userinfo);

			if ($vbulletin->GPC['bonuspoints'][$key])
			{
				VBACTIVITY::$db->insert('dbtech_vbactivity_awardbonus', array(
					'rewardid' 	=> $rewardid,
					'userid' 	=> $vbulletin->GPC['userid'][$key],
					'awardid' 	=> $vbulletin->GPC['featureid'][$key],
					'dateline' 	=> $vbulletin->GPC['dateline'][$key],
					'points' 	=> $vbulletin->GPC['bonuspoints'][$key],
				));

				// Override points
				VBACTIVITY::$cache['type'][$typeid]['points'] = $vbulletin->GPC['bonuspoints'][$key];

				// Insert points log
				VBACTIVITY::insert_points('awards', $rewardid, $vbulletin->GPC['userid'][$key]);

				// Override points
				VBACTIVITY::$cache['type'][$typeid]['points'] = 0;
			}
		}
		
		// Set redirect phrase
		$phrase = $vbphrase['dbtech_vbactivity_added'];
	}
	
	define('CP_REDIRECT', 'vbactivity.php?do=award&action=user');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_medal'], $phrase);
}

// #############################################################################
if ($_REQUEST['action'] == 'deleteuser')
{
	$vbulletin->input->clean_gpc('r', 'rewardid', TYPE_UINT);
	
	print_cp_header(construct_phrase($vbphrase['dbtech_vbactivity_delete_x'], $vbphrase['dbtech_vbactivity_reward']));
	print_delete_confirmation('dbtech_vbactivity_rewards', $vbulletin->GPC['rewardid'], 'vbactivity', 'award', 'dbtech_vbactivity_medal', array('action' => 'killuser'), '', 'rewardid');
	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'killuser')
{
	$vbulletin->input->clean_gpc('p', 'rewardid', TYPE_UINT);
	
	// Get the user info
	$userinfo = $db->query_first_slave("SELECT userid FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards WHERE rewardid = " . $db->sql_prepare($vbulletin->GPC['rewardid']));
	
	// Remove the reward
	$db->query_write("
		DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`
		WHERE `rewardid` = " . $db->sql_prepare($vbulletin->GPC['rewardid'])
	);
	$db->query_write("
		DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_awardbonus`
		WHERE `rewardid` = " . $db->sql_prepare($vbulletin->GPC['rewardid'])
	);
	
	// Rebuild the rewards cache
	VBACTIVITY::build_rewards_cache($userinfo);
	
	define('CP_REDIRECT', 'vbactivity.php?do=award&action=user');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_reward'], $vbphrase['dbtech_vbactivity_deleted']);
}

// #############################################################################
if ($_REQUEST['action'] == 'requests')
{
	$entry_by_type = array();
	$requests_q = $db->query_read_slave("
		SELECT medalrequest.*, user.username, recipient.username AS recipient
		FROM " . TABLE_PREFIX . "dbtech_vbactivity_medalrequest AS medalrequest
		LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = medalrequest.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS recipient ON(recipient.userid = medalrequest.targetuserid)
		WHERE status = '0'
		ORDER BY dateline ASC		
	");
	while ($requests_r = $db->fetch_array($requests_q))
	{
		$type = ($requests_r['targetuserid'] == $requests_r['userid'] ? 'request' : 'nominate');
		$entry_by_type["$type"]["$requests_r[medalrequestid]"] = $requests_r;
	}
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_vbactivity_medal'];
	$headings[] = $vbphrase['user'];
	$headings[] = $vbphrase['dbtech_vbactivity_target_user'];
	$headings[] = $vbphrase['date'];
	$headings[] = $vbphrase['reason'];
	$headings[] = $vbphrase['dbtech_vbactivity_moderation_action'];
	
	
	// Beginning of form
	print_cp_header($vbphrase['dbtech_vbactivity_medal_moderation']);
	print_form_header('vbactivity', 'award');
	construct_hidden_code('action', 'updaterequests');
	print_table_header($vbphrase['dbtech_vbactivity_medal_moderation'], count($headings));
	print_description_row($vbphrase['dbtech_vbactivity_medal_moderation_descr'], false, count($headings));
	
	foreach ($entry_by_type as $typeid => $medalrequestids)
	{
		print_description_row($vbphrase["dbtech_vbactivity_medal_{$typeid}"], false, count($headings), 'optiontitle');
		print_cells_row($headings, 0, 'thead');
		
		foreach ((array)$medalrequestids as $medalrequestid => $medalrequest)
		{
			// Table data
			$cell = array();
			$cell[] = VBACTIVITY::$cache['medal']["$medalrequest[medalid]"]['title'];
			$cell[] = $medalrequest['username'];
			$cell[] = $medalrequest['recipient'];
			$cell[] = vbdate($vbulletin->options['timeformat'] . ', ' . $vbulletin->options['dateformat'], $medalrequest['dateline']);
			$cell[] = nl2br($medalrequest['reason']);
			$cell[] = '
				<label for="dw_undecided_' . $medalrequestid . '"><input type="radio" name="dowhat[' . $medalrequestid . ']" value="0" id="dw_undecided_' . $medalrequestid . '" checked="checked" />' . $vbphrase['dbtech_vbactivity_undecided'] . '</label>
				<label for="dw_approve_' . $medalrequestid . '"><input type="radio" name="dowhat[' . $medalrequestid . ']" value="1" id="dw_approve_' . $medalrequestid . '" />' . $vbphrase['dbtech_vbactivity_approve'] . '</label>
				<label for="dw_reject_' . $medalrequestid . '"><input type="radio" name="dowhat[' . $medalrequestid . ']" value="2" id="dw_reject_' . $medalrequestid . '" />' . $vbphrase['dbtech_vbactivity_reject'] . '</label>
			';
			
			// Print the data
			print_cells_row($cell, 0, 0, -5, 'top', 1, 1);
		}
	}
	// End of area
	print_submit_row($vbphrase['dbtech_vbactivity_perform_moderation'], false, count($headings));
}


// #############################################################################
if ($_POST['action'] == 'updaterequests')
{
	// Grab stuff
	$vbulletin->input->clean_array_gpc('p', array(
		'dowhat'		=> TYPE_ARRAY_UINT,
	));
	
	$SQL 					= array();
	$SQL['approve'] 		= array();
	$SQL['reject'] 			= array();
	$SQL['rejectreason'] 	= array();
	$decrement 				= 0;
	
	foreach ($vbulletin->GPC['dowhat'] as $entryid => $action)
	{
		switch ($action)
		{
			case 1:
				$SQL['approve'][] = $entryid;
				$decrement++;
				break;
			
			case 2:
				$SQL['reject'][] = $entryid;
				$decrement++;
				break;
		}
	}
	
	if (count($SQL['approve']))
	{
		$requests_q = $db->query_read_slave("
			SELECT medalrequest.*, user.username, recipient.username AS recipient
			FROM " . TABLE_PREFIX . "dbtech_vbactivity_medalrequest AS medalrequest
			LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = medalrequest.userid)
			LEFT JOIN " . TABLE_PREFIX . "user AS recipient ON(recipient.userid = medalrequest.targetuserid)
			WHERE `medalrequestid` IN(" . implode(',', $SQL['approve']) . ")
		");
		while ($requests_r = $db->fetch_array($requests_q))
		{
			$reason = ($requests_r['userid'] == $requests_r['targetuserid'] ? 
				$vbphrase['dbtech_vbactivity_award_request_accepted'] :
				construct_phrase($vbphrase['dbtech_vbactivity_award_nomination_from_x_accepted'], 
					$requests_r['username'])
			);
			
			// Grant the reward
			$userinfo = array('userid' => $requests_r['targetuserid']);
			VBACTIVITY::add_reward('medal', $requests_r['medalid'], $userinfo, $reason);
			
			// Rebuild the cache
			VBACTIVITY::build_rewards_cache($userinfo);		
		}
		
		// Update the database
		$db->query_write("
			UPDATE `" . TABLE_PREFIX . "dbtech_vbactivity_medalrequest`
			SET 
				`status` = '1'
			WHERE `medalrequestid` IN(" . implode(',', $SQL['approve']) . ")
		");
	}
	
	if (count($SQL['reject']))
	{
		// Update the database
		$db->query_write("
			UPDATE `" . TABLE_PREFIX . "dbtech_vbactivity_medalrequest`
			SET 
				`status` = '2'
			WHERE `medalrequestid` IN(" . implode(',', $SQL['reject']) . ")
		");
	}
	
	if ($decrement)
	{
		$db->query_write("
			UPDATE `" . TABLE_PREFIX . "user`
			SET dbtech_vbactivity_medalmoderatecount = IF(dbtech_vbactivity_medalmoderatecount >= $decrement, (dbtech_vbactivity_medalmoderatecount - $decrement), 0)
		");
	}
	
	define('CP_REDIRECT', 'vbactivity.php?do=award&action=requests');
	print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_medal_moderation'], $vbphrase['dbtech_vbactivity_edited']);
}
} // End "Can Grant Awards"

print_cp_footer();