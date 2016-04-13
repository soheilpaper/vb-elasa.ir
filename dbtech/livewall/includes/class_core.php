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

// #############################################################################
// LiveWall functionality class

/**
* Handles everything to do with LiveWall.
*
* @package	LiveWall
* @version	$ $Rev$ $
* @date		$ $Date$ $
*/
class LIVEWALL
{
	/**
	* Version info
	*
	* @public	mixed
	*/	
	public static $jQueryVersion 	= '1.7.2';	
	public static $version 			= '1.2.3';
	public static $versionnumber	= 123;
	
	/**
	* The vBulletin registry object
	*
	* @private	vB_Registry
	*/	
	protected static $vbulletin 	= NULL;
	
	/**
	* The database object
	*
	* @private	Thanks_Database
	*/	
	public static $db 				= NULL;
	
	/**
	* The vBulletin registry object
	*
	* @private	vB_Registry
	*/	
	protected static $prefix 		= 'dbtech_';
	
	/**
	* The vBulletin registry object
	*
	* @private	vB_Registry
	*/	
	protected static $bitfieldgroup	= array(
		'livewallpermissions'
	);
	
	/**
	* Array of permissions to be returned
	*
	* @public	array
	*/	
	public static $permissions 		= NULL;
	
	/**
	* Array of cached items
	*
	* @public	array
	*/		
	public static $cache			= array();
	
	/**
	* Whether we've called the DM fetcher
	*
	* @public	boolean
	*/		
	protected static $called		= false;
	
	/**
	* Array of cached items
	*
	* @public	array
	*/		
	public static $unserialize		= array(
		'contenttype' => array(
			'permissions',
			'code',
		),
	);
	
	/**
	* Whether we have the pro version or not
	*
	* @public	boolean
	*/		
	public static $isPro			= false;

	/**
	* Array of cached contenttypes
	*
	* @public	array
	*/		
	public static $contenttypes		= array();

	/**
	* Array of latest IDs fetched
	*
	* @public	array
	*/		
	public static $lastIds			= array();

	/**
	* Array of all IDs fetched
	*
	* @public	array
	*/		
	public static $allIds			= array();

	/**
	* Array of all comments fetched
	*
	* @public	array
	*/		
	public static $allComments		= array();


	
	/**
	* Does important checking before anything else should be going on
	*
	* @param	vB_Registry	Registry object
	*/
	public static function init($vbulletin)
	{
		// Check if the vBulletin Registry is an object
		if (!is_object($vbulletin))
		{
			// Something went wrong here I think
			trigger_error("Registry object is not an object", E_USER_ERROR);
		}
		
		// Set registry
		self::$vbulletin =& $vbulletin;
		
		// Set database object
		self::$db = new LiveWall_Database($vbulletin->db);
		
		// Set permissions shorthand
		self::_getPermissions();
		
		// What permissions to override
		$override = array(
			'canview',
		);
		
		foreach ($override as $permname)
		{
			// Override various permissions
			self::$permissions[$permname] = (self::$permissions['ismanager'] ? 1 : self::$permissions[$permname]);
		}
		
		foreach (self::$unserialize as $cachetype => $keys)
		{
			foreach ((array)self::$cache[$cachetype] as $id => $arr)
			{
				foreach ($keys as $key)
				{
					// Do unserialize
					self::$cache[$cachetype][$id][$key] = @unserialize($arr[$key]);
					self::$cache[$cachetype][$id][$key] = (is_array(self::$cache[$cachetype][$id][$key]) ? self::$cache[$cachetype][$id][$key] : array());
				}
			}
		}
		
		// Set pro version
		/*DBTECH_PRO_START*/
		self::$isPro = true;
		/*DBTECH_PRO_END*/
	}
		
	/**
	* Check if we have permissions to perform an action
	*
	* @param	array		User info
	* @param	array		Permissions info
	*/		
	public static function checkPermissions(&$user, $permissions, $bitIndex = 'default')
	{
		if (!$user['usergroupid'] OR (!isset($user['membergroupids']) AND $user['userid']))
		{
			// Ensure we have this
			$user = fetch_userinfo($user['userid']);
		}
		
		if (!is_array($user['permissions']))
		{
			// Ensure we have the perms
			cache_permissions($user);
		}
		
		$ugs = fetch_membergroupids_array($user);		
		if (!$ugs[0])
		{
			// Hardcode guests
			$ugs[0] = 1;
		}
		
		$bit = $bitIndex;
		
		//self::$vbulletin->usergroupcache
		foreach ($ugs as $usergroupid)
		{
			$value = $permissions[$usergroupid][$bit];
			$value = (isset($value) ? $value : 0);
			
			switch ($value)
			{
				case 1:
					// Allow
					return true;
					break;
			}
		}
		
		// We didn't make it
		return false;
	}
	
	/**
	* Class factory. This is used for instantiating the extended classes.
	*
	* @param	string			The type of the class to be called (user, forum etc.)
	* @param	vB_Registry		An instance of the vB_Registry object.
	* @param	integer			One of the ERRTYPE_x constants
	*
	* @return	vB_DataManager	An instance of the desired class
	*/
	public static function &initDataManager($classtype, &$registry, $errtype = ERRTYPE_STANDARD)
	{
		if (empty(self::$called))
		{
			// include the abstract base class
			require_once(DIR . '/includes/class_dm.php');
			self::$called = true;
		}
	
		if (preg_match('#^\w+$#', $classtype))
		{
			if (file_exists(DIR . '/dbtech/livewall/includes/class_dm_' . strtolower($classtype) . '.php'))
			{
				// Lite
				require_once(DIR . '/dbtech/livewall/includes/class_dm_' . strtolower($classtype) . '.php');
			}
			else
			{
				// Pro
				require_once(DIR . '/dbtech/livewall_pro/includes/class_dm_' . strtolower($classtype) . '.php');
			}
	
			$classname = 'LiveWall_DataManager_' . $classtype;
			$object = new $classname($registry, $errtype);
	
			return $object;
		}
	}
	
