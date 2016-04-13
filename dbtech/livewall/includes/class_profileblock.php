<?php
/**
* Thanks Block for Advanced Post Thanks / Like
*
* @package Advanced Post Thanks / Like
*/
class vB_ProfileBlock_LiveWall_UserWall extends vB_ProfileBlock
{
	/**
	* The name of the template to be used for the block
	*
	* @var string
	*/
	var $template_name = 'dbtech_livewall_memberinfo_block_userwall';
	
	var $nowrap = true;
	
	var $skip_privacy_check = true;
	
	/**
	* Variables to automatically prepare
	*
	* @var array
	*/
	var $auto_prepare = array();

	/**
	* Sets/Fetches the default options for the block
	*
	*/
	function fetch_default_options()
	{
		$this->option_defaults = array(
			'pagenumber' => 1,
			'perpage'    => 25,
		);
	}

	/**
	* Whether to return an empty wrapper if there is no content in the blocks
	*
	* @return bool
	*/
	function confirm_empty_wrap()
	{
		return false;
	}

	/**
	* Whether or not the block is enabled
	*
	* @return bool
	*/
	function block_is_enabled($id)
	{
		return (LIVEWALL::$permissions['canviewuserwall']);
	}

	/**
	* Prepare any data needed for the output
	*
	* @param	string	The id of the block
	* @param	array	Options specific to the block
	*/
	function prepare_output($id = '', $options = array())
	{
		global $show, $vbphrase;
		
		if (intval($this->registry->versionnumber) == 3)
		{
			$this->nowrap = false;
		}
		
		// Fetch the data
		$data = LIVEWALL::fetchContentTypeData(-1, $this->profile->userinfo['userid']);
		
		if (!function_exists('fetch_avatar_url'))
		{
			// Get the avatar function
			require_once(DIR . '/includes/functions_user.php');
		}
		
		// Store the entries
		$entries = '';
			
		// Parse the BBCode that we generated
		require_once(DIR . '/includes/class_bbcode.php');	
		$parser = new vB_BbCodeParser($this->registry, fetch_tag_list());
	
		// Hacks
		$this->registry->options['allowbbimagecode'] = $this->registry->options['dbtech_livewall_images'];
	
		foreach ($data as $info)
		{
			// Shorthand
			$contenttype = LIVEWALL::$cache['contenttype'][$info['contenttypeid']];
			
			// Init the object
			$contentTypeObj = LIVEWALL::initContentType($contenttype);
			
			// Do some array modifications
			$info['phrase'] = $contentTypeObj->constructPhrase($info);
			$info['actiondate'] = vbdate($this->registry->options['dateformat'], $info['dateline'], true);
			$info['actiontime'] = vbdate($this->registry->options['timeformat'], $info['dateline']);
			$info['display'] = 'block';
			if ($this->registry->options['dbtech_livewall_inlinecomments'])
			{		
				$info['commentcount'] = count(LIVEWALL::$allComments[$info['contenttypeid']][$info['contentid']]);
			}
			
			if ($this->registry->options['dbtech_livewall_enable_previews'] AND $contenttype['preview'])
			{
				// We're doing some form of preview trimming
				$info['preview'] = $parser->parse(fetch_trimmed_title($info['pagetext'], $contenttype['preview']), 'nonforum');
			}
			
			// Install avatar info
			fetch_avatar_from_userinfo($info);
		
			foreach ((array)LIVEWALL::$allComments[$info['contenttypeid']][$info['contentid']] as $info2)
			{
				// Install avatar info
				fetch_avatar_from_userinfo($info2);
				
				$info2['actiondate'] 	= vbdate($this->registry->options['dateformat'], $info2['dateline'], true);
				$info2['actiontime'] 	= vbdate($this->registry->options['timeformat'], $info2['dateline']);
				$info2['message'] 		= $parser->parse($info2['message'], 'nonforum');
				
				$templater = vB_Template::create('dbtech_livewall_comment');
					$templater->register('entry', 	$info2);
				$info['comments'] .= $templater->render();			
			}
			
			$templater = vB_Template::create('dbtech_livewall_entry');
				$templater->register('entry', 	$info);
			$entries .= $templater->render();	
		}
		unset($parser);
		
		$this->block_data['resultbits'] = $entries;	
		
		// Make sure we can check the options
		//$this->block_data['options'] = $options;
	}
}