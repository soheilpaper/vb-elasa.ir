<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// Add to the navbits
$navbits['vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contests'] = $vbphrase['dbtech_vbactivity_contests'];

// draw cp nav bar
VBACTIVITY::setNavClass('contests');

// #############################################################################
if ($_REQUEST['action'] == 'contests' OR $_REQUEST['action'] == 'previous' OR empty($_REQUEST['action']))
{
	$contestbits = array(
		'display' 	=> array(),
		'json' 		=> array(),
	);
	$displayContest = false;
	$upcomingContest = false;
	foreach ((array)VBACTIVITY::$cache['contest'] as $contestid => $contest)
	{
		if (!$contest['is_public'])
		{
			// Not a public contest
			continue;
		}
		
		if ($contest['start'] <= TIMENOW AND $contest['end'] > TIMENOW)
		{
			if ($_REQUEST['action'] == 'previous')
			{
				// Only showing previous contests
				continue;
			}
			$contest['category'] = 'ongoing';
		}
		else if ($contest['start'] > TIMENOW)
		{
			if ($_REQUEST['action'] == 'previous')
			{
				// Only showing previous contests
				continue;
			}
			$contest['category'] = 'upcoming';
		}
		else
		{
			if ($_REQUEST['action'] == 'contests' OR empty($_REQUEST['action']))
			{
				// Only showing current or upcoming contests
				continue;
			}
			$contest['category'] = 'previous';
		}
		
		// Get standing
		$standing = VBACTIVITY::getContestStanding($contestid, false, $contest['numusers']);

		// Prepare some important variables
		$contest['datestart'] 		= vbdate($vbulletin->options['logdateformat'], $contest['start']);
		$contest['dateend'] 		= vbdate($vbulletin->options['logdateformat'], $contest['end']);
		$contest['contesttype'] 	= VBACTIVITY::$cache['contesttype'][$contest['contesttypeid']]['title_translated'];
		$contest['description'] 	= nl2br($contest['description']);
		$contest['winnerdisplay'] 	= $standing['winnerList'] ? $standing['winnerList'] : $vbphrase['n_a'];	
		$contest['banner'] 			= ($contest['banner'] ? ($contest['link'] ? '<a href="' . $contest['link'] . '">' : '') . '
				<img src="' . $contest['banner'] . '" alt="" border="0" />' .
				($contest['link'] ? '</a>' : '') : '');
		$contest['link'] 			= ($contest['link'] ? '<a href="' . $contest['link'] . '">' . $contest['title_translated'] . '</a>' : $contest['title_translated']);
		$contest['target'] 			= ($contest['target'] ? construct_phrase($vbphrase['dbtech_vbactivity_x_points'], $contest['target']) : $vbphrase['n_a']);
		
		if ($contest['winnerdisplay'] AND !VBACTIVITY::$cachedUsers[$vbulletin->userinfo['userid']] AND $vbulletin->options['dbtech_vbactivity_extended_contest_standing'])
		{
			$standing = array();
			
			// Fetch all the points entries we need
			$pointsList = VBACTIVITY::$db->fetchAll('
				SELECT userid, points
				FROM $dbtech_vbactivity_contestprogress
				WHERE contestid = ?
			', array(
				$contest['contestid'],
			));
			foreach ($pointsList as $info)
			{
				$standing[$info['userid']] = $info['points'];
			}
			arsort($standing, SORT_NUMERIC);
			
			$i = 1;
			foreach ($standing as $userid => $points)
			{
				if ($userid == $vbulletin->userinfo['userid'])
				{
					$user = fetch_userinfo($userid, 2);
				
					// Grab markup username
					fetch_musername($user);
					
					// grab avatar from userinfo
					fetch_avatar_from_userinfo($user, true);
					
					$user['place'] = $i;	
					$user['points'] = $points;	

					$templater = vB_Template::create('dbtech_vbactivity_contests_winner');
						$templater->register('winner', $user);
					$contest['standing'] = '<br />' . $templater->render() . '<br />';
					
					break;
				}
				$i++;
			}
		}
		
		$contest['standing'] .= (($vbulletin->options['dbtech_vbactivity_enable_standing'] AND $contest['winnerdisplay'] != $vbphrase['n_a']) ? '<br /><a href="vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contests&amp;action=standing&amp;contestid=' . $contest['contestid'] . '">[' . $vbphrase['dbtech_vbactivity_view_complete_standing'] . ']</a>' : '');
		
		if ($contest['show_criteria'])
		{
			foreach ((array)$contest['excludedcriteria'] as $typeid)
			{
				// Shorthand
				$type = VBACTIVITY::$cache['type'][$typeid];
				
				// Show excluded criteria
				$contest['criteriadisplay'] .= '<li>' . ($vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] ? $vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] : "dbtech_vbactivity_condition_per{$type[typename]}") . '</li>';
			}
			
			foreach ((array)$contest['excludedforums'] as $forumid)
			{
				if (!$forum = $vbulletin->forumcache[$forumid])
				{
					// skip this
					continue;
				}

				$fperms =& $vbulletin->userinfo['forumpermissions'][$forumid];
		
				if (!((int)$fperms & (int)$vbulletin->bf_ugp_forumpermissions['canview']) OR !((int)$fperms & (int)$vbulletin->bf_ugp_forumpermissions['canviewthreads']) OR !verify_forum_password($forumid, $forum['password'], false))
				{
					// No perms
					continue;
				}

				// Show excluded forums
				$contest['forumdisplay'] .= '<li>' . $forum['title'] . '</li>';
			}
		}
		
		// Ensure this is set
		$contest['criteriadisplay'] = ($contest['criteriadisplay'] 	? '<ul>' . $contest['criteriadisplay'] 	. '</ul>' : $vbphrase['n_a']);
		$contest['forumdisplay'] 	= ($contest['forumdisplay'] 	? '<ul>' . $contest['forumdisplay'] 	. '</ul>' : $vbphrase['n_a']);
	
		// Get prize info
		foreach ((array)$contest['prizes'] as $place => $prize)
		{
			if (!$prize AND !intval($contest['prizes2'][$place]))
			{
				// Skip this
				continue;
			}
			
			// Add the prize info
			$templater = vB_Template::create('dbtech_vbactivity_contests_prize');
				$templater->register('place', 	$place);
				$templater->register('award', 	VBACTIVITY::$cache['medal'][$prize]);
				$templater->register('points', 	intval($contest['prizes2'][$place]));
			$contest['prizedisplay'] .= $templater->render();
		}
		
		$contest['prizedisplay'] = ($contest['prizedisplay'] ? $contest['prizedisplay'] : $vbphrase['n_a']);
		
		if ($contest['category'] == 'ongoing' OR $_REQUEST['action'] == 'previous')
		{
			// Store display contest
			$displayContest = ($displayContest ? $displayContest : $contest);
		}

		if ($contest['category'] == 'upcoming')
		{
			// Store display contest
			$upcomingContest = ($upcomingContest ? $upcomingContest : $contest);
		}
		
		// Store JSON
		$contestbits['json'][$contest['contestid']] = $contest;
		
		$templater = vB_Template::create('dbtech_vbactivity_contests_bit');
			$templater->register('contest', $contest);
		$contestbits['display'][$contest['category']] .= $templater->render();
	}

	if ($upcomingContest AND !$displayContest)
	{
		// Ensure we have at least one display contest
		$displayContest = $upcomingContest;
	}
	
	// Create the archive template
	$templater = vB_Template::create('dbtech_vbactivity_contests');
		$templater->register('pagenav', 	$pagenav);
		$templater->register('pagenumber', 	$pagenumber);
		$templater->register('perpage', 	$perpage);
		$templater->register('contests', 	$contestbits['display']);
		$templater->register('contestJSON', VBACTIVITY::encodeJSON($contestbits['json']));
		$templater->register('contest', 	$displayContest);
		$templater->register('permissions', VBACTIVITY::$permissions);
	$HTML = $templater->render();
}

