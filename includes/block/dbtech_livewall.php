<?php
class vB_BlockType_Dbtech_livewall extends vB_BlockType
{
	/**
	 * The Productid that this block type belongs to
	 * Set to '' means that it belongs to vBulletin forum
	 *
	 * @var string
	 */
	protected $productid = 'dbtech_livewall';
	
	/**
	 * The block settings
	 * It uses the same data structure as forum settings table
	 * e.g.:
	 * <code>
	 * $settings = array(
	 *     'varname' => array(
	 *         'defaultvalue' => 0,
	 *         'optioncode'   => 'yesno'
	 *         'displayorder' => 1,
	 *         'datatype'     => 'boolean'
	 *     ),
	 * );
	 * </code>
	 * @see print_setting_row()
	 *
	 * @var string
	 */
	protected $settings = array(
		/*
		'dbtech_livewall_contenttypeids' => array(
			'defaultvalue' => -1,
			'optioncode'   => 'selectmulti:eval
$options = vB_BlockType_Dbtech_livewall::contenttypeIdChooser(fetch_phrase("dbtech_livewall_all_contenttypes", "dbtech_livewall"));',
			'displayorder' => 5,
			'datatype'     => 'arrayinteger'
		),
		*/
		'dbtech_livewall_limit' => array(
			'defaultvalue' => 5,
			'displayorder' => 9001,
			'datatype'     => 'integer'
		),
	);
	
	public static function contenttypeIdChooser($topname = null)
	{
		$selectoptions = array();

		if ($topname)
		{
			$selectoptions['-1'] = $topname;
		}
		
		foreach ((array)LIVEWALL::$cache['contenttype'] as $contenttypeid => $contenttype)
		{
			if (!$contenttype['active'])
			{
				// Skip inactive contenttypes
				continue;
			}
			
			// Add to select options
			$selectoptions[$contenttypeid] = $contenttype['title'];
		}

		return $selectoptions;
	}

	public function getData() {}
	
	public function getHTML($data = false)
	{
		if (!class_exists('LIVEWALL'))
		{
			// Not displaying any results
			return '';
		}		
		
		global $footer, $vbphrase, $headinclude, $show;
		
		if (!LIVEWALL::$permissions['canview'])
		{
			return false;
		}
		
		// Grab the data
		$data = LIVEWALL::fetchContentTypeData(-1, 0, $this->config['dbtech_livewall_limit'], array(), true);
		
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
		$this->registry->options['allowbbimagecode'] = $this->registry->options['dbtech_livewall_images_sidebar'];
		
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
			if ($this->registry->options['dbtech_livewall_inlinecomments_sidebar'])
			{		
				$info['commentcount'] = count(LIVEWALL::$allComments[$info['contenttypeid']][$info['contentid']]);
			}
			
			if ($this->registry->options['dbtech_livewall_enable_previews'] AND $contenttype['preview_sidebar'])
			{
				// We're doing some form of preview trimming
				$info['preview'] = $parser->parse(fetch_trimmed_title($info['pagetext'], $contenttype['preview_sidebar']), 'nonforum');
			}	
			
			$show['deletestatus'] = false;
			if ($this->registry->userinfo['userid'] AND $info['contenttypeid'] == 'statusupdate')
			{
				// Whether we can delete comments
				$show['deletestatus'] = ($info['userid'] == $this->registry->userinfo['userid'] ?
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
				
				$info2['actiondate'] 	= vbdate($this->registry->options['dateformat'], $info2['dateline'], true);
				$info2['actiontime'] 	= vbdate($this->registry->options['timeformat'], $info2['dateline']);
				$info2['message'] 		= $parser->parse($info2['message'], 'nonforum');
				
				$show['deletecomment'] = false;
				if ($vbulletin->userinfo['userid'])
				{
					// Whether we can delete comments
					$show['deletecomment'] = ($info2['userid'] == $this->registry->userinfo['userid'] ?
						LIVEWALL::$permissions['candeletecomments'] :
						LIVEWALL::$permissions['candeleteotherscomments']
					);
				}
				
				$templater = vB_Template::create('dbtech_livewall_comment');
					$templater->register('entry', 	$info2);
				$info['comments'] .= $templater->render();
			}			

			$templater = vB_Template::create('dbtech_livewall_block_entry');
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
					userId : \'' . intval($this->registry->GPC['userid']) . '\',
					liveOptions : ' . LIVEWALL::encodeJSON(array(
						'perpage' 			=> $this->config['dbtech_livewall_limit'],
						'refresh' 			=> $this->registry->options['dbtech_livewall_refreshrate'],
						'type' 				=> 'entries',
						'status_maxchars' 	=> $this->registry->options['dbtech_livewall_status_maxlength'],
						'status_delay' 		=> $this->registry->options['dbtech_livewall_status_delay'],
						'comment_maxchars' 	=> $this->registry->options['dbtech_livewall_comment_maxlength'],
						'comment_delay' 	=> $this->registry->options['dbtech_livewall_comment_delay'],
						'sidebar' 			=> 1,
					)) . '
				};
		', false, false) . $footer;
		
		// Sneak the CSS into the headinclude
		$templater = vB_Template::create('dbtech_livewall_css');
			$templater->register('jQueryVersion', 	LIVEWALL::$jQueryVersion);
			$templater->register('versionnumber', 	LIVEWALL::$versionnumber);
		$headinclude .= $templater->render() . '<script type="text/javascript" src="' . REQ_PROTOCOL . '://ajax.googleapis.com/ajax/libs/jquery/' . LIVEWALL::$jQueryVersion . '/jquery.min.js"></script>' . LIVEWALL::js('', true, false);

		$templater = vB_Template::create('dbtech_livewall_block_entries');
			$templater->register('blockinfo', 	$this->blockinfo);
			$templater->register('entries', 	$entries);
		return $templater->render();
	}

	/**
	 * Generates a hash used for block caching.
	 * If the block output depends on permissions,
	 * ensure it's unique either per-user or for all
	 * users with similar permissions
	 *
	 * @return string 	The hash
	 */
	public function getHash()
	{
		$context = new vB_Context('forumblock' ,
		array(
			'blockid' 		=> $this->blockinfo['blockid'],
			'permissions' 	=> $this->userinfo['forumpermissions'],
			'ignorelist' 	=> $this->userinfo['ignorelist'],
			THIS_SCRIPT)
		);

		return strval($context);
	}
}