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

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// Navigation bits
$navbits['livewall.php' . $vbulletin->session->vars['sessionurl_q']] = $pagetitle = $vbphrase['dbtech_livewall_livewall'];

if (!$vbulletin->userinfo['userid'])
{
	print_no_permission();
}

// ############################### start display options ###############################
if ($_REQUEST['action'] == 'favourites' OR empty($_REQUEST['action']))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'pagenumber'  	=> TYPE_UINT,
		'perpage'     	=> TYPE_UINT,
	));
	
	// Hacks
	$vbulletin->options['dbtech_livewall_status_maxlength'] = 0;
	$show['hidecommententry'] = true;
	
	// Ensure there's no errors or out of bounds with the page variables
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$pagenumber = $vbulletin->GPC['pagenumber'];
	$perpage = (!$vbulletin->GPC['perpage'] ? 
		$vbulletin->options['dbtech_livewall_perpage'] : (
		$vbulletin->GPC['perpage'] > $vbulletin->options['dbtech_livewall_perpage'] ? 
			$vbulletin->options['dbtech_livewall_perpage'] : 
			$vbulletin->GPC['perpage']
		)
	);	
	
	// Count number of entries
	$total = LIVEWALL::$db->fetchOne('
		SELECT COUNT(*)
		FROM $dbtech_livewall_favourite AS favourite
		WHERE userid = ?
	', array(
		$vbulletin->userinfo['userid'],
	));
	
	// Ensure every result is as it should be
	sanitize_pageresults($total, $pagenumber, $perpage);
	
	// Find out where to start
	$startat = ($pagenumber - 1) * $perpage;
	
	// Constructs the page navigation
	$pagenav = construct_page_nav(
		$pagenumber,
		$perpage,
		$total,
		'livewall.php?' . $vbulletin->session->vars['sessionurl'] . "do=favourites",
		"&amp;perpage=$perpage"
	);
	
	// Parse the BBCode that we generated
	require_once(DIR . '/includes/class_bbcode.php');	
	$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());	
	
	// Hacks
	$vbulletin->options['allowbbimagecode'] = $vbulletin->options['dbtech_livewall_images'];
	
	if (!function_exists('fetch_avatar_url'))
	{
		// Get the avatar function
		require_once(DIR . '/includes/functions_user.php');
	}	
	
	$entries = '';
	
	// Fetch entries
	$favourites = LIVEWALL::$db->fetchAll('
		SELECT *
		FROM $dbtech_livewall_favourite AS favourite
		WHERE userid = ?
		ORDER BY dateline DESC
		LIMIT :startAt, :perPage
	', array(
		$vbulletin->userinfo['userid'],
		':startAt' 			=> $startat,
		':perPage' 			=> $perpage,
	));
	foreach ($favourites as $favourite)
	{
		// Fetch the data
		$data = LIVEWALL::fetchCommentData($favourite['contenttypeid'], $favourite['contentid'], -1);
		
		// Shorthand
		$contenttype = LIVEWALL::$cache['contenttype'][$favourite['contenttypeid']];
		
		// Init the object
		$contentTypeObj = LIVEWALL::initContentType($contenttype);
		
		// Do some array modifications
		$data['content']['phrase'] = $contentTypeObj->constructPhrase($data['content']);
		$data['content']['actiondate'] = vbdate($vbulletin->options['dateformat'], $data['content']['dateline'], true);
		$data['content']['actiontime'] = vbdate($vbulletin->options['timeformat'], $data['content']['dateline']);
		$data['content']['display'] = 'block';
		$data['content']['commentcount'] = count($data['comments']);
		
		if ($vbulletin->options['dbtech_livewall_enable_previews'] AND $contenttype['preview'])
		{
			// We're doing some form of preview trimming
			$data['content']['preview'] = $parser->parse(fetch_trimmed_title($data['content']['pagetext'], $contenttype['preview']), 'nonforum');
		}
		
		// Install avatar info
		fetch_avatar_from_userinfo($data['content']);
		
		foreach ($data['comments'] as $info)
		{
			// Do some array modifications
			$info['actiondate'] = vbdate($vbulletin->options['dateformat'], $info['dateline'], true);
			$info['actiontime'] = vbdate($vbulletin->options['timeformat'], $info['dateline']);
			$info['display'] = 'block';
			$info['message'] = $parser->parse($info['message'], 'nonforum');
			
			// Install avatar info
			fetch_avatar_from_userinfo($info);
			
			// Whether we can delete comments
			$show['deletecomment'] = false;
			
			$templater = vB_Template::create('dbtech_livewall_comment');
				$templater->register('entry', 	$info);
			$data['content']['comments'] .= $templater->render();			
		}
		
		$templater = vB_Template::create('dbtech_livewall_entry');
			$templater->register('pagetitle', 	$pagetitle);
			$templater->register('entry', 		$data['content']);
		$entries .= $templater->render();
	}
	unset($parser);				
	
	$navbits[] = $pagetitle = $vbphrase['dbtech_livewall_viewing_favourites'];
	
	// Begin list of JS phrases
	$jsphrases = array(
		'dbtech_livewall_fetching_entries_in_x_seconds' => $vbphrase['dbtech_livewall_fetching_entries_in_x_seconds'],
		'dbtech_livewall_really_delete_comment' 		=> $vbphrase['dbtech_livewall_really_delete_comment'],
		'dbtech_livewall_really_delete_status' 			=> $vbphrase['dbtech_livewall_really_delete_status']
	);
	
	// Escape them
	LIVEWALL::jsEscapeString($jsphrases);
	
	$escapedJsPhrases = '';
	foreach ($jsphrases as $varname => $value)
	{
		// Replace phrases with safe values
		$escapedJsPhrases .= "vbphrase['$varname'] = \"$value\"\n\t\t\t\t\t";
	}
	
	// We can see at least 1 instance
	$footer = LIVEWALL::js($escapedJsPhrases . '
			var liveWall = {
				lastIds : ' . LIVEWALL::encodeJSON(LIVEWALL::$lastIds) . ',
				userId : \'' . intval($vbulletin->GPC['userid']) . '\',
				liveOptions : ' . LIVEWALL::encodeJSON(array(
					'perpage' 			=> 0,
					'refresh' 			=> 0,
					'type' 				=> 'entries',
					'status_maxchars' 	=> $vbulletin->options['dbtech_livewall_status_maxlength'],
					'status_delay' 		=> $vbulletin->options['dbtech_livewall_status_delay'],
					'comment_maxchars' 	=> $vbulletin->options['dbtech_livewall_comment_maxlength'],
					'comment_delay' 	=> $vbulletin->options['dbtech_livewall_comment_delay'],
					'sidebar' 			=> 0
				)) . '
			};
	', false, false) . $footer;	
	
	$templater = vB_Template::create('dbtech_livewall_main');
		$templater->register('pagetitle', 	$pagetitle);
		$templater->register('pagenav', 	$pagenav);
		$templater->register('pagenumber', 	$pagenumber);
		$templater->register('perpage', 	$perpage);		
		$templater->register('entries', 	$entries);
	$HTML = $templater->render();	
}


?>