	/**
	* JS class fetcher for AdminCP
	*
	* @param	string	The JS file name or the code
	* @param	boolean	Whether it's a file or actual JS code
	*/
	public static function js($js = '', $file = true, $echo = true)
	{
		$output = '';
		if ($file)
		{
			$output = '<script type="text/javascript" src="' . self::$vbulletin->options['bburl'] . '/dbtech/livewall/clientscript/livewall' . $js . '.js?v=' . self::$versionnumber . '"></script>';
		}
		else
		{
			$output = "
				<script type=\"text/javascript\">
					<!--
					$js
					// -->
				</script>
			";
		}
		
		if ($echo)
		{
			echo $output;
		}
		else
		{
			return $output;
		}
	}

	/**
	* Returns a 'depth mark' for use in prefixing items that need to show depth in a hierarchy
	*
	* @param	integer	Depth of item (0 = no depth, 3 = third level depth)
	* @param	string	Character or string to repeat $depth times to build the depth mark
	* @param	string	Existing depth mark to append to
	*
	* @return	string
	*/
	function getDepthMark($depth, $depthchar, $depthmark = '')
	{
		for ($i = 0; $i < $depth; $i++)
		{
			$depthmark .= $depthchar;
		}
		return $depthmark;
	}

	/**
	* Breaks down a difference (in seconds) into its days / hours / minutes / seconds components.
	*
	* @param	integer	Difference (in seconds)
	*
	* @return	array
	*/
	function getTimeBreakdown($difference)
	{
		
		$breakdown = array();
		
		// Set days
		$breakdown['days'] = intval($difference / 86400);
		$difference -= ($breakdown['days'] * 86400);
		
		// Set hours
		$breakdown['hours'] = intval($difference / 3600);
		$difference -= ($breakdown['hours'] * 3600);
		
		// Set minutes
		$breakdown['minutes'] = intval($difference / 60);
		$difference -= ($breakdown['minutes'] * 60);
		
		// Set seconds
		$breakdown['seconds'] = intval($difference);
		
		return $breakdown;
	}
	
	/**
	* Quick Method of building the CPNav Template
	*
	* @param	string	The selected item in the CPNav
	*/	
	public static function setNavClass($selectedcell = 'main')
	{
		global $navclass;
	
		$cells = array(
			'main',
		);
	
		//($hook = vBulletinHook::fetch_hook('usercp_nav_start')) ? eval($hook) : false;
		
		// set the class for each cell/group
		$navclass = array();
		foreach ($cells AS $cellname)
		{
			$navclass[$cellname] = (intval(self::$vbulletin->versionnumber) == 3 ? 'alt2' : 'inactive');
		}
		$navclass[$selectedcell] = (intval(self::$vbulletin->versionnumber) == 3 ? 'alt1' : 'active');
		
		//($hook = vBulletinHook::fetch_hook('usercp_nav_complete')) ? eval($hook) : false;
	}
	
	/**
	* Escapes a string and makes it JavaScript-safe
	*
	* @param	mixed	The string or array to make JS-safe
	*/	
	public static function jsEscapeString(&$arr)
	{
		$find = array(
			"\r\n",
			"\n",
			"\t",
			'"'
		);
		
		$replace = array(
			'\r\n',
			'\n',
			'\t',
			'\"',
		);
		
		$arr = str_replace($find, $replace, $arr);
	}
	
	/**
	* Encodes a string as a JSON object (consistent behaviour instead of relying on PHP built-in functions)
	*
	* @param	mixed	The string or array to encode
	* @param	boolean	(Optional) Whether this is an associative array
	* @param	boolean	(Optional) Whether we should escape the string or if they have already been escaped
	*/	
	public static function encodeJSON($arr, $assoc = true, $doescape = true)
	{
		if ($doescape)
		{
			self::jsEscapeString($arr);
		}
		if (!$assoc)
		{
			// Not associative, simple return
			return '{"' . implode('","', $arr) . '"}';
		}
		
		$content = array();
		foreach ((array)$arr as $key => $val)
		{
			if (is_array($val))
			{
				// Recursion, definition: see recursion
				$val = self::encodeJSON($val);
				$content[] = '"' . $key . '":' . $val . '';
			}
			else
			{
				$content[] = '"' . $key . '":"' . $val . '"';
			}
		}
		
		return '{' . implode(',', $content) . '}';
	}
	
	/**
	* Outputs a JSON string to the browser 
	*
	* @param	mixed	array to output
	*/	
	public static function outputJSON($json, $full_shutdown = false)
	{
		if (!headers_sent())
		{
			// Set the header
			header('Content-type: application/json');
		}
		
		// Create JSON
		$json = self::encodeJSON($json);
		
		// Turn off debug output
		self::$vbulletin->debug = false;
		
		if (defined('VB_API') AND VB_API === true)
		{
			print_output($json);
		}

		//run any registered shutdown functions
		if (intval(self::$vbulletin->versionnumber) > 3)
		{
			$GLOBALS['vbulletin']->shutdown->shutdown();
		}
		if (defined('NOSHUTDOWNFUNC'))
		{
			if ($full_shutdown)
			{
				exec_shut_down();
			}
			else
			{
				self::$vbulletin->db->close();
			}
		}
		
		$sendHeader = false;
		switch(self::$vbulletin->options['ajaxheader'])
		{
			case 0 :
				$sendHeader = true;
				
			case 1 :
				$sendHeader = false;
				
			case 2 :
			default:
				$sendHeader = (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false);
		}

		if ($sendHeader)
		{
			// this line is causing problems with mod_gzip/deflate, but is needed for some IIS setups
			@header('Content-Length: ' . strlen($json));
		}
		
		// Finally spit out JSON
		echo $json;
		die();
	}
	
