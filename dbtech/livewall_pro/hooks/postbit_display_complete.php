<?php
if (THIS_SCRIPT == 'showthread' AND (!defined('VB_API') OR VB_API !== true))
{
	$contenttypeid = ($this->thread['firstpostid'] == $this->post['postid'] ? 'threads' : 'posts');
	$contentid = ($this->thread['firstpostid'] == $this->post['postid'] ? $this->thread['threadid'] : $this->post['postid']);
	
	do
	{
		if (!$contenttype = LIVEWALL::$cache['contenttype'][$contenttypeid])
		{
			// Wrong content type
			break;
		}
		
		// Initialise the content type
		$contentTypeObj = LIVEWALL::initContentType($contenttype);
		
		if (!$contentTypeObj->preCheck() OR !$contenttype['enabled'])
		{
			// Either inactive or we can't access it
			break;
		}
		
		if (!function_exists('fetch_avatar_url'))
		{
			// Get the avatar function
			require_once(DIR . '/includes/functions_user.php');
		}
		
		$entries = '';
		foreach ((array)LIVEWALL::$allIds[$contenttypeid][$contentid] as $info2)
		{
			// Install avatar info
			fetch_avatar_from_userinfo($info2);
			
			$info2['actiondate'] 	= vbdate($this->registry->options['dateformat'], $info2['dateline'], true);
			$info2['actiontime'] 	= vbdate($this->registry->options['timeformat'], $info2['dateline']);
			$info2['message'] 		= $this->bbcode_parser->parse($info2['message'], 'nonforum');
			$info2['display'] 		= 'block';
			
			$templater = vB_Template::create('dbtech_livewall_comment');
				$templater->register('entry', 	$info2);
			$entries .= $templater->render();	
		}
		
		if (intval($this->registry->versionnumber) == 3)
		{
			$postid = $this->post['postid'];
			
			global $vbcollapse;
			if (!isset($vbcollapse["collapseobj_dbtech_livewall_postcomment_$postid"]))
			{
				$vbcollapse["collapseobj_dbtech_livewall_postcomment_$postid"] = 'display:none;';
			}
			$collapseobj_postcommentid =& $vbcollapse["collapseobj_dbtech_livewall_postcomment_$postid"];
			$collapseimg_postcommentid =& $vbcollapse["collapseimg_forumbit_$postid"];		
		}
		
		$templater = vB_Template::create('dbtech_livewall_post_comments');
			$templater->register('contenttypeid', 	$contenttypeid);
			$templater->register('contentid', 		$contentid);
			$templater->register('commentcount', 	count(LIVEWALL::$allIds[$contenttypeid][$contentid]));
			$templater->register('entries', 		$entries);
			$templater->register('postid', 			$this->post['postid']);
			$templater->register('collapseobj_postcommentid', 		$collapseobj_postcommentid);
			$templater->register('collapseimg_postcommentid', 		$collapseimg_postcommentid);
		$post['message'] .= '<br /><br />' . $templater->render();				
	}
	while (false);
	
}
?>