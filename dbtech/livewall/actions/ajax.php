<?php

// ############################### start display options ###############################
if ($_REQUEST['action'] == 'entries')
{
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	$vbulletin->input->clean_array_gpc('r', array(
		'lastids' 	=> TYPE_ARRAY_UINT,
		'allids' 	=> TYPE_ARRAY,
		'userid' 	=> TYPE_UINT,
		'sidebar' 	=> TYPE_BOOL,
	));
	
	if (!LIVEWALL::$permissions['canviewuserwall'])
	{
		// Always null this out
		$vbulletin->GPC['userid'] = 0;
	}	
	if ($vbulletin->GPC['userid'])
	{
		$userinfo = ($vbulletin->GPC['userid'] == $vbulletin->userinfo['userid'] ? $vbulletin->userinfo : fetch_userinfo($vbulletin->GPC['userid']));
		if (!$userinfo)
		{
			// Not valid user
			$vbulletin->GPC['userid'] = 0;
		}
	}
	
	if (!function_exists('fetch_avatar_url'))
	{
		// Get the avatar function
		require_once(DIR . '/includes/functions_user.php');
	}
	
	// Store the entries
	$entries = array();
	
	// Fetch the data
	$data = LIVEWALL::fetchContentTypeData($vbulletin->GPC['lastids'], $vbulletin->GPC['userid'], -1, $vbulletin->GPC['allids'], $vbulletin->GPC['sidebar']);
	
	// Parse the BBCode that we generated
	require_once(DIR . '/includes/class_bbcode.php');	
	$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
	
	// Hacks
	$vbulletin->options['allowbbimagecode'] = $vbulletin->options['dbtech_livewall_images' . ($vbulletin->GPC['sidebar'] ? '_sidebar' : '')];
	
	$comments = array();	
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
		$info['display'] = 'none';
		if ($vbulletin->options['dbtech_livewall_inlinecomments'])
		{
			$info['commentcount'] = count(LIVEWALL::$allComments[$info['contenttypeid']][$info['contentid']]);
		}
		
		if ($vbulletin->options['dbtech_livewall_enable_previews'] AND $contenttype['preview' . ($vbulletin->GPC['sidebar'] ? '_sidebar' : '')])
		{
			// We're doing some form of preview trimming
			$info['preview'] = $parser->parse(fetch_trimmed_title($info['pagetext'], $contenttype['preview' . ($vbulletin->GPC['sidebar'] ? '_sidebar' : '')]), 'nonforum');
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
			
			// We've already done these
			unset(LIVEWALL::$allComments[$info['contenttypeid']][$info['contentid']]);
		}		
		
		$templaterr = vB_Template::create('dbtech_livewall_' . ($vbulletin->GPC['sidebar'] ? 'block_' : '') . 'entry');
			$templaterr->register('entry', 	$info);
		$entries[] = $templaterr->render();
	}
	
	foreach ((array)LIVEWALL::$allComments as $contenttypeid => $arr)
	{
		foreach ($arr as $contentid => $arr2)
		{
			foreach ($arr2 as $commentid => $info2)
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
				$comments[$contenttypeid][$contentid][] = $templater->render();
			}
		}
	}		
	unset($parser);
	
	LIVEWALL::outputJSON(array(
		'lastids' 	=> LIVEWALL::$lastIds,
		/*DBTECH_PRO_START*/
		'allids' 	=> LIVEWALL::$allIds,
		'comments' 	=> $comments,
		/*DBTECH_PRO_END*/
		'entries' 	=> $entries
	));
}

/*DBTECH_PRO_START*/
// ############################### start display options ###############################
if ($_REQUEST['action'] == 'comments')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'contenttypeid' => TYPE_STR,
		'contentid' 	=> TYPE_UINT,
		'lastids' 		=> TYPE_ARRAY_UINT,
	));
	
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	if (!$vbulletin->options['dbtech_livewall_comment_maxlength'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'commentsoff',
		));
	}	
	
	if (!function_exists('fetch_avatar_url'))
	{
		// Get the avatar function
		require_once(DIR . '/includes/functions_user.php');
	}
	
	// Store the entries
	$entries = array();
	
	// Fetch the data
	$data = LIVEWALL::fetchCommentData($vbulletin->GPC['contenttypeid'], $vbulletin->GPC['contentid'], $vbulletin->GPC['lastids'][$vbulletin->GPC['contenttypeid']]);
	
	// Parse the BBCode that we generated
	require_once(DIR . '/includes/class_bbcode.php');	
	$parser = new vB_BbCodeParser($vbulletin, fetch_tag_list());
	
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
			$show['deletecomment'] = ($info['userid'] == $vbulletin->userinfo['userid'] ?
				LIVEWALL::$permissions['candeletecomments'] :
				LIVEWALL::$permissions['candeleteotherscomments']
			);
		}
		
		$templater = vB_Template::create('dbtech_livewall_comment');
			$templater->register('entry', 	$info);
		$entries[] = $templater->render();	
	}
	unset($parser);
	
	LIVEWALL::outputJSON(array(
		'lastids' 	=> LIVEWALL::$lastIds,
		'entries' 	=> $entries
	));
}
/*DBTECH_PRO_END*/

// ############################### start display options ###############################
if ($_POST['action'] == 'savestatus')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'message' 	=> TYPE_STR,
	));
	
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	if (!$vbulletin->options['dbtech_livewall_status_maxlength'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'statusoff',
		));
	}	
	
	if ((TIMENOW - $vbulletin->options['dbtech_livewall_status_delay']) < LIVEWALL::$db->fetchOne('SELECT dateline FROM $dbtech_livewall_status WHERE userid = ?', array($vbulletin->userinfo['userid'])))
	{
		// Just add a dummy error, this shouldn't really happen
		LIVEWALL::outputJSON(array(
			'error' => 'toosoon',
		));
	}
	
	LIVEWALL::$db->insert('dbtech_livewall_status', array(
		'userid' 	=> $vbulletin->userinfo['userid'],
		'pagetext' 	=> str_replace(array("\n"), '', trim(convert_urlencoded_unicode(urldecode($vbulletin->GPC['message'])))),
		'dateline' 	=> TIMENOW
	));
	
	LIVEWALL::outputJSON(array(
		'dorefresh' => true,
	));
}