	/**
	* Constructs some <option>s for use in the templates
	*
	* @param	array	The key:value data array
	* @param	mixed	(Optional) The selected id(s)
	* @param	boolean	(Optional) Whether we should HTMLise the values
	*/	
	public static function createSelectOptions($array, $selectedid = '', $htmlise = false)
	{
		if (!is_array($array))
		{
			return '';
		}
		
		$options = '';
		foreach ($array as $key => $val)
		{
			if (is_array($val))
			{
				// Create the template
				$templater = vB_Template::create('optgroup');
					$templater->register('optgroup_label', 	($htmlise ? htmlspecialchars_uni($key) : $key));
					$templater->register('optgroup_options', self::createSelectOptions($val, $selectedid, $tabindex, $htmlise));
				$options .= $templater->render();
			}
			else
			{
				if (is_array($selectedid))
				{
					$selected = iif(in_array($key, $selectedid), ' selected="selected"', '');
				}
				else
				{
					$selected = iif($key == $selectedid, ' selected="selected"', '');
				}
				
				$templater = vB_Template::create('option');
					$templater->register('optionvalue', 	($key !== 'no_value' ? $key : ''));
					$templater->register('optionselected', 	$selected);
					$templater->register('optiontitle', 	($htmlise ? htmlspecialchars_uni($val) : $val));
				$options .= $templater->render();
			}
		}
		
		return $options;
	}
	