// #############################################################################
if ($_REQUEST['action'] == 'standing')
{
	if (!$vbulletin->options['dbtech_vbactivity_enable_standing'])
	{
		// Non-existing contest
		eval(standard_error(fetch_error('dbtech_vbactivity_error_x', $vbphrase['dbtech_vbactivity_invalid_action'])));
	}
	
	// Grab contest id
	$contestid = $vbulletin->input->clean_gpc('r', 'contestid', TYPE_UINT);
	
	if (!$contest = VBACTIVITY::$cache['contest'][$contestid])
	{
		// Non-existing contest
		eval(standard_error(fetch_error('dbtech_vbactivity_error_x', $vbphrase['dbtech_vbactivity_invalid_action'])));
	}
	
	if ($contest['end'] > TIMENOW AND !$contest['is_public'])
	{
		// Non-existing contest
		eval(standard_error(fetch_error('dbtech_vbactivity_error_x', $vbphrase['dbtech_vbactivity_contest_in_progress_and_not_public'])));
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'  	=> TYPE_UINT,
		'perpage'     	=> TYPE_UINT,
		'sorttype'    	=> TYPE_STR,
		'sortorder'   	=> TYPE_STR,
	));
	
	// Ensure there's no errors or out of bounds with the page variables
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$pagenumber = $vbulletin->GPC['pagenumber']; // $vbulletin->options['dbtech_vbshout_maxshouts']
	$perpage = (!$vbulletin->GPC['perpage'] OR $vbulletin->GPC['perpage'] > 25) ? 25 : $vbulletin->GPC['perpage'];
	
	// Count number of entries
	$users = VBACTIVITY::$db->fetchOne('
		SELECT COUNT(*)
		FROM $dbtech_vbactivity_contestprogress
		WHERE contestid = ?
	', array(
		$contest['contestid'],
	));
	
	// Ensure every result is as it should be
	sanitize_pageresults($users, $pagenumber, $perpage);
	
	// Find out where to start
	$startat = ($pagenumber - 1) * $perpage;
	
	// Constructs the page navigation
	$pagenav = construct_page_nav(
		$pagenumber,
		$perpage,
		$users,
		'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . "do=contests",
		"&amp;action=standing&amp;contestid=$contestid&amp;perpage=$perpage"
	);
	
	// This contest is ongoing
	$winnerList = VBACTIVITY::$db->fetchAll('
		SELECT user.*, contestprogress.points
		' . ($vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : '') . '		
		FROM $dbtech_vbactivity_contestprogress AS contestprogress
		LEFT JOIN $user AS user ON(user.userid = contestprogress.userid)
		' . ($vbulletin->options['avatarenabled'] ? '
		LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid)
		LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)
		' : '') . '
		WHERE contestprogress.contestid = ?
		ORDER BY contestprogress.points DESC
		LIMIT :startAt, :perPage
	', array(
		$contest['contestid'],
		':startAt' 		=> $startat,
		':perPage' 		=> $perpage,
	));
	
	$SQL = array();
	foreach ($winnerList as $winner)
	{
		// Store this
		$SQL[] = $winner['userid'];
	}
	
	$pointsDisplay = array();
	if ($vbulletin->options['dbtech_vbactivity_extended_contest_info'])
	{
		// Fetch all the points entries we need
		$pointsList = VBACTIVITY::$db->fetchAll('
			SELECT *
			FROM $dbtech_vbactivity_pointslog
			WHERE dateline >= ?
				AND dateline <= ?
				AND typeid NOT :criteriaList
				AND forumid NOT :forumList
				AND userid :userList
		', array(
			$contest['start'],
			$contest['end'],
			':criteriaList' => VBACTIVITY::$db->queryList(array_merge((array)$contest['excludedcriteria'], array(VBACTIVITY::fetch_type('contestprize')))),
			':forumList' 	=> VBACTIVITY::$db->queryList((array)$contest['excludedforums']),
			':userList' 	=> VBACTIVITY::$db->queryList((array)$SQL),
		));
		foreach ($pointsList as $info)
		{
			$pointsDisplay[$info['userid']][$info['typeid']] += $info['points'];
		}
	}
	
	if (!function_exists('fetch_avatar_from_userinfo'))
	{
		// Get the avatar function
		require_once(DIR . '/includes/functions_user.php');
	}
	
	$winners = '';
	$place = $startat;
	foreach ($winnerList as $winner)
	{
		// Store the place we're at
		$winner['place'] = ++$place;
		
		// Grab markup username
		fetch_musername($winner);
		
		// grab avatar from userinfo
		fetch_avatar_from_userinfo($winner, true);
		
		$winner['breakdown'] = '';
		foreach ((array)$pointsDisplay[$winner['userid']] as $typeid => $points)
		{
			// Shorthand
			$type = VBACTIVITY::$cache['type'][$typeid];
			
			$winner['breakdown'] .= '<li>' . ($vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] ? $vbphrase["dbtech_vbactivity_condition_per{$type[typename]}"] : "dbtech_vbactivity_condition_per{$type[typename]}") . ': ' . construct_phrase($vbphrase['dbtech_vbactivity_x_points'], $points) . '</li>';
		}
		
		$templater = vB_Template::create('dbtech_vbactivity_contests_standing_bit');
			$templater->register('winner', $winner);
		$winners .= $templater->render();
	}
	
	// Add to the navbits
	$navbits[] = $pagetitle = construct_phrase($vbphrase['dbtech_vbactivity_viewing_current_standing_x'], $contest['title_translated']);

	// Create the archive template
	$templater = vB_Template::create('dbtech_vbactivity_contests_standing');
		$templater->register('pagenav', 	$pagenav);
		$templater->register('pagenumber', 	$pagenumber);
		$templater->register('perpage', 	$perpage);
		$templater->register('winners', 	$winners);
	$HTML = $templater->render();
}