// ############################### start display options ###############################
if ($_POST['action'] == 'deletestatus')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'statusid' => TYPE_UINT,
	));
	
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	if (!$existing = LIVEWALL::$db->fetchRow('SELECT * FROM $dbtech_livewall_status WHERE statusid = ?', array($vbulletin->GPC['statusid'])))
	{
		// Just add a dummy error, this shouldn't really happen
		LIVEWALL::outputJSON(array(
			'error' => 'noexist',
		));
	}
	
	if (!($existing['userid'] == $vbulletin->userinfo['userid'] ?
		LIVEWALL::$permissions['canmanagestatus'] :
		LIVEWALL::$permissions['canmanageothersstatuses']
	))
	{
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	// Delete the status
	LIVEWALL::$db->delete('dbtech_livewall_status', array($vbulletin->GPC['statusid']), 'WHERE statusid = ?');
	
	LIVEWALL::outputJSON(array(
		'statusid' => $vbulletin->GPC['statusid'],
		'dorefresh' => true,
	));
}

/*DBTECH_PRO_START*/
// ############################### start display options ###############################
if ($_POST['action'] == 'savecomments')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'contenttypeid' => TYPE_STR,
		'contentid' 	=> TYPE_UINT,
		'message' 		=> TYPE_STR,
	));
	
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	if (!$vbulletin->options['dbtech_livewall_comment_maxlength'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'commentsoff',
		));
	}	
	
	if ((TIMENOW - $vbulletin->options['dbtech_livewall_comment_delay']) < LIVEWALL::$db->fetchOne('SELECT dateline FROM $dbtech_livewall_comment WHERE userid = ?', array($vbulletin->userinfo['userid'])))
	{
		// Just add a dummy error, this shouldn't really happen
		LIVEWALL::outputJSON(array(
			'error' => 'toosoon',
		));
	}
	
	LIVEWALL::$db->insert('dbtech_livewall_comment', array(
		'userid' 		=> $vbulletin->userinfo['userid'],
		'contenttypeid' => $vbulletin->GPC['contenttypeid'],
		'contentid' 	=> $vbulletin->GPC['contentid'],
		'message' 		=> str_replace(array("\n"), '', trim(convert_urlencoded_unicode(urldecode($vbulletin->GPC['message'])))),
		'dateline' 		=> TIMENOW
	));
	
	LIVEWALL::outputJSON(array(
		'dorefresh' => true,
	));
}

// ############################### start display options ###############################
if ($_POST['action'] == 'deletecomment')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'commentid' 			=> TYPE_UINT,
		'contenttypeid' 		=> TYPE_STR,
		'contentid' 			=> TYPE_UINT,
	));
	
	if (!LIVEWALL::$permissions['canview'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	if (!$existing = LIVEWALL::$db->fetchRow('SELECT * FROM $dbtech_livewall_comment WHERE commentid = ?', array($vbulletin->GPC['commentid'])))
	{
		// Just add a dummy error, this shouldn't really happen
		LIVEWALL::outputJSON(array(
			'error' => 'noexist',
		));
	}
	
	if (!($existing['userid'] == $vbulletin->userinfo['userid'] ?
		LIVEWALL::$permissions['candeletecomments'] :
		LIVEWALL::$permissions['candeleteotherscomments']
	))
	{
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	// Delete the comment
	LIVEWALL::$db->delete('dbtech_livewall_comment', array($vbulletin->GPC['commentid']), 'WHERE commentid = ?');
	
	LIVEWALL::outputJSON(array(
		'commentid' => $vbulletin->GPC['commentid'],
		'dorefresh' => true,
	));
}

// ############################### start display options ###############################
if ($_POST['action'] == 'togglefavourite')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'contenttypeid' 		=> TYPE_STR,
		'contentid' 			=> TYPE_UINT,
	));
	
	if (!LIVEWALL::$permissions['canview'] OR !$vbulletin->userinfo['userid'])
	{
		// Always null this out
		LIVEWALL::outputJSON(array(
			'error' => 'noperms',
		));
	}
	
	if ($existing = LIVEWALL::$db->fetchRow('
		SELECT userid
		FROM $dbtech_livewall_favourite
		WHERE contenttypeid = ?
			AND contentid = ?
			AND userid = ?
		', array(
			$vbulletin->GPC['contenttypeid'],
			$vbulletin->GPC['contentid'],
			$vbulletin->userinfo['userid']
		))
	)
	{
		// Toggle favourite off
		LIVEWALL::$db->delete('dbtech_livewall_favourite', array($vbulletin->GPC['contenttypeid'], $vbulletin->GPC['contentid'], $vbulletin->userinfo['userid']), 'WHERE contenttypeid = ? AND contentid = ? AND userid = ?');
	}
	else
	{
		// Insert new favourite
		LIVEWALL::$db->insert('dbtech_livewall_favourite', array(
			'contenttypeid' => $vbulletin->GPC['contenttypeid'],
			'contentid' 	=> $vbulletin->GPC['contentid'],
			'userid' 		=> $vbulletin->userinfo['userid'],
			'dateline' 		=> TIMENOW
		));
	}
	
	LIVEWALL::outputJSON(array());
}
/*DBTECH_PRO_END*/
?>