	/**
	* Constructs a time selector
	*
	* @param	string	The title of the time select
	* @param	string	(Optional) The HTML form name
	* @param	array	(Optional) The time we should start with
	* @param	string	(Optional) The vertical align state
	* 
	* @return	string	The constructed time row
	*/	
	public static function timeRow($title, $name = 'date', $unixtime = '', $valign = 'middle')
	{
		global $vbphrase, $vbulletin;
		
		$output = '';
	
		$monthnames = array(
			0  => '- - - -',
			1  => $vbphrase['january'],
			2  => $vbphrase['february'],
			3  => $vbphrase['march'],
			4  => $vbphrase['april'],
			5  => $vbphrase['may'],
			6  => $vbphrase['june'],
			7  => $vbphrase['july'],
			8  => $vbphrase['august'],
			9  => $vbphrase['september'],
			10 => $vbphrase['october'],
			11 => $vbphrase['november'],
			12 => $vbphrase['december'],
		);
	
		if (is_array($unixtime))
		{
			require_once(DIR . '/includes/functions_misc.php');
			$unixtime = vbmktime(0, 0, 0, $unixtime['month'], $unixtime['day'], $unixtime['year']);
		}
	
		if ($unixtime)
		{
			$month = vbdate('n', $unixtime, false, false);
			$day = vbdate('j', $unixtime, false, false);
			$year = vbdate('Y', $unixtime, false, false);
			$hour = vbdate('G', $unixtime, false, false);
			$minute = vbdate('i', $unixtime, false, false);
		}
	
		$cell = array();
		$cell[] = "<label for=\"{$name}_month\">$vbphrase[month]</label><br /><select name=\"{$name}[month]\" id=\"{$name}_month\" tabindex=\"1\" class=\"primary select\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[month]&quot;\"") . ">\n" . self::createSelectOptions($monthnames, $month) . "\t\t</select>";
		$cell[] = "<label for=\"{$name}_date\">$vbphrase[day]</label><br /><input type=\"text\" class=\"primary textbox\" name=\"{$name}[day]\" id=\"{$name}_date\" value=\"$day\" size=\"4\" maxlength=\"2\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[day]&quot;\"") . ' />';
		$cell[] = "<label for=\"{$name}_year\">$vbphrase[year]</label><br /><input type=\"text\" class=\"primary textbox\" name=\"{$name}[year]\" id=\"{$name}_year\" value=\"$year\" size=\"4\" maxlength=\"4\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[year]&quot;\"") . ' />';
		$inputs = '';
		foreach($cell AS $html)
		{
			$inputs .= "\t\t<td style=\"padding-left:6px;\"><span class=\"smallfont\">$html</span></td>\n";
		}
		
		$output .= "<div id=\"ctrl_$name\" class=\"" . (intval(self::$vbulletin->versionnumber) == 3 ? 'alt1' : 'blockrow') . "\">$title: <table cellpadding=\"0\" cellspacing=\"2\" border=\"0\"><tr>\n$inputs\t\n</tr></table></div><br />";	
		
		return $output;
	}
	
	/**
	* Sends a PM to a specified user
	*
	* @param	integer	The UserID to send the PM to
	* @param	string	Title of the PM
	* @param	string	The UserID to send the PM to
	* @param	string	(Optional) The username to send the PM from
	* @param	integer	(Optional) The user ID to send the PM from
	*/	
	public static function sendPM($userid, $title, $message, $fromusername = '', $fromuserid = 0)
	{
		global $vbphrase;
		
		// Who's the PM to
		$recipient = fetch_userinfo($userid);
		
		if ($fromusername)
		{
			// We have a source username
			if (!$fromuserid = self::$db->fetchOne('
				SELECT userid FROM $user WHERE username = ?
			', array(
				htmlspecialchars_uni($fromusername)
			)))
			{
				// Invalid user
				return false;
			}
			
			// Who's the PM to
			$sender = fetch_userinfo($fromuserid);
		}
		else if (!$fromuserid)
		{
			// Who's the PM from
			$sender = self::$vbulletin->userinfo;
		}
		
		// Send pm
		$pmdm =& datamanager_init('PM', self::$vbulletin, ERRTYPE_ARRAY);
			$pmdm->set_info('is_automated', true); // implies overridequota
			$pmdm->set('fromuserid', 	$sender['userid']);
			$pmdm->set('fromusername', 	$sender['username']);
			$pmdm->set_recipients($recipient['username'], $sender['permissions'], 'cc');
			$pmdm->setr('title', 		$title);
			$pmdm->setr('message', 		$message);
			$pmdm->set('dateline', 		TIMENOW);
			$pmdm->set('showsignature', 1);
			$pmdm->set('allowsmilie', 	0);
		if (!$pmdm->pre_save())
		{
			return $pmdm->errors;
		}
		else
		{			
			return $pmdm->save();
		}
	}
	
	/**
	* Grabs what permissions we have got
	*/
	protected static function _getPermissions()
	{
		if (!self::$vbulletin->userinfo['permissions'])
		{
			// For some reason, this is missing
			cache_permissions(self::$vbulletin->userinfo);
		}
		
		foreach (self::$bitfieldgroup as $bitfieldgroup)
		{
			// Override bitfieldgroup variable
			$bitfieldgroup = self::$prefix . $bitfieldgroup;
			
			if (!is_array(self::$vbulletin->bf_ugp[$bitfieldgroup]))
			{
				// Something went wrong here I think
				require_once(DIR . '/includes/class_bitfield_builder.php');
				if (vB_Bitfield_Builder::build(false) !== false)
				{
					$myobj =& vB_Bitfield_Builder::init();
					if (sizeof($myobj->data['ugp'][$bitfieldgroup]) != sizeof(self::$vbulletin->bf_ugp[$bitfieldgroup]))
					{
						require_once(DIR . '/includes/adminfunctions.php');
						$myobj->save(self::$vbulletin->db);
						build_forum_permissions();
						
						if (IN_CONTROL_PANEL === true)
						{
							define('CP_REDIRECT', self::$vbulletin->scriptpath);
							print_stop_message('rebuilt_bitfields_successfully');
						}
						else
						{
							self::$vbulletin->url = self::$vbulletin->scriptpath;
							eval(print_standard_redirect(array('redirect_updatethanks', self::$vbulletin->userinfo['username']), true, true));
						}
					}
				}
				else
				{
					echo "<strong>error</strong>\n";
					print_r(vB_Bitfield_Builder::fetch_errors());
					die();
				}
			}
			
			foreach ((array)self::$vbulletin->bf_ugp[$bitfieldgroup] as $permname => $bit)
			{
				// Set the permission
				self::$permissions[$permname] = (!$bit ? self::$vbulletin->userinfo['permissions'][$bitfieldgroup][$permname] : (self::$vbulletin->userinfo['permissions'][$bitfieldgroup] & $bit ? 1 : 0));
			}
		}
	}
	
	/**
	* Initialises a type class.
	*
	* @param	string	Type name
	*/
	public static function initContentType($contenttype)
	{
		if (!class_exists('LiveWall_ContentType_Core'))
		{
			// Include the needed class
			require_once(DIR . '/dbtech/livewall/includes/class_contenttype_core.php');
		}
		
		if (self::$contenttypes[$contenttype['contenttypeid']])
		{
			// We don't need to init this
			return self::$contenttypes[$contenttype['contenttypeid']];
		}
		
		if (!$contenttype['active'] OR !$contenttype['enabled'])
		{
			// We don't want to init this
			self::$contenttypes[$contenttype['contenttypeid']] = new LiveWall_ContentType_Core(self::$vbulletin, $type);
			return self::$contenttypes[$contenttype['contenttypeid']];
		}
		
		$classname = 'LiveWall_ContentType_' . $contenttype['contenttypeid'];
		if (!class_exists($classname))
		{
			// Include the needed class
			require_once(DIR . '/' . $contenttype['filename']);
		}
		
		// Init the type
		self::$contenttypes[$contenttype['contenttypeid']] = new $classname(self::$vbulletin, $contenttype);
		
		return self::$contenttypes[$contenttype['contenttypeid']];
	}
	
	/**
	* Fetches the content type data for all content types
	*
	* @param	mixed	Last data ID we fetched ([contenttypeid] => [lastId or -1 for initial load])
	* @param	integer	The userID we are fetching if we are only fetching one user
	* @param	integer	The limit (for forum sideblock)
	* @param	array	The previously fetched IDs
	*/
	public static function fetchContentTypeData($lastIds = -1, $onlyUser = 0, $limit = -1, $allIds = array(), $isSidebar = false)
	{
		// Init a couple arrays
		$data = $sortedData = array();
		
		$contenttypes = array();
		foreach ((array)self::$cache['contenttype'] as $contenttypeid => $contenttype)
		{
			// Initialise the content type
			$contentTypeObj = self::initContentType($contenttype);
			
			if (!$contentTypeObj->preCheck() OR !$contenttype['enabled'])
			{
				// Either inactive or we can't access it
				continue;
			}
			
			if (!method_exists($contentTypeObj, 'fetchData'))
			{
				// This content type is not fully implemented yet
				continue;
			}
			
			// Add to the data array
			$data = array_merge($data, (array)$contentTypeObj->fetchData(($lastIds == -1 ? $lastIds : $lastIds[$contenttypeid]), $onlyUser, $limit));
			
			// Add this content type
			$contenttypes[$contenttypeid] = $contenttype;
			
			if ($lastIds != -1)
			{
				// Store this to ensure refreshes with no new content works
				self::$lastIds[$contenttypeid] = $lastIds[$contenttypeid];
			}
		}
		
		/*DBTECH_PRO_START*/		
		if (!self::$vbulletin->options['dbtech_livewall_inlinecomments' . ($isSidebar ? '_sidebar' : '')])
		{
			$allCommentsSorted = array();
			$allCommentsByStory = self::$db->fetchAll('
				SELECT contenttypeid, contentid, COUNT(*) AS commentcount
				FROM $dbtech_livewall_comment
				GROUP BY contenttypeid, contentid		
			');
			foreach ($allCommentsByStory as $info)
			{
				$allCommentsSorted[$info['contenttypeid']][$info['contentid']] = $info['commentcount'];
			}			
		}
		/*DBTECH_PRO_END*/
		
		foreach ($data as $key => $arr)
		{
			if (!isset(self::$lastIds[$arr['contenttypeid']]) OR $arr['contentid'] > self::$lastIds[$arr['contenttypeid']])
			{
				// Set highest content type id
				self::$lastIds[$arr['contenttypeid']] = $arr['contentid'];
			}
			
			/*DBTECH_PRO_START*/		
			if (!self::$vbulletin->options['dbtech_livewall_inlinecomments' . ($isSidebar ? '_sidebar' : '')])
			{
				// Store the comment count
				$data[$key]['commentcount'] = intval($allCommentsSorted[$arr['contenttypeid']][$arr['contentid']]);
			}
			/*DBTECH_PRO_END*/	
			
			// Create a sortable array
			$sortedData[$key] = $arr['dateline'];
		}
		
		foreach ((array)$contenttypes as $contenttypeid => $contenttype)
		{
			if (!isset(self::$lastIds[$contenttypeid]))
			{
				// Set highest content type id
				self::$lastIds[$contenttypeid] = 0;
			}
		}
	
		// Sort descending by dateline
		arsort($sortedData);
		
		foreach ($sortedData as $key => $dateline)
		{
			// Restore the data
			$sortedData[$key] = $data[$key];
		}
		
		while (count($sortedData) > ($limit == -1 ? self::$vbulletin->options['dbtech_livewall_perpage'] : $limit))
		{
			// Shorten the end of the array
			array_pop($sortedData);
		}
		
		// We only need sortable
		unset($data);
		
		/*DBTECH_PRO_START*/
		foreach ($sortedData as $key => $data)
		{
			$allIds[] = array(
				'contentid' 	=> $data['contentid'],
				'contenttypeid' => $data['contenttypeid'],
			);
		}	
		
		if (self::$vbulletin->options['dbtech_livewall_inlinecomments' . ($isSidebar ? '_sidebar' : '')])
		{
			$SQL = array();
			foreach ($allIds as $key => $info)
			{
				// Fetch all current IDs
				$SQL[] = '(comment.contenttypeid = ' . self::$vbulletin->db->sql_prepare($info['contenttypeid']) . ' AND comment.contentid = ' . intval($info['contentid']) . ')';
			}
			
			if (count($SQL))
			{
				// Grab all comments
				$allCommentsByStory = self::$db->fetchAll('
					SELECT 
						comment.*,			
						user.*
						:avatarQuery				
					FROM $dbtech_livewall_comment AS comment
					LEFT JOIN $user AS user ON(user.userid = comment.userid)
					:avatarJoin
					WHERE ' . implode(' OR ', $SQL) . '
				', array(
					':avatarQuery' 			=> (self::$vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : ''),
					':avatarJoin' 			=> (self::$vbulletin->options['avatarenabled'] ? 'LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)' : ''),		
				));
			}
			
			$sortedComments = array();
			foreach ((array)$allCommentsByStory as $key => $arr)
			{
				// Create a sortable array
				$sortedComments[$key] = $arr['dateline'];
			}
			
			// Sort descending by dateline
			arsort($sortedComments);
			
			foreach ((array)$sortedComments as $key => $dateline)
			{
				// Set all comments
				self::$allComments[$allCommentsByStory[$key]['contenttypeid']][$allCommentsByStory[$key]['contentid']][] = $allCommentsByStory[$key];
			}
			
			foreach ((array)$allIds as $key => $info)
			{
				self::$allIds[] = array(
					'contentid' 	=> $info['contentid'],
					'contenttypeid' => $info['contenttypeid'],
					'commentcount' 	=> count(self::$allComments[$info['contenttypeid']][$info['contentid']])
				);
			}			
		}
		else
		{
			foreach ($allIds as $key => $info)
			{
				self::$allIds[] = array(
					'contentid' 	=> $info['contentid'],
					'contenttypeid' => $info['contenttypeid'],
					'commentcount' 	=> intval($allCommentsSorted[$info['contenttypeid']][$info['contentid']])
				);
			}			
		}
		/*DBTECH_PRO_END*/
		
		return $sortedData;
	}
	
	/**
	* Fetches the comments for the selected story
	*
	* @param	string	The content type id we are fetching comments for
	* @param	integer	The content ID
	* @param	mixed	Last data ID we fetched
	*/
	public static function fetchCommentData($contenttypeid, $contentid, $lastId = -1)
	{
		global $vbphrase;
		
		// Init a couple arrays
		$data = $sortedData = array();
		
		if (!$contenttype = self::$cache['contenttype'][$contenttypeid])
		{
			// Wrong content type
			eval(standard_error(fetch_error('dbtech_livewall_error_x', $vbphrase['dbtech_livewall_invalid_action'])));
		}
		
		// Initialise the content type
		$contentTypeObj = self::initContentType($contenttype);
		
		if (!$contentTypeObj->preCheck() OR !$contenttype['enabled'])
		{
			// Either inactive or we can't access it
			eval(standard_error(fetch_error('dbtech_livewall_error_x', $vbphrase['dbtech_livewall_invalid_action'])));
		}
		
		// Fetch the content - hack so we don't have to make a new function. u mad?
		$data['content'] = $contentTypeObj->fetchData($contentid, 0, 1, true);
		
		if (!count($data['content']))
		{
			// This content type is not fully implemented yet
			eval(standard_error(fetch_error('dbtech_livewall_error_x', $vbphrase['dbtech_livewall_invalid_action'])));
		}
		
		// Ensure this is proper
		$data['content'] = $data['content'][(count($data['content']) - 1)];		
		
		// Fetch all comments
		$data['comments'] = self::$db->fetchAll('
			SELECT 
				comment.*,
				user.*
				:avatarQuery
			FROM $dbtech_livewall_comment AS comment
			LEFT JOIN $user AS user ON(user.userid = comment.userid)
			:avatarJoin
			WHERE contenttypeid = ?
				AND contentid = ?
				' . ($lastId != -1 ? 'AND commentid > ' . intval($lastId) : '') . '
		', array(
			$contenttypeid,
			$contentid,
			':avatarQuery' 			=> (self::$vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : ''),
			':avatarJoin' 			=> (self::$vbulletin->options['avatarenabled'] ? 'LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)' : ''),			
		));
		
		if ($lastId != -1)
		{
			// Store this to ensure refreshes with no new content works
			self::$lastIds[$contenttypeid] = $lastId;
		}
		
		foreach ($data['comments'] as $key => $arr)
		{
			if (!isset(self::$lastIds[$contenttypeid]) OR $arr['commentid'] > self::$lastIds[$contenttypeid])
			{
				// Set highest content type id
				self::$lastIds[$contenttypeid] = $arr['commentid'];
			}
			
			// Create a sortable array
			$sortedData[$key] = $arr['dateline'];
		}
		
		// Sort descending by dateline
		arsort($sortedData);
		
		foreach ($sortedData as $key => $dateline)
		{
			// Restore the data
			$sortedData[$key] = $data['comments'][$key];
		}
		
		// Restore this
		$data['comments'] = $sortedData;
		
		/*
		while (count($sortedData) > ($limit == -1 ? self::$vbulletin->options['dbtech_livewall_perpage'] : $limit))
		{
			// Shorten the end of the array
			array_pop($sortedData);
		}
		*/
		
		return $data;
	}
	
	/**
	* Fetches the comments for the threadbits
	*
	* @param	array	Array of content types => ids to fetch
	* @param	mixed	Last data ID we fetched
	*/
	public static function fetchCommentDataThreadbit($contentTypeIds, $lastIds = -1)
	{
		global $vbphrase;
		
		// Init a couple arrays
		$data = $sortedData = $finalData = array();
		
		foreach ($contentTypeIds as $contenttypeid => $contentids)
		{
			if (!$contenttype = self::$cache['contenttype'][$contenttypeid])
			{
				// Wrong content type
				unset($contentTypeIds[$contenttypeid]);
				continue;
			}
			
			// Initialise the content type
			$contentTypeObj = self::initContentType($contenttype);
			
			if (!$contentTypeObj->preCheck() OR !$contenttype['enabled'])
			{
				// Either inactive or we can't access it
				unset($contentTypeIds[$contenttypeid]);
				continue;
			}
			
			// Fetch all comments
			$data = self::$db->fetchAll('
				SELECT 
					comment.*,
					user.*
					:avatarQuery
				FROM $dbtech_livewall_comment AS comment
				LEFT JOIN $user AS user ON(user.userid = comment.userid)
				:avatarJoin
				WHERE contenttypeid = ?
					AND contentid :queryList
					' . ($lastIds != -1 ? 'AND commentid > ' . intval($lastIds[$contenttypeid]) : '') . '
			', array(
				$contenttypeid,
				':queryList' 	=> self::$db->queryList($contentids),
				':avatarQuery' 	=> (self::$vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : ''),
				':avatarJoin' 	=> (self::$vbulletin->options['avatarenabled'] ? 'LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)' : ''),			
			));
			
			if ($lastIds != -1)
			{
				// Store this to ensure refreshes with no new content works
				self::$lastIds[$contenttypeid] = $lastIds[$contenttypeid];
			}
			
			foreach ($data as $key => $arr)
			{
				if (!isset(self::$lastIds[$contenttypeid]) OR $arr['commentid'] > self::$lastIds[$contenttypeid])
				{
					// Set highest content type id
					self::$lastIds[$contenttypeid] = $arr['commentid'];
				}
				
				// Create a sortable array
				$sortedData[$key] = $arr['dateline'];
			}
			
			// Sort descending by dateline
			arsort($sortedData);
			
			foreach ($sortedData as $key => $dateline)
			{
				// Restore the data
				$finalData[$contenttypeid][$data[$key]['contentid']][$key] = $data[$key];
			}
		}
					
		/*
		while (count($sortedData) > ($limit == -1 ? self::$vbulletin->options['dbtech_livewall_perpage'] : $limit))
		{
			// Shorten the end of the array
			array_pop($sortedData);
		}
		*/
		
		return $finalData;
	}
}

