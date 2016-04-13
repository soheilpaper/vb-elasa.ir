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

// ############################### start display options ###############################
if ($_REQUEST['action'] == 'main' OR empty($_REQUEST['action']))
{
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		print_no_permission();
	}
	
	$vbulletin->input->clean_gpc('r', 'userid', TYPE_UINT);
	
	if (!LIVEWALL::$permissions['canviewuserwall'])
	{
		// Always null this out
		$vbulletin->GPC['userid'] = 0;
	}
	
	if ($vbulletin->GPC['userid'])
	{
		$userinfo = ($vbulletin->GPC['userid'] == $vbulletin->userinfo['userid'] ? $vbulletin->userinfo : fetch_userinfo($vbulletin->GPC['userid']));
		if ($userinfo)
		{
			// Valid user
			$navbits['livewall.php?' . $vbulletin->session->vars['sessionurl'] . 'u=' . $vbulletin->GPC['userid']] = $pagetitle = construct_phrase($vbphrase['dbtech_livewall_viewing_user_wall_x'], $userinfo['username']);
		}
		else
		{
			// Not valid user
			$vbulletin->GPC['userid'] = 0;
		}
	}
	
	/*DBTECH_PRO_START*/
	if ($_REQUEST['type'] == 'recenthistory')
	{
		// We're on the Recent History page
		$vbulletin->options['dbtech_livewall_perpage'] 		= $vbulletin->options['dbtech_livewall_recent_entries'];
		$vbulletin->options['dbtech_livewall_refreshrate'] 	= 0;
	}
	/*DBTECH_PRO_END*/

	// Fetch the data
	$data = LIVEWALL::fetchContentTypeData(-1, $vbulletin->GPC['userid']);
	
	if (!function_exists('fetch_avatar_url'))
	{
		// Get the avatar function
		require_once(DIR . '/includes/functions_user.php');
	}
	
	// Store the entries
	$entries = '';
		
	// Parse the BBCode that we generated
	require_once(DIR . '/includes/class_bbcode.php');	
	$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	// Hacks
	$vbulletin->options['allowbbimagecode'] = $vbulletin->options['dbtech_livewall_images'];

	foreach ($data as $info)
	{
		// Shorthand
		$contenttype = LIVEWALL::$cache['contenttype'][$info['contenttypeid']];
		
		// Init the object
		$contentTypeObj = LIVEWALL::initContentType($contenttype);
		
		// Do some array modifications
		$info['phrase'] = $contentTypeObj->constructPhrase($info);
		$info['actiondate'] = vbdate($vbulletin->options['dateformat'], $info['dateline'], true);
		$info['actiontime'] = vbdate($vbulletin->options['timeformat'], $info['dateline']);
		$info['display'] = 'block';
		if ($vbulletin->options['dbtech_livewall_inlinecomments'])
		{		
			$info['commentcount'] = count(LIVEWALL::$allComments[$info['contenttypeid']][$info['contentid']]);
		}
		
		if ($vbulletin->options['dbtech_livewall_enable_previews'] AND $contenttype['preview'])
		{
			// We're doing some form of preview trimming
			$info['preview'] = $parser->parse(fetch_trimmed_title($info['pagetext'], $contenttype['preview']), 'nonforum');
		}
		
		$show['deletestatus'] = false;
		if ($vbulletin->userinfo['userid'] AND $info['contenttypeid'] == 'statusupdate')
		{
			// Whether we can delete comments
			$show['deletestatus'] = ($info['userid'] == $vbulletin->userinfo['userid'] ?
				LIVEWALL::$permissions['canmanagestatus'] :
				LIVEWALL::$permissions['canmanageothersstatuses']
			);
		}
		
		// Install avatar info
		fetch_avatar_from_userinfo($info);
	
		foreach ((array)LIVEWALL::$allComments[$info['contenttypeid']][$info['contentid']] as $info2)
		{
			// Install avatar info
			fetch_avatar_from_userinfo($info2);
			
			$info2['actiondate'] 	= vbdate($vbulletin->options['dateformat'], $info2['dateline'], true);
			$info2['actiontime'] 	= vbdate($vbulletin->options['timeformat'], $info2['dateline']);
			$info2['message'] 		= $parser->parse($info2['message'], 'nonforum');
			
			$show['deletecomment'] = false;
			if ($vbulletin->userinfo['userid'])
			{
				// Whether we can delete comments
				$show['deletecomment'] = ($info2['userid'] == $vbulletin->userinfo['userid'] ?
					LIVEWALL::$permissions['candeletecomments'] :
					LIVEWALL::$permissions['candeleteotherscomments']
				);
			}
			
			$templater = vB_Template::create('dbtech_livewall_comment');
				$templater->register('entry', 	$info2);
			$info['comments'] .= $templater->render();			
		}
		
		$templater = vB_Template::create('dbtech_livewall_entry');
			$templater->register('entry', 	$info);
		$entries .= $templater->render();	
	}
	unset($parser);
	
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
					'perpage' 			=> $vbulletin->options['dbtech_livewall_perpage'],
					'refresh' 			=> $vbulletin->options['dbtech_livewall_refreshrate'],
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
		$templater->register('entries', 	$entries);
	$HTML = $templater->render();	
}
?>