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
if ($_REQUEST['action'] == 'viewcomments' OR empty($_REQUEST['action']))
{
	$vbulletin->input->clean_array_gpc('r', array(
		'contenttypeid' => TYPE_STR,
		'contentid' 	=> TYPE_UINT,
	));
	
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		print_no_permission();
	}
	
	if (!$vbulletin->options['dbtech_livewall_comment_maxlength'])
	{
		// Always null this out
		print_no_permission();
	}
	
	// Fetch the data
	$data = LIVEWALL::fetchCommentData($vbulletin->GPC['contenttypeid'], $vbulletin->GPC['contentid'], -1);
	
	if (!function_exists('fetch_avatar_url'))
	{
		// Get the avatar function
		require_once(DIR . '/includes/functions_user.php');
	}
	
	// Parse the BBCode that we generated
	require_once(DIR . '/includes/class_bbcode.php');	
	$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());

	// Hacks
	$vbulletin->options['allowbbimagecode'] = $vbulletin->options['dbtech_livewall_images'];

	// Shorthand
	$contenttype = LIVEWALL::$cache['contenttype'][$vbulletin->GPC['contenttypeid']];
	
	// Init the object
	$contentTypeObj = LIVEWALL::initContentType($contenttype);
	
	// Do some array modifications
	$data['content']['phrase'] = $contentTypeObj->constructPhrase($data['content']);
	$data['content']['actiondate'] = vbdate($vbulletin->options['dateformat'], $data['content']['dateline'], true);
	$data['content']['actiontime'] = vbdate($vbulletin->options['timeformat'], $data['content']['dateline']);
	$data['content']['display'] = (intval($vbulletin->versionnumber) == 3 ? '' : 'block');
	
	if ($vbulletin->options['dbtech_livewall_enable_previews'] AND $contenttype['preview'])
	{
		// We're doing some form of preview trimming
		$data['content']['preview'] = $parser->parse(fetch_trimmed_title($data['content']['pagetext'], $contenttype['preview']), 'nonforum');
	}
	
	// Install avatar info
	fetch_avatar_from_userinfo($data['content']);
	
	$templater = vB_Template::create('dbtech_livewall_entry_comments');
		$templater->register('entry', 	$data['content']);
	$entry = $templater->render();	

	// Store the entries
	$comments = '';
	
	foreach ($data['comments'] as $info)
	{
		// Do some array modifications
		$info['actiondate'] = vbdate($vbulletin->options['dateformat'], $info['dateline'], true);
		$info['actiontime'] = vbdate($vbulletin->options['timeformat'], $info['dateline']);
		$info['display'] = 'block';
		$info['message'] = $parser->parse($info['message'], 'nonforum');
		
		// Install avatar info
		fetch_avatar_from_userinfo($info);
		
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
			$templater->register('entry', 	$info);
		$comments .= $templater->render();	
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
					'perpage' 			=> -1,
					'refresh' 			=> $vbulletin->options['dbtech_livewall_refreshrate'],
					'type' 				=> 'comments',
					'status_maxchars' 	=> $vbulletin->options['dbtech_livewall_status_maxlength'],
					'status_delay' 		=> $vbulletin->options['dbtech_livewall_status_delay'],
					'comment_maxchars' 	=> $vbulletin->options['dbtech_livewall_comment_maxlength'],
					'comment_delay' 	=> $vbulletin->options['dbtech_livewall_comment_delay'],
					'sidebar' 			=> 0,
					'contenttypeid' 	=> $vbulletin->GPC['contenttypeid'],
					'contentid' 		=> $vbulletin->GPC['contentid']
				)) . '
			};
	', false, false) . $footer;
	
	$navbits[] = $pagetitle = $vbphrase['dbtech_livewall_viewing_comments'];
	
	$templater = vB_Template::create('dbtech_livewall_comments');
		$templater->register('pagetitle', 	$pagetitle);
		$templater->register('entry', 		$data['content']);
		$templater->register('origEntry', 	$entry);
		$templater->register('comments', 	$comments);
	$HTML = $templater->render();	
}
?>