// #############################################################################
// database functionality class

/**
* Class that handles database wrapper
*
* @package	Framework
* @version	$ $Rev$ $
* @date		$ $Date$ $
*/
class LiveWall_Database
{
	/**
	* The vBulletin database object
	*
	* @private	vB_Database
	*/		
	private $db;
	
	/**
	* The query result we executed
	*
	* @private	MySQL_Result
	*/	
	private $result;
	
	/**
	* Whether we're debugging output
	*
	* @public	boolean
	*/	
	public $debug = false;


	/**
	* Does important checking before anything else should be going on
	*
	* @param	vB_Registry		Registry object
	*/
	function __construct($dbobj)
	{
		$this->db = $dbobj;
	}


	/**
	 * Inserts a table row with specified data.
	 *
	 * @param mixed $table The table to insert data into.
	 * @param array $bind Column-value pairs.
	 * @param mixed $exclusions Array of field names that should be ignored from the $queryvalues array
	 * 
	 * @return int The number of affected rows.
	 */
	public function insert($table, array $bind, array $exclusions = array())
	{
		// Store the query
		$sql = fetch_query_sql($bind, $table, '', $exclusions);
		
		if ($this->debug)
		{
			echo "<pre>";
			echo $sql;
			echo "</pre>";
			die();
		}
		
		$this->db->query_write($sql);
		
		// Return insert ID if only one row was inserted, otherwise return number of affected rows
		$affected = $this->db->affected_rows();
		return($affected === 1 ? $this->db->insert_id() : $affected);
	}
	