// #############################################################################
if ($_REQUEST['action'] == 'manage')
{
	if (!VBACTIVITY::$permissions['contest'])
	{
		print_no_permission();
	}

	$contesttypes = '';
	$contesttypes2 = array();
	foreach ((array)VBACTIVITY::$cache['contesttype'] as $contesttypeid => $contesttype)
	{
		if (!$contesttype['active'])
		{
			// Skip this contest type
			continue;
		}

		// Store the contesttype
		$templater = vB_Template::create('option');
			$templater->register('optionvalue', $contesttypeid);
			$templater->register('optiontitle', $contesttype['title_translated']);
		$contesttypes .= $templater->render();

		// Store the contesttype
		$contesttypes2[$contesttypeid] = $contesttype['title_translated'];
	}

	$contests_by_status = array();
	foreach ((array)VBACTIVITY::$cache['contest'] as $contestid => $contest)
	{
		// Index by upcoming, ongoing or previous
		$contests_by_status[($contest['end'] > TIMENOW ? ($contest['start'] > TIMENOW ? '1' : '2') : '3')][$contestid] = $contest;
	}
	ksort($contests_by_status);

	$contestbits = '';
	foreach ($contests_by_status as $status => $contests)
	{
		$contestList = '';
		foreach ($contests as $contestid => $contest)
		{
			// Grab some important arrays
			$prizes 	= (is_array($contest['prizes']) 	? $contest['prizes'] 	: array());
			$prizes2 	= (is_array($contest['prizes2']) 	? $contest['prizes2'] 	: array());
			$winners 	= (is_array($contest['winners']) 	? $contest['winners'] 	: array());
			
			// Query winner info
			$contest['winarr'] = $contest['prizearr'] = array();
			foreach ($prizes as $place => $prize)
			{
				// Store medal info
				$medal = VBACTIVITY::$cache['medal'][$prizes[$place]];

				// Add the winner info
				$contest['prizearr'][] = $place . '. ' . ($medal ? 
					'<a href="vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=award&amp;action=user&amp;medalid=' . $medal['medalid'] . '">' . $medal['title_translated'] . '</a>' : 
					$vbphrase['n_a']
				) . ' / ' . construct_phrase($vbphrase['dbtech_vbactivity_x_points'], intval($prizes2[$place]));

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
				$contest['winarr'][] = $place . '. ' . $userinfo['username'] . ' (' . construct_phrase($vbphrase['dbtech_vbactivity_x_points'], $winner['points']) . ')';
			}

			// Table data
			$contest['contesttype'] = $contesttypes2[$contest['contesttypeid']];
			$contest['description'] = nl2br($contest['description']);
			$contest['start'] 		= vbdate($vbulletin->options['logdateformat'], $contest['start']);
			$contest['end'] 		= vbdate($vbulletin->options['logdateformat'], $contest['end']);
			$contest['prizearr'] 	= ($contest['prizearr'] 	? implode('<br />', $contest['prizearr']) 	: $vbphrase['n_a']);
			$contest['winarr'] 		= ($contest['winarr'] 		? implode('<br />', $contest['winarr']) 	: $vbphrase['n_a']);

			// Create the archive template
			$templater = vB_Template::create('dbtech_vbactivity_contests_manage_bit');
				$templater->register('contest', 	$contest);
			$contestList .= $templater->render();
		}

		$templater = vB_Template::create('dbtech_vbactivity_contests_manage_wrapper');
			$templater->register('title', 		$vbphrase['dbtech_vbactivity_conteststatus_' . $status]);
			$templater->register('contestList', $contestList);
		$contestbits .= $templater->render();
	}

	// Add to the navbits
	$navbits[] = $pagetitle = $vbphrase['dbtech_vbactivity_contest_management'];

	// Create the archive template
	$templater = vB_Template::create('dbtech_vbactivity_contests_manage');
		$templater->register('contesttypes', 	$contesttypes);
		$templater->register('contestbits', 	$contestbits);
	$HTML = $templater->render();
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
		$medals[$medalid] = $medal['title_translated'];
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
		$navbits[] = $pagetitle = strip_tags(construct_phrase($vbphrase['dbtech_vbactivity_editing_x_y'], $vbphrase['dbtech_vbactivity_contest'], $contest['title_translated']));
	}
	else
	{
		// Add
		$navbits[] = $pagetitle = $vbphrase['dbtech_vbactivity_add_new_contest'];

		// Set some defaults
		$contest = $defaults;
	}
	
	if (!$contest['numwinners'] OR !$contest['contesttypeid'])
	{
		// Missing categories
		eval(standard_error(fetch_error('dbtech_vbactivity_error_x', $vbphrase['dbtech_vbactivity_invalid_action'])));
	}

	// Used for the template
	$options = array(
		'recurring' 	=> '',
		'winner_notifs' => '',
	);
	
	// Grab the functions
	require_once(DIR . '/dbtech/vbactivity/includes/functions.php');

	$forums = construct_contest_forum_chooser_options();

	// Detect winners
	$winners = (is_array($contest['winners']) ? $contest['winners'] : array());
	
	// Load the contest type info
	$contestObj = VBACTIVITY::initContest($contest);

	foreach ($timespans as $optionvalue => $optiontitle)
	{
		// Store the contesttype
		$templater = vB_Template::create('option');
			$templater->register('optionvalue', $optionvalue);
			$templater->register('optiontitle', $optiontitle);
		if ($optionvalue == $contest['recurring'])
		{
			// This is the correct value
			$templater->register('optionselected', ' selected="selected"');
		}
		$options['recurring'] .= $templater->render();
	}

	foreach ($notifs as $optionvalue => $optiontitle)
	{
		// Store the contesttype
		$templater = vB_Template::create('option');
			$templater->register('optionvalue', $optionvalue);
			$templater->register('optiontitle', $optiontitle);
		if ($optionvalue == $contest['winner_notifs'])
		{
			// This is the correct value
			$templater->register('optionselected', ' selected="selected"');
		}
		$options['winner_notifs'] .= $templater->render();
	}

	// Start/end dates
	$options['start'] 	= VBACTIVITY::timeRow('contest[start]', $contest['start']);
	$options['end'] 	= VBACTIVITY::timeRow('contest[end]', 	$contest['end']);

	if (method_exists($contestObj, 'frontEndForm'))
	{
		// Item info
		$extraForm = $contestObj->frontEndForm();
	}

	$prizebits = '';
	for ($i = 1; $i <= $contest['numwinners']; $i++)
	{
		if ($winners[$i])
		{
			$tmp = VBACTIVITY::$cache['medal'][$contest['prizes'][$i]['medal']]['title_translated'];
			$tmp = ($tmp ? $tmp : $vbphrase['n_a']);
		}
		else
		{
			$options['medal'] = '';
			foreach ($medals as $optionvalue => $optiontitle)
			{
				// Store the contesttype
				$templater = vB_Template::create('option');
					$templater->register('optionvalue', $optionvalue);
					$templater->register('optiontitle', $optiontitle);
				if ($optionvalue == $contest['prizes'][$i])
				{
					// This is the correct value
					$templater->register('optionselected', ' selected="selected"');
				}
				$options['medal'] .= $templater->render();
			}
		}

		$templater = vB_Template::create('dbtech_vbactivity_contests_manage_prizebit');
			$templater->register('i', 				$i);
			$templater->register('hasWinner', 		$winners[$i]);
			$templater->register('prize', 			$contest['prizes'][$i]);
			$templater->register('prize2', 			$contest['prizes2'][$i]);
			$templater->register('displayprize', 	$tmp);
			$templater->register('displayprize2', 	construct_phrase($vbphrase['dbtech_vbactivity_x_points'], intval($contest['prizes2'][$i])));
			$templater->register('options', 		$options);
		$prizebits .= $templater->render();
	}

	$criteriabits 	= construct_checkbox_options('contest[excludedcriteria][]',	$criteria, 		(array)$contest['excludedcriteria'], 	false);
	$forumbits 		= construct_checkbox_options('contest[excludedforums][]',	$forums, 		(array)$contest['excludedforums'], 		false);

	// Create the archive template
	$templater = vB_Template::create('dbtech_vbactivity_contests_manage_modify');
		$templater->register('pagetitle', 		$pagetitle);
		$templater->register('contest', 		$contest);
		$templater->register('options', 		$options);
		$templater->register('extraForm', 		$extraForm);
		$templater->register('prizebits', 		$prizebits);
		$templater->register('criteriabits', 	$criteriabits);
		$templater->register('forumbits', 		$forumbits);
	$HTML = $templater->render();
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
			eval(standard_error(fetch_error('dbtech_vbactivity_invalid_x', $vbphrase['dbtech_vbactivity_contest'], $vbulletin->GPC['contestid'])));
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
		
	$vbulletin->url = 'vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=contests&amp;action=manage';
	eval(print_standard_redirect('redirect_dbtech_vbactivity_contest_saved'));	
}