	/**
	 * Updates table rows with specified data based on a WHERE clause.
	 *
	 * @param  mixed		$table The table to update.
	 * @param  array		$bind  Column-value pairs.
	 * @param  mixed		$where UPDATE WHERE clause(s).
	 * @param  mixed		$exclusions Array of field names that should be ignored from the $queryvalues array
	 * 
	 * @return int		  The number of affected rows.
	 */
	public function update($table, array $bind, $where, array $exclusions = array())
	{
		$sql = fetch_query_sql($bind, $table, $where, $exclusions);
		
		if ($this->debug)
		{
			echo "<pre>";
			echo $sql;
			echo "</pre>";
			die();
		}
		
		$this->db->query_write($sql);
		return $this->db->affected_rows();
	}
	
	/**
	 * Deletes table rows based on a WHERE clause.
	 *
	 * @param  mixed		$table The table to update.
	 * @param  mixed  		$bind Data to bind into DELETE placeholders.
	 * @param  mixed		$where DELETE WHERE clause(s).
	 * 
	 * @return int		  The number of affected rows.
	 */
	public function delete($table, array $bind, $where = '')
	{
		/**
		 * Build the DELETE statement
		 */
		$sql = "DELETE FROM "
			 . TABLE_PREFIX . $table
			 . ' ' . $where;

		/**
		 * Execute the statement and return the number of affected rows
		 */
		$result = $this->query($sql, $bind, 'query_write');
		return $this->db->affected_rows();
	}
	
	/**
	 * Fetches all SQL result rows as a sequential array.
	 *
	 * @param string $sql  An SQL SELECT statement.
	 * @param mixed  $bind Data to bind into SELECT placeholders.
	 * 
	 * @return array
	 */
	public function fetchAll($sql, $bind = array())
	{
		$results = array();
		
		$this->query($sql, $bind, 'query_read');
		while ($row = $this->db->fetch_array($this->result))
		{
			$results[] = $row;
		}
		return $results;
	}
	
	/**
	 * Fetches results from the database with a specified column from each row keyed according to preference.
	 * The 'key' parameter provides the column name with which to key the result.
	 * The 'column' parameter provides the column name with which to use as the result.
	 * For example, calling fetchAllKeyed('SELECT item_id, title, date FROM table', 'item_id', 'title')
	 * would result in an array keyed by item_id:
	 * [$itemId] => $title
	 *
	 * Note that the specified key must exist in the query result, or it will be ignored.
	 *
	 * @param string SQL to execute
	 * @param string Column with which to key the results array
	 * @param string Column to use as the result for that key
	 * @param mixed Parameters for the SQL
	 *
	 * @return array
	 */
	public function fetchAllSingleKeyed($sql, $key, $column, $bind = array())
	{
		$results = array();
		$i = 0;

		$this->query($sql, $bind, 'query_read');
		while ($row = $this->db->fetch_array($this->result))
		{
			$results[(isset($row[$key]) ? $row[$key] : $i)] = $row[$column];
			$i++;
		}

		return $results;
	}
	
	/**
	 * Fetches results from the database with each row keyed according to preference.
	 * The 'key' parameter provides the column name with which to key the result.
	 * For example, calling fetchAllKeyed('SELECT item_id, title, date FROM table', 'item_id')
	 * would result in an array keyed by item_id:
	 * [$itemId] => array('item_id' => $itemId, 'title' => $title, 'date' => $date)
	 *
	 * Note that the specified key must exist in the query result, or it will be ignored.
	 *
	 * @param string SQL to execute
	 * @param string Column with which to key the results array
	 * @param mixed Parameters for the SQL
	 *
	 * @return array
	 */
	public function fetchAllKeyed($sql, $key, $bind = array())
	{
		$results = array();
		$i = 0;

		$this->query($sql, $bind, 'query_read');
		while ($row = $this->db->fetch_array($this->result))
		{
			$results[(isset($row[$key]) ? $row[$key] : $i)] = $row;
			$i++;
		}

		return $results;
	}

	/**
	 * Fetches all SQL result rows as an associative array.
	 *
	 * The first column is the key, the entire row array is the
	 * value.  You should construct the query to be sure that
	 * the first column contains unique values, or else
	 * rows with duplicate values in the first column will
	 * overwrite previous data.
	 *
	 * @param string $sql An SQL SELECT statement.
	 * @param mixed $bind Data to bind into SELECT placeholders.
	 * 
	 * @return array
	 */
	public function fetchAssoc($sql, $bind = array())
	{
		$data = array();
		$this->query($sql, $bind, 'query_read');
		while ($row = $this->db->fetch_array($this->result))
		{
			$key = key($row);
			$data[$row[$key]] = $row;
		}
		return $data;
	}	
	
	/**
	 * Fetches the first row of the SQL result.
	 *
	 * @param string $sql An SQL SELECT statement.
	 * @param mixed  $bind Data to bind into SELECT placeholders.
	 * @param mixed  $fetchMode Override current fetch mode.
	 * 
	 * @return array
	 */
	public function fetchRow($sql, $bind = array())
	{
		// Check the limit and fix $sql
		$limit = explode('limit', strtolower($sql));
		if (sizeof($limit) != 2 OR !is_numeric(trim($limit[1])))
		{
			// Append limit
			$sql .= ' LIMIT 1';
		}
		
		$result = $this->query($sql, $bind, 'query_first');
		return $result;
	}
	
	/**
	 * Fetches the first column of all SQL result rows as an array.
	 *
	 * @param string $sql An SQL SELECT statement.
	 * @param mixed  $bind Data to bind into SELECT placeholders.
	 * @param mixed  $column OPTIONAL - Key to use for the column index
	 * @return array
	 */
	public function fetchCol($sql, $bind = array(), $column = '')
	{
		$data = array();
		$this->query($sql, $bind, 'query_read');
		while ($row = $this->db->fetch_array($this->result))
		{
			// Validate the key
			$key = ((isset($row[$column]) AND $column) ? $column : key($row));
			$data[] = $row[$key];
		}
		return $data;
	}
	
	/**
	 * Fetches the first column of the first row of the SQL result.
	 *
	 * @param string $sql An SQL SELECT statement.
	 * @param mixed  $bind Data to bind into SELECT placeholders.
	 * @param mixed  $column OPTIONAL - Key to use for the column index
	 * @return string
	 */
	public function fetchOne($sql, $bind = array(), $column = '')
	{
		$result = $this->fetchRow($sql, $bind);
		return ($column ? $result[$column] : (is_array($result) ? reset($result) : ''));
	}
	
	/**
	 * Prepares and executes an SQL statement with bound data.
	 *
	 * @param  mixed  $sql  The SQL statement with placeholders.
	 * @param  mixed  $bind An array of data to bind to the placeholders.
	 * @param  string Which query method to use
	 * 
	 * @return mixed  Result
	 */
	public function query($sql, $bind = array(), $which = 'query_read')
	{
		// make sure $bind is an array
		if (!is_array($bind))
		{
			$bind = (array)$bind;
		}
		
		if (!in_array($which, array('query_read', 'query_write', 'query_first')))
		{
			// Default to query read
			$which = 'query_read';
		}
		
		foreach ($bind as $key => $val)
		{
			if (is_numeric($key))
			{
				// Sort string mapping
				$val = (is_numeric($val) ? "'$val'" : "'" . $this->db->escape_string($val) . "'");
				
				// Replace first instance of ?
				$sql = implode($val, explode('?', $sql, 2));
			}
		}
		
		foreach ($bind as $key => $val)
		{
			if (!is_numeric($key))
			{
				// Array of token replacements
				$sql = str_replace($key, $val, $sql);
			}
		}
		
		// Set the table prefix
		$sql = preg_replace('/\s+`?\$/U', ' ' . TABLE_PREFIX, $sql);
		
		if ($this->debug)
		{
			echo "<pre>";
			echo $sql;
			echo "</pre>";
			die();
		}		
		
		// Execute the query
		$this->result = $this->db->$which($sql);
		return $this->result;
	}
	
	/**
	 * Helper function for IN statements for SQL queries.
	 * For example, with an array $userids = array(1, 2, 3, 4, 5);
	 * the query would be WHERE userid IN' . $this->queryList($userids) . '
	 *
	 * @param  array The array to work with
	 * 
	 * @return mixed  Properly escaped and parenthesised IN() list
	 */
	public function queryList($arr)
	{
		$values = array();
		foreach ($arr as $val)
		{
			// Ensure the value is escaped properly
			$values[] = (is_numeric($val) ? $val : "'" . $this->db->escape_string($val) . "'");
		}
		
		if (!count($values))
		{
			// Ensure there's no SQL errors
			$values[] = 0;
		}
		
		return 'IN(' . implode(', ', $values) . ')';
	}
}

// #############################################################################
// filter functionality class

/**
* Class that handles filtering arrays
*
* @package	Framework
* @version	$ $Rev$ $
* @date		$ $Date$ $
*/
class LIVEWALL_FILTER
{
	/**
	* Id Field we are using
	*
	* @private	string
	*/	
	private static $idfield 	= NULL;
	
	/**
	* Id value we are looking for
	*
	* @private	mixed
	*/	
	private static $idval 		= NULL;
	
	
	
	/**
	* Sets up and begins the filtering process 
	*
	* @param	array	Array to filter
	* @param	string	What the ID Field is
	* @param	mixed	What we are looking for
	*
	* @return	array	Filtered array
	*/
	public static function filter($array, $idfield, $idval)
	{
		// Set the two things we can't pass on to the callback
		self::$idfield 	= $idfield;
		self::$idval	= $idval;
		
		// Filter this shiet
		return array_filter($array, array(__CLASS__, 'do_filter'));
	}
	
	/**
	* Checks if this element should be included
	*
	* @param	array	Array to filter
	*
	* @return	boolean	Whether we should include this or not
	*/	
	protected static function do_filter($array)
	{
		$idfield 	= self::$idfield;
		$idval		= self::$idval;
		return ($array["$idfield"] == $idval);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Created: 16:52, Sat Dec 26th 2009
|| # SVN: $ $Rev$ $ - $ $Date$ $
|| ####################################################################
\*======================================================================*/