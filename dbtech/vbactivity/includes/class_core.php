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

// #############################################################################
// vBActivity functionality class

/**
* Handles everything to do with vBActivity.
*/
class VBACTIVITY
{
	/**
	* Version info
	*
	* @public	mixed
	*/	
	public static $jQueryVersion 	= '1.7.2';	
	public static $version 			= '3.1.9 Patch Level 1';
	public static $versionnumber	= '319pl1';
	
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
		'vbactivitypermissions',
		'vbactivitymodpermissions',
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
	* Whether we've called the translations
	*
	* @public	boolean
	*/		
	protected static $called2		= false;
	
	/**
	* Array of cached items
	*
	* @public	array
	*/		
	public static $unserialize		= array(
		'contest' => array(
			'data',
			'prizes',
			'prizes2',
			'winners',
			'excludedcriteria',
			'excludedforums',
		),
		'type' => array(
			'pointsperforum'
		),
	);
	
	/**
	* Array of translatable items
	*
	* @public	array
	*/		
	public static $translations		= array(
		'achievement' 	=> 'achievementid',
		'category' 		=> 'categoryid',
		'contest' 		=> 'contestid',
		'contesttype' 	=> 'varname',
		'medal' 		=> 'medalid',
	);
	
	/**
	* Whether we have the pro version or not
	*
	* @public	boolean
	*/		
	public static $isPro		= false;
	
	/**
	* Array of postbits
	*
	* @public	array
	*/	
	public static $postbitcache 	= array();
	
	/**
	* Array of postbits
	*
	* @public	array
	*/	
	public static $postbitcache2 	= array();
	
	/**
	* Whether we've cached the points
	*
	* @private	boolean
	*/	
	public static $points_cached = false;
	
	
	/**
	* Caches various values to ease resource consumption
	*
	* @private	array
	*/	
	private static $levels 			= array();
	private static $tnlvalues 		= array();	
	
	/**
	* Array of type objects
	*
	* @public	array
	*/	
	public static $types 			= array();
	
	/**
	* Array of cached users
	*
	* @public	array
	*/	
	public static $cachedUsers		= array();

	
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
		self::$db = new vBActivity_Database($vbulletin->db);
		
		// Set permissions shorthand
		self::_getPermissions();
		
		// What permissions to override
		$override = array(
			'canviewactivity',
		);
		
		foreach ($override as $permname)
		{
			// Override various permissions
			self::$permissions[$permname] = (self::$permissions['ismanager'] ? 1 : self::$permissions[$permname]);
		}

		if (VB_AREA == 'AdminCP')
		{
			// Easier than a billion if checks in the files
			$override = array(
				'achievement',
				'category',
				'criteria',
				'award',
				'grantawards',
				'maintenance',
				'trophy',
				'contest',
				'promotion',
				'backup',
				'snapshot',
				'impex',
				'points',
				'permissions',
				'options',
			);
			
			foreach ($override as $permname)
			{
				// Override various permissions
				self::$permissions[$permname] = 1;
			}
		}
		
		foreach (self::$unserialize as $cachetype => $keys)
		{
			foreach (self::$cache[$cachetype] as $id => $arr)
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
	* Handles translation of things
	*/
	public static function loadTranslations()
	{
		global $vbphrase;

		if (self::$called2)
		{
			// We've already done this
			return;
		}

		foreach (self::$translations as $cachetype => $key)
		{
			foreach (self::$cache[$cachetype] as $id => $arr)
			{
				self::$cache[$cachetype][$id]['title_translated'] 		= isset($vbphrase["dbtech_vbactivity_{$cachetype}_{$arr[$key]}_title"]) 		? $vbphrase["dbtech_vbactivity_{$cachetype}_{$arr[$key]}_title"] 		: $arr['title'];
				self::$cache[$cachetype][$id]['description_translated'] = isset($vbphrase["dbtech_vbactivity_{$cachetype}_{$arr[$key]}_description"]) 	? $vbphrase["dbtech_vbactivity_{$cachetype}_{$arr[$key]}_description"] 	: $arr['description'];
			}
		}

		// Translations loaded
		self::$called2 = true;
	}
		
	/**
	* Check if we have permissions to perform an action
	*
	* @param	array		User info
	* @param	array		Permissions info
	*/		
	public static function checkPermissions(&$user, $permissions, $bitIndex)
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
		
		$bits = array(
			'default' 	=> 4
		);
		$bit = $bits[$bitIndex];
		
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
				
				case -1:
					// Usergroup Default		
					if (!($user[self::$prefix . self::$bitfieldgroup[0]] & $bit))
					{
						// Allow by default
						return true;
					}
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
			if (file_exists(DIR . '/dbtech/vbactivity/includes/class_dm_' . strtolower($classtype) . '.php'))
			{
				// Lite
				require_once(DIR . '/dbtech/vbactivity/includes/class_dm_' . strtolower($classtype) . '.php');
			}
			else
			{
				// Pro
				require_once(DIR . '/dbtech/vbactivity_pro/includes/class_dm_' . strtolower($classtype) . '.php');
			}
	
			$classname = 'vBActivity_DataManager_' . $classtype;
			$object = new $classname($registry, $errtype);
	
			return $object;
		}
	}
	
	/**
	* (Legacy) Class factory. This is used for instantiating the extended classes.
	*
	* @param	string			The type of the class to be called (user, forum etc.)
	* @param	vB_Registry		An instance of the vB_Registry object.
	* @param	integer			One of the ERRTYPE_x constants
	*
	* @return	vB_DataManager	An instance of the desired class
	*/
	public static function &datamanager_init($classtype, &$registry, $errtype = ERRTYPE_STANDARD)
	{
		return self::initDataManager($classtype, $registry, $errtype);
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
			$output = '<script type="text/javascript" src="' . self::$vbulletin->options['bburl'] . '/dbtech/vbactivity/clientscript/vbactivity' . $js . '.js?v=' . self::$versionnumber . '"></script>';
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
	* Determines the path to jQuery based on browser settings
	*/
	public static function jQueryPath()
	{
		// create the path to jQuery depending on the version
		if (self::$vbulletin->options['customjquery_path'])
		{
			$path = str_replace('{version}', self::$jQueryVersion, self::$vbulletin->options['customjquery_path']);
			if (!preg_match('#^https?://#si', self::$vbulletin->options['customjquery_path']))
			{
				$path = REQ_PROTOCOL . '://' . $path;
			}
			return $path;
		}
		else
		{
			switch (self::$vbulletin->options['remotejquery'])
			{
				case 1:
				default:
					// Google CDN
					return REQ_PROTOCOL . '://ajax.googleapis.com/ajax/libs/jquery/' . self::$jQueryVersion . '/jquery.min.js';
					break;

				case 2:
					// jQuery CDN
					return REQ_PROTOCOL . '://code.jquery.com/jquery-' . self::$jQueryVersion . '.min.js';
					break;

				case 3:
					// Microsoft CDN
					return REQ_PROTOCOL . '://ajax.aspnetcdn.com/ajax/jquery/jquery-' . self::$jQueryVersion . '.min.js';
					break;
			}
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
			
			'achievements',
			'promotions',
			'trophies',
			'medals',
			'activity',
			
			'ranking',
			'leaderboards',
			
			'allachievements',
			'alltrophies',
			'allawards',
			'contests',
			'activitystats',
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
				$content[] = '"' . $key . '":' . $val;
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
	/**
	* Outputs a JSON string to the browser 
	*
	* @param	mixed	array to output
	*/	
	public static function outputJSON($json, $full_shutdown = false)
	{
		if (headers_sent($file, $line))
		{
			die("Cannot send response, headers already sent. File: $file Line: $line");
		}

		// Store the charset
		$charset = strtoupper(self::getCharset());
		
		// We need to convert $json charset if we're not using UTF-8
		if ($charset != 'UTF-8')
		{
			$json = self::toCharset($json, $charset, 'UTF-8');
		}

		//If this is IE9, IE10, or IE11 -- we also need to work around the deliberate attempt to break "is IE" logic by the
		//IE dev team -- we need to send type "text/plain". Yes, we know that's not the standard.
		if (
			isset($_SERVER['HTTP_USER_AGENT']) && (
				(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) OR
				(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false)
			)
		)
		{
			header('Content-type: text/plain; charset=UTF-8');
		}
		else
		{
			header('Content-type: application/json; charset=UTF-8');
		}

		// IE will cache ajax requests, and we need to prevent this - VBV-148
		header('Cache-Control: max-age=0,no-cache,no-store,post-check=0,pre-check=0');
		header('Expires: Sat, 1 Jan 2000 01:00:00 GMT');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Pragma: no-cache");

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
		exec_shut_down();
		self::$vbulletin->db->close();
		
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
	 * Converts a string from one character encoding to another.
	 * If the target encoding is not specified then it will be resolved from the current
	 * language settings.
	 *
	 * @param	string|array	The string/array to convert
	 * @param	string	The source encoding
	 * @return	string	The target encoding
	 */
	public static function toCharset($in, $in_encoding, $target_encoding = false)
	{
		if (!$target_encoding) {
			if (!($target_encoding = self::getCharset())) {
				return $in;
			}
		}

		if (is_object($in))
		{
			foreach ($in as $key => $val)
			{
				$in->$key = self::toCharset($val, $in_encoding, $target_encoding);
			}

			return $in;
		}
		else if (is_array($in)) {
			foreach ($in as $key => $val)
			{
				$in["$key"] = self::toCharset($val, $in_encoding, $target_encoding);
			}

			return $in;
		}
		else if (is_string($in))
		{
			// ISO-8859-1 or other Western charset doesn't support Asian ones so that we need to NCR them
			// Iconv will ignore them
			if (preg_match("/^[ISO|Windows|IBM|MAC|CP]/i", $target_encoding)) {
				$in = self::ncrEncode($in, true, true);
			}

			// Try iconv
			if (function_exists('iconv')) {
				// Try iconv
				$out = @iconv($in_encoding, $target_encoding . '//IGNORE', $in);
				return $out;
			}

			// Try mbstring
			if (function_exists('mb_convert_encoding')) {
				return @mb_convert_encoding($in, $target_encoding, $in_encoding);
			}
		}
		else
		{
			// if it's not a string, array or object, don't modify it
			return $in;
		}
	}

	/**
	 * Gets the current charset
	 **/
	public static function getCharset()
	{
		static $lang_charset = '';
		if (!empty($lang_charset))
		{
			return $lang_charset;
		}

		if (intval(self::$vbulletin->versionnumber) > 3)
		{
			// vB4
			$lang_charset = vB_Template_Runtime::fetchStyleVar('charset');
		}
		else
		{
			// vB3
			$lang_charset = $GLOBALS['stylevar']['charset'];
		}

		if (!empty($lang_charset))
		{
			return $lang_charset;
		}

		$lang_charset = (!empty(self::$vbulletin->userinfo['lang_charset'])) ? self::$vbulletin->userinfo['lang_charset'] : 'utf-8';

		return $lang_charset;
	}

	/**
	* Converts a UTF-8 string into unicode NCR equivelants.
	*
	* @param	string	String to encode
	* @param	bool	Only ncrencode unicode bytes
	* @param	bool	If true and $skip_ascii is true, it will skip windows-1252 extended chars
	* @return	string	Encoded string
	*/
	public static function ncrEncode($str, $skip_ascii = false, $skip_win = false)
	{
		if (!$str)
		{
			return $str;
		}

		if (function_exists('mb_encode_numericentity'))
		{
			if ($skip_ascii)
			{
				if ($skip_win)
				{
					$start = 0xFE;
				}
				else
				{
					$start = 0x80;
				}
			}
			else
			{
				$start = 0x0;
			}
			return mb_encode_numericentity($str, array($start, 0xffff, 0, 0xffff), 'UTF-8');
		}

		if (is_pcre_unicode())
		{
			return preg_replace_callback(
				'#\X#u',
				create_function('$matches', 'return ncrencode_matches($matches, ' . (int)$skip_ascii . ', ' . (int)$skip_win . ');'),
				$str
			);
		}

		return $str;
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
	* Resulting form element names: $name[day], $name[month], $name[year], $name[hour], $name[minute]
	*
	* @param	string	Title for row
	* @param	string	Base name for form elements - $name[day], $name[month], $name[year] etc.
	* @param	mixed	Unix timestamp to be represented by the form fields OR SQL date field (yyyy-mm-dd)
	* @param	boolean	Whether or not to show the time input components, or only the date
	* @param	boolean	If true, expect an SQL date field from the unix timestamp parameter instead (for birthdays)
	* @param	string	Vertical alignment for the row
	* 
	* @return	string	The constructed time row
	*/	
	public static function timeRow($name = 'date', $unixtime = '', $showtime = true, $birthday = false, $valign = 'middle')
	{
		global $vbphrase;
		
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
		
		if ($birthday)
		{ // mktime() on win32 doesn't support dates before 1970 so we can't fool with a negative timestamp
			if ($unixtime == '')
			{
				$month = 0;
				$day = '';
				$year = '';
			}
			else
			{
				$temp = explode('-', $unixtime);
				$month = intval($temp[0]);
				$day = intval($temp[1]);
				if ($temp[2] == '0000')
				{
					$year = '';
				}
				else
				{
					$year = intval($temp[2]);
				}
			}
		}
		else
		{
			if ($unixtime)
			{
				$month = vbdate('n', $unixtime, false, false);
				$day = vbdate('j', $unixtime, false, false);
				$year = vbdate('Y', $unixtime, false, false);
				$hour = vbdate('G', $unixtime, false, false);
				$minute = vbdate('i', $unixtime, false, false);
			}
		}
	
		$cell = array();
		$cell[] = "<label for=\"{$name}_month\">$vbphrase[month]</label><br /><select name=\"{$name}[month]\" id=\"{$name}_month\" tabindex=\"1\" class=\"primary select\"" . iif(self::$vbulletin->debug, " title=\"name=&quot;$name" . "[month]&quot;\"") . ">\n" . self::createSelectOptions($monthnames, $month) . "\t\t</select>";
		$cell[] = "<label for=\"{$name}_date\">$vbphrase[day]</label><br /><input type=\"text\" class=\"primary textbox\" name=\"{$name}[day]\" id=\"{$name}_date\" value=\"$day\" size=\"4\" maxlength=\"2\" tabindex=\"1\"" . iif(self::$vbulletin->debug, " title=\"name=&quot;$name" . "[day]&quot;\"") . ' />';
		$cell[] = "<label for=\"{$name}_year\">$vbphrase[year]</label><br /><input type=\"text\" class=\"primary textbox\" name=\"{$name}[year]\" id=\"{$name}_year\" value=\"$year\" size=\"4\" maxlength=\"4\" tabindex=\"1\"" . iif(self::$vbulletin->debug, " title=\"name=&quot;$name" . "[year]&quot;\"") . ' />';
		if ($showtime)
		{
			$cell[] = "<label for=\"{$name}_hour\">$vbphrase[hours]</label><br /><input type=\"text\" class=\"primary textbox\" name=\"{$name}[hour]\" id=\"{$name}_year\" value=\"$hour\" size=\"4\" maxlength=\"4\" tabindex=\"1\"" . iif(self::$vbulletin->debug, " title=\"name=&quot;$name" . "[hour]&quot;\"") . ' />';
			$cell[] = "<label for=\"{$name}_minute\">$vbphrase[minute]</label><br /><input type=\"text\" class=\"primary textbox\" name=\"{$name}[minute]\" id=\"{$name}_minute\" value=\"$minute\" size=\"4\" maxlength=\"4\" tabindex=\"1\"" . iif(self::$vbulletin->debug, " title=\"name=&quot;$name" . "[minute]&quot;\"") . ' />';
		}
		$inputs = '';
		foreach($cell AS $html)
		{
			$inputs .= "\t\t<td style=\"padding-left:6px;\"><span class=\"smallfont\">$html</span></td>\n";
		}
		
		$output .= "<table cellpadding=\"0\" cellspacing=\"2\" border=\"0\"><tr>\n$inputs\t\n</tr></table>";	
		
		return $output;
	}
	
	/**
	* Sends a PM to a specified user
	*
	* @param	mixed	The UserID or userinfo to send the PM to
	* @param	string	Title of the PM
	* @param	string	Body of the PM
	* @param	mixed	Userinfo or vBOption key to send the PM from
	*/	
	public static function sendPM($recipient, $title, $message, $sender = NULL)
	{
		global $vbphrase;
		
		if (!is_array($recipient))
		{
			// Who's the PM to
			$recipient = fetch_userinfo($recipient);
		}
		
		if (array_key_exists($sender, self::$vbulletin->options))
		{
			if (self::$vbulletin->options[$sender])
			{
				// Who's the PM from
				$sender = fetch_userinfo(self::$vbulletin->options[$sender]);
			}
			else
			{
				// Null this out since we had no defined sender
				$sender = NULL;
			}
		}

		if ($sender === NULL)
		{
			// We're using the recipient
			$sender = $recipient;
		}

		if (!isset($sender))
		{
			if (!$fromuserid)
			{
				// Invalid user
				return false;
			}

			// Who's the PM to
			$sender = fetch_userinfo($fromuserid);
		}
		
		// Send pm
		$pmdm =& datamanager_init('PM', self::$vbulletin, ERRTYPE_ARRAY);
			$pmdm->set_info('is_automated', true); // implies overridequota
			$pmdm->set('fromuserid', 	$sender['userid']);
			$pmdm->set('fromusername', 	unhtmlspecialchars($sender['username']));
			$pmdm->set_recipients(unhtmlspecialchars($recipient['username']), $sender['permissions'], 'cc');
			$pmdm->setr('title', 		$title);
			$pmdm->setr('message', 		$message);
			$pmdm->set('dateline', 		TIMENOW);
			$pmdm->set('showsignature', 1);
			$pmdm->set('allowsmilie', 	1);
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
							if (version_compare(self::$vbulletin->versionnumber, '4.1.7') >= 0)
							{
								eval(print_standard_redirect(array('redirect_updatethanks', self::$vbulletin->userinfo['username']), true, true));
							}
							else
							{
								eval(print_standard_redirect('redirect_updatethanks', true, true));
							}
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
	* Fetches the type id of the specified type name
	*
	* @param	string		Type name
	* 
	* @return	integer		Type ID
	*/
	public static function fetch_type($typename)
	{
		$retval = 0;

		foreach (self::$cache['type'] as $typeid => $type)
		{
			if ($type['typename'] == $typename)
			{
				// This is the correct type
				$retval = $typeid;
				break;
			}
		}
		
		return $retval;
	}
		
	/**
	* Initialises a type class.
	*
	* @param	string	Type name
	*/
	public static function init_type($type)
	{
		if (!class_exists('vBActivity_Type_Core'))
		{
			// Include the needed class
			require_once(DIR . '/dbtech/vbactivity/type/core.php');
		}
		
		if (self::$types[$type['typename']])
		{
			// We don't need to init this
			return;
		}
		
		if (!$type['active'])
		{
			// We don't want to init this
			self::$types[$type['typename']] = new vBActivity_Type_Core(self::$vbulletin, $type);
			return;
		}
		
		$classname = 'vBActivity_Type_' . $type['typename'];
		if (!class_exists($classname))
		{
			// Include the needed class
			require_once(DIR . $type['filename']);
		}
		
		// Init the type
		self::$types[$type['typename']] = new $classname(self::$vbulletin, $type);
	}
	
	/**
	* Initialises a contest class.
	*
	* @param	string	Type name
	*/
	public static function initContest($contest)
	{
		if (!is_file(DIR . self::$cache['contesttype'][$contest['contesttypeid']]['filename']) OR $contest == NULL)
		{
			// We don't want to init this
			return false;
		}
		
		$classname = 'vBActivity_ContestType_' . ucfirst(self::$cache['contesttype'][$contest['contesttypeid']]['varname']);
		if (!class_exists($classname))
		{
			// Include the needed class
			require_once(DIR . self::$cache['contesttype'][$contest['contesttypeid']]['filename']);
		}
		
		// Return the object
		return new $classname(self::$vbulletin, $contest);
	}
	
	/**
	* Adds a winner to a contest and rewards them their points / medals if applicable
	*
	* @param	integer	The UserID that won
	* @param	integer	The ContestID information
	* @param	double	The number of points we had
	*/	
	public static function addContestWinner($userid, $contestid, $numpoints, $typename = 'contestswon', $multiplier = 1)
	{
		global $vbphrase;
		
		if (!$contest = self::$cache['contest'][$contestid])
		{
			// Contest didn't exist
			return false;
		}
		
		foreach ((array)$contest['winners'] as $place => $winnerInfo)
		{
			if ($winnerInfo['userid'] == $userid)
			{
				// Already won this contest
				return false;
			}
		}
		
		// Determine our place
		$place = count($contest['winners']) + 1;
		
		// Dupe this
		$winners = $contest['winners'];
		
		// Set winrar
		$winners[$place] = array(
			'userid' => $userid,
			'points' => $numpoints
		);
		
		if ($contest['prizes'][$place] AND self::$cache['medal'][$contest['prizes'][$place]])
		{
			// We had a prize
			$userinfo = array('userid' => $userid);
			
			// Add the reward
			self::add_reward('medal', $contest['prizes'][$place], $userinfo);
			
			// Rebuild the cache
			self::build_rewards_cache($userinfo);
		}
		
		// init data manager
		$dm =& self::initDataManager('Contest', self::$vbulletin, ERRTYPE_SILENT);
			$dm->set_existing($contest);				
			$dm->set('winners', $winners);
		$dm->save();
		
		// Insert points for contests won
		self::insert_points($typename, $contestid, $userid, $multiplier);
		
		if ($contest['prizes2'][$place])
		{
			// Insert points for contests won
			self::insert_points('contestprize', $contestid, $userid, $contest['prizes2'][$place]);
		}
		
		if ($contest['admin_notifs'])
		{
			// Fetch winner user info
			$winnerInfo = fetch_userinfo($userid);
			
			// Grab title and message
			$title = $vbphrase['dbtech_vbactivity_new_contest_winner_title'];
			$message = construct_phrase($vbphrase['dbtech_vbactivity_new_contest_winner_body'],
				self::$vbulletin->options['bburl'],
				$userid,
				$winnerInfo['username'],
				$place,
				$contest['title_translated'],
				$numpoints,
				($contest['prizes'][$place] ? self::$cache['medal'][$contest['prizes'][$place]]['title_translated'] : $vbphrase['n_a']),
				intval($contest['prizes2'][$place]),
				$contest['contestid']
			);
			
			$notifs = explode(',', $contest['admin_notifs']);
			foreach ($notifs as $recipient)
			{
				if (self::$vbulletin->options['dbtech_vbactivity_contestadminnotif_pm'])
				{
					// Who's the PM from
					$sender = 'dbtech_vbactivity_contestadminnotif_pm';
				}
				else
				{
					// Null this out since we had no defined sender
					$sender = $winnerInfo;
				}
				
				// Now send the PM
				self::sendPM($recipient, $title, $message, $sender);
			}
		}
		
		if ((int)$contest['winner_notifs'] & 1 OR (int)$contest['winner_notifs'] & 2)
		{
			// Fetch winner user info
			$winnerInfo = fetch_userinfo($userid);
			
			// Grab title and message
			$title = $vbphrase['dbtech_vbactivity_you_are_contest_winner_title'];
			$message = construct_phrase($vbphrase['dbtech_vbactivity_you_are_contest_winner_body'],
				self::$vbulletin->options['bburl'],
				$winnerInfo['username'],
				$place,
				$contest['title_translated'],
				$numpoints,
				($contest['prizes'][$place] ? self::$cache['medal'][$contest['prizes'][$place]]['title_translated'] : $vbphrase['n_a']),
				intval($contest['prizes2'][$place]),
				$contest['contestid']
			);
			
			if (((int)$contest['winner_notifs'] & 1) AND ((int)$winnerInfo['options'] & self::$vbulletin->bf_misc_useroptions['adminemail']))
			{
				if (!function_exists('convert_url_to_bbcode'))
				{
					// Ensure we can convert URL to BBCode
					require_once(DIR . '/includes/functions_newpost.php');
				}
				
				// Convert URL to BBCode
				$message = convert_url_to_bbcode($message);	
				
				// Parse the BBCode that we generated
				require_once(DIR . '/includes/class_bbcode.php');	
				$parser = new vB_BbCodeParser(self::$vbulletin, fetch_tag_list());
				$message = $parser->parse($message, 'nonforum', false);
				unset($parser);				
				
				// Send email to winner
				vbmail($winnerInfo['email'], $title, $message, true);
			}
			
			if ((int)$contest['winner_notifs'] & 2)
			{
				// Send PM to winner
				self::sendPM($userid, $title, $message, 'dbtech_vbactivity_contestwinnernotif_pm');				
			}
		}
	}
	
	/**
	* Gets information about the contest's (potential) winners.
	*
	* @param	integer	Contest ID
	* @param	boolean	Whether we're returning raw winner data
	*/	
	public static function getContestStanding($contestid, $winnersOnly = false, $numUsers = 0)
	{
		global $vbphrase;
		
		if (!$contest = self::$cache['contest'][$contestid])
		{
			// Invalid contest
			return false;
		}
		
		// Init this array
		$SQL = array();
		$winnerTmp = array();
		
		if ((($contest['show_progress'] AND $contest['end'] > TIMENOW) OR $winnersOnly) AND $contest['numwinners'])
		{
			// In case we have some winners in progress
			$winnerTmp = (array)$contest['winners'];
			
			$progressWinners = array(0);
			foreach ($winnerTmp as $place => $winnerInfo)
			{
				// Add to the winner array
				$progressWinners[] = $winnerInfo['userid'];
			}

			// In-progress contest that we're showing progress for
			$numUsers = $numUsers ? $numUsers : ($contest['numwinners'] - count($winnerTmp));
			
			// This contest is ongoing
			$winnerList = self::$db->fetchAll('
				SELECT points, userid
				FROM $dbtech_vbactivity_contestprogress
				WHERE contestid = ?
					AND userid NOT :userList
				ORDER BY points DESC
				LIMIT :limit
			', array(
				$contestid,
				':userList' 	=> self::$db->queryList((array)$progressWinners),
				':limit' 		=> $numUsers,
			));
			
			
			$winnersToSort = array();
			foreach ($winnerTmp as $place => $winnerInfo)
			{
				// Add to the winner array
				$winnersToSort[$winnerInfo['userid']] = $winnerInfo['points'];
			}
			foreach ($winnerList as $winner)
			{
				// Add to the winner array
				$winnersToSort[$winner['userid']] = $winner['points'];
			}
			arsort($winnersToSort, SORT_NUMERIC);
			
			if ($winnersOnly)
			{
				// We're only returning the raw winner data
				return $winnersToSort;
			}
			
			$winners = array();
			$i = 0;
			foreach ($winnersToSort as $winner => $points)
			{
				// Temporary winner display
				$winnerTmp[++$i] = array(
					'userid' => $winner,
					'points' => $points
				);
				
				if (in_array($winner, $SQL))
				{
					// We don't need more of this thank you
					continue;
				}
				
				if (is_array(self::$cachedUsers[$winner]))
				{
					// Already cached this user
					continue;
				}
				
				// We're looking up this userid
				$SQL[] = $winner;
			}
		}
		
		$winnerDisplay = array();
		if ($contest['end'] <= TIMENOW)
		{
			$winnerTmp = $contest['winners'];
			foreach ((array)$contest['winners'] as $winner)
			{
				if (in_array($winner['userid'], $SQL))
				{
					// We don't need more of this thank you
					continue;
				}
				
				if (is_array(self::$cachedUsers[$winner['userid']]))
				{
					// Already cached this user
					continue;
				}
				
				// We're looking up this userid
				$SQL[] = $winner['userid'];
			}
		}
		
		if (count($SQL))
		{
			if (!function_exists('fetch_avatar_from_userinfo'))
			{
				// Get the avatar function
				require_once(DIR . '/includes/functions_user.php');
			}
			
			$winners = self::$db->fetchAllKeyed('
				SELECT *, user.userid AS realuserid
				' . (self::$vbulletin->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : '') . '
				FROM  $user AS user
				' . (self::$vbulletin->options['avatarenabled'] ? '
				LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid)
				LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)
				' : '') . '
				WHERE user.userid :userList
			', 'realuserid', array(
				':userList' => self::$db->queryList($SQL),
			));
			foreach ($winners as $userid => $winner)
			{
				// No idea why this is needed
				$winners[$userid]['userid'] = $userid;
				
				// Grab markup username
				fetch_musername($winners[$userid]);
				
				// grab avatar from userinfo
				fetch_avatar_from_userinfo($winners[$userid], true);	
				
				// Cache this user
				self::$cachedUsers[$userid] = $winners[$userid];
			}
		}
		
		foreach ((array)$winnerTmp as $place => $winner)
		{
			$winner['place'] = $place;
			if ($contest['end'] <= TIMENOW)
			{
				switch ($place)
				{
					case 1:
						$winner['trophy'] = 'gold';
						break;
						
					case 2:
						$winner['trophy'] = 'silver';
						break;
						
					default:
						$winner['trophy'] = 'bronze';
						break;
				}
			}
			
			//self::$cachedUsers[$winner['userid']]
			//$winner
			// We're looking up this userid
			$templater = vB_Template::create('dbtech_vbactivity_contests_winner');
				$templater->register('winner', 		array_merge($winner, (array)self::$cachedUsers[$winner['userid']]));
			$winnerDisplay['winnerList'] .= $templater->render();
		}
		
		return $winnerDisplay;	
	}
	
	/**
	* Fetches the type id of the specified type name
	*
	* @param	string		Type name
	* @param	integer		(Optional) ID field we are inserting
	* @param	integer		(Optional) Userid we are granting points to
	* @param	integer		(Optional) For the "per reputation point" etc settings
	* @param	integer		(Optional) Dateline
	* @param	integer		(Optional) Forum ID of the source
	* 
	* @return	none		none
	*/	
	public static function insert_points($typename, $idfield = 0, $userid = 0, $multiplier = 1, $dateline = TIMENOW, $forumid = 0)
	{
		$typeid = self::fetch_type($typename);
		$type = self::$cache['type'][$typeid];
		
		// Store number of points for this action
		$type['points'] = (($forumid AND $type['pointsperforum'][$forumid] !== NULL AND $type['pointsperforum'][$forumid] != -1) ? $type['pointsperforum'][$forumid] : $type['points']);
		
		if ($type['points'] == 0 OR
			$multiplier == 0 OR
			!$type['active'])
		{
			// We aren't awarding/taking away any points, so ignore this
			return;
		}
		
		// Shorthand
		$userid = ($userid > 0 ? $userid : self::$vbulletin->userinfo['userid']);
		$points = ($type['points'] * $multiplier);
		
		// Round the points off to 2 decimals
		$points = round($points, 2);
		
		// Insert into pointslog
		self::$db->insert('dbtech_vbactivity_pointslog', array(
			'userid' 	=> $userid,
			'dateline' 	=> ($dateline ? $dateline : TIMENOW),
			'points' 	=> $points,
			'typeid' 	=> $typeid,
			'idfield' 	=> $idfield,
			'forumid' 	=> $forumid,
		));
		
		// Update users points
		self::$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_points
			SET $typename = $typename + " . doubleval($points) . "
			WHERE userid = '" . $userid . "'
		");
		
		// Update users points
		self::$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET
				dbtech_vbactivity_points = dbtech_vbactivity_points + " . self::$vbulletin->db->sql_prepare($points) . ",
				dbtech_vbactivity_pointscache = dbtech_vbactivity_pointscache + " . self::$vbulletin->db->sql_prepare($points) . ",
				dbtech_vbactivity_pointscache_day = dbtech_vbactivity_pointscache_day + " . self::$vbulletin->db->sql_prepare($points) . ",
				dbtech_vbactivity_pointscache_week = dbtech_vbactivity_pointscache_week + " . self::$vbulletin->db->sql_prepare($points) . ",
				dbtech_vbactivity_pointscache_month = dbtech_vbactivity_pointscache_month + " . self::$vbulletin->db->sql_prepare($points) . "
			WHERE userid = '" . $userid . "'
		");

		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_points_add')) ? eval($hook) : false;
		
		if ($userid == self::$vbulletin->userinfo['userid'])
		{
			// To make AJAX shit work properly n such
			self::$vbulletin->userinfo['dbtech_vbactivity_points'] += $points;
			self::$vbulletin->userinfo['dbtech_vbactivity_pointscache'] += $points;
			self::$vbulletin->userinfo['dbtech_vbactivity_pointscache_day'] += $points;
			self::$vbulletin->userinfo['dbtech_vbactivity_pointscache_week'] += $points;
			self::$vbulletin->userinfo['dbtech_vbactivity_pointscache_month'] += $points;
			
			// Store userinfo
			$userinfo = self::$vbulletin->userinfo;
		}
		else
		{
			// Fetch userinfo
			$userinfo = fetch_userinfo($userid);
		}
		
		if (!$userinfo['permissions'])
		{
			// For some reason, this is missing
			cache_permissions($userinfo);
		}
		
		if (($userinfo['permissions']['dbtech_vbactivitypermissions'] & self::$vbulletin->bf_ugp_dbtech_vbactivitypermissions['isexcluded_contests']) OR $userinfo['dbtech_vbactivity_excluded'])
		{
			// We're excluded
			return true;
		}
		
		if ($typename == 'contestprize')
		{
			// We're not counting this
			return true;
		}
		
		foreach (self::$cache['contest'] as $contestid => $contest)
		{
			if ($contest['start'] > $dateline)
			{
				// Contest hasn't started yet
				continue;
			}
			
			if ($contest['end'] <= $dateline)
			{
				// Contest has ended
				continue;
			}

			if (!is_array($contest['excludedcriteria']))
			{
				// For some reason
				$contest['excludedcriteria'] = array();
			}
			
			if (in_array($typeid, $contest['excludedcriteria']))
			{
				// We're not dealing with this criteria
				continue;
			}

			if (!is_array($contest['excludedforums']))
			{
				// For some reason
				$contest['excludedforums'] = array();
			}

			if ($forumid AND in_array($forumid, $contest['excludedforums']))
			{
				// We're not dealing with this forum
				continue;
			}
			
			// Update users points
			self::$db->query('
				INSERT INTO $dbtech_vbactivity_contestprogress
					(contestid, userid, points)
				VALUES (?, ?, :points)
				ON DUPLICATE KEY UPDATE
					points = points + :points
			', array(
				$contestid,
				$userid,
				':points' => doubleval($points)
			));
			
			if (self::$cache['contesttype'][$contest['contesttypeid']]['varname'] != 'target')
			{
				// Wrong contest type
				continue;
			}
						
			if (count($contest['winners']) >= $contest['numwinners'])
			{
				// We've already drawn all the winners we need
				continue;
			}
			
			// Detect winners
			$contest['winners'] = (is_array($contest['winners']) ? $contest['winners'] : array());
			
			foreach ((array)$contest['winners'] as $winnerid => $winner)
			{
				if ($winner['userid'] == $userid)
				{
					// Already won
					continue 2;
				}
			}
			
			// This contest is ongoing
			$numpoints = self::$db->fetchOne('
				SELECT points
				FROM $dbtech_vbactivity_contestprogress
				WHERE contestid = ?
					AND userid = ?
			', array(
				$contestid,
				$userid,
			));
			
			if ($numpoints < $contest['target'])
			{
				// No winrar
				continue;
			}
			
			// Add Contest winner
			self::addContestWinner($userid, $contestid, $numpoints);
		}
	}
		
	/**
	* Grabs our activity level
	*
	* @param	array	Information regarding the user we're checking
	*/	
	public static function fetch_activity_level(&$userinfo)
	{
		// Ensure this is set
		$userinfo['pointscache'] = round(($userinfo['pointscache'] ? $userinfo['pointscache'] : 0), 2);
		
		// Begin at 0 points
		$points = 0;
		$userinfo['activitylevel'] = 0;
		do
		{
			$userinfo['activitylevel']++;			
			$points = self::fetch_points_required($userinfo['activitylevel']);
		}
		while ($points < $userinfo['pointscache']);
		
		// Set our actual activity level
		$userinfo['activitylevel'] = ($userinfo['activitylevel'] > 1 ? ($userinfo['activitylevel'] - 1) : 1);
		
		$currentlevel 	= self::fetch_points_required($userinfo['activitylevel']);
		$nextlevel 		= self::fetch_points_required($userinfo['activitylevel'] + 1);
		$difference		= $nextlevel - $currentlevel;
		
		// Set TNL
		$userinfo['tonextlevel'] 	= $nextlevel - $userinfo['pointscache'];
		$userinfo['tnlpercent'] 	= (round(($userinfo['tonextlevel'] / $difference), 4) * 100);
		$userinfo['tnlpercent']		= round(($userinfo['tnlpercent'] > 100 ? 100 : $userinfo['tnlpercent']), 2);
		$userinfo['levelpercent'] 	= round(abs($userinfo['tnlpercent'] - 100), 2);
	}
	
	/**
	* Fetches how many points we need to reach a specified level
	*
	* @param	integer	The level we want to check
	*/	
	public static function fetch_points_required($level)
	{
		$retval = 0;
		
		if (!self::$levels[$level])
		{
			switch ($level)
			{
				case 1:
					$retval = 10;
					break;
					
				default:
					// Fetch number of points needed based on previous levels
					for ($i = $level; $i >= 1; $i--)
					{
						// Sum TNLs of all previous levels
						$retval += self::fetch_points_tnl($i);
					}
					break;
			}
			
			self::$levels[$level] = $retval;
		}
		else
		{
			// Grab from cache
			$retval = self::$levels[$level];
		}
		
		return $retval;
	}	
	
	/**
	* Fetches how many points we need to advance to the next level
	*
	* @param	integer	The level we currently are
	*/	
	private static function fetch_points_tnl($level)
	{
		$retval = 0;
		
		if (!self::$tnlvalues[$level])
		{
			switch ($level)
			{
				case 1:
					$retval = 10 + (10 * 0.025) + 10;
					break;
					
				default:
					// Fetch number of points needed based on previous levels
					$prevlevel = $level - 1;
					$retval = self::$tnlvalues[$prevlevel] + (self::$tnlvalues[$prevlevel] * 0.025) + self::$tnlvalues[1];
					break;
			}
			
			self::$tnlvalues[$level] = floor($retval);
		}
		else
		{
			// Grab from cache
			$retval = self::$tnlvalues[$level];
		}
		
		return floor($retval);
	}
	
	/**
	* Grabs our activity rating
	*
	* @param	array	Information regarding the user we're checking
	*/	
	public static function fetch_activity_rating(&$userinfo)
	{
		// Init this
		$userinfo['target'] = array();
		
		// Shorthand
		$target = self::$vbulletin->options['dbtech_vbactivity_activity_target'];			
		$target = ($target >= 1 ? $target : 1); // Avoid division by zero
		
		// Set targets
		$userinfo['target']['weekly_target'] 	= $target;
		$userinfo['target']['daily_target'] 	= round(($target / 7), 2);
		$userinfo['target']['monthly_target'] 	= round((($target / 7) * date('t')), 2);
		
		if ($userinfo['pointscache'] > 0)
		{			
			// %age of weekly target
			$userinfo['target']['weekly'] = self::calculate_target($userinfo['pointscache_week'], $userinfo['target']['weekly_target']);
			$userinfo['target']['weekly_bar'] = ($userinfo['target']['weekly'] > 100 ? 100 : $userinfo['target']['weekly']);
			
			// %age of daily target
			$userinfo['target']['daily'] = self::calculate_target($userinfo['pointscache_day'], $userinfo['target']['daily_target']);
			$userinfo['target']['daily_bar'] = ($userinfo['target']['daily'] > 100 ? 100 : $userinfo['target']['daily']);
			
			// %age of monthly target
			$userinfo['target']['monthly'] = self::calculate_target($userinfo['pointscache_month'], $userinfo['target']['monthly_target']);
			$userinfo['target']['monthly_bar'] = ($userinfo['target']['monthly'] > 100 ? 100 : $userinfo['target']['monthly']);
		}
		else
		{
			// Default values
			$userinfo['target']['weekly'] = $userinfo['target']['daily'] = $userinfo['target']['monthly'] = 
			$userinfo['target']['weekly_bar'] = $userinfo['target']['daily_bar'] = $userinfo['target']['monthly_bar'] = 0;
		}
	}
	
	/**
	* Calculates our relation to a target
	*
	* @param	float	The number of points we have
	* @param	float	The target points
	*
	* @return	float	How close in % we are to achieving the current target
	*/	
	private static function calculate_target($points, $target)
	{
		$result = round($points / ($target / 100), 2);
		return ($result >= 0 ? $result : 0);
	}
	
	/**
	* Checks whether we meet a certain criteria
	*
	* @param	integer	The criteria ID we are checking
	* @param	array	Information regarding the user we're checking
	* 
	* @return	boolean	Whether this criteria has been met
	*/	
	public static function check_criteria($conditionid, $userinfo, $typename = false)
	{
		if (!$condition = self::$cache['condition'][$conditionid])
		{
			// condition doesn't even exist
			return false;
		}
		
		if (!$typename)
		{
			// grab us the type name
			$typename = self::$cache['type'][$condition['typeid']]['typename'];
			$typename = ($condition['type'] == 'points' ? 'per' . $typename : $typename);
		}
		
		// Ensure these are set
		$userinfo[$typename] 		= ($userinfo[$typename] 	? $userinfo[$typename] 		: 0);
		$condition['value'] 		= ($condition['value'] 		? $condition['value'] 		: 0);
		$condition['comparison'] 	= ($condition['comparison'] ? $condition['comparison'] 	: '>');
		
		// Estupido
		$condition['comparison'] 	= ($condition['comparison'] == '=' ? '==' : $condition['comparison']);
		
		// Check the criteria
		eval('$retval = (' . $userinfo[$typename] . $condition['comparison'] . $condition['value'] . ');');		

		/*
		echo "<pre>";
		echo "$typename<br />";
		print_r($condition);
		echo '$retval = (' . $userinfo[$typename] . $condition['comparison'] . $condition['value'] . ');<br /><br />';
		die();
		*/
		
		return $retval;
	}
		
	/**
	* Checks whether we should be awarded a certain feature
	*
	* @param	string	The feature we are checking
	* @param	integer	The feature ID we are checking
	* @param	array	Information regarding the user we're checking
	* 
	* @return	boolean	Whether we met the criteria for this feature
	*/	
	public static function check_feature($feature, $featureid, &$userinfo)
	{
		if (!$featureinfo = self::$cache[$feature][$featureid])
		{
			// Feature doesn't even exist
			if (VB_AREA == 'AdminCP')
			{
				throw new Exception('invalid_feature', 1);
			}
			else
			{
				return false;
			}
		}
		
		if (isset($featureinfo['displayorder']) AND $featureinfo['displayorder'] <= 0)
		{
			// This is an inactive feature
			if (VB_AREA == 'AdminCP')
			{
				throw new Exception('inactive_feature', 2);
			}
			else
			{
				return false;
			}
		}
		
		// Fetch all criteria
		$conditioninfo = VBACTIVITY_FILTER::filter(self::$cache['conditionbridge'], 'featureid', $featureid);
		$conditioninfo = VBACTIVITY_FILTER::filter($conditioninfo, 'feature', $feature);
		
		if (!count($conditioninfo))
		{
			if (VB_AREA == 'AdminCP')
			{
				throw new Exception('no_criteria', 3);
			}
			else
			{
				// We had no criteria so just return true
				return true;
			}
		}

		foreach ($conditioninfo as $condition)
		{
			// Shorthand
			$condition 	= self::$cache['condition'][$condition['conditionid']];
			$typename 	= self::$cache['type'][$condition['typeid']]['typename'];
			
			// Inititalise this type
			self::init_type(self::$cache['type'][self::fetch_type($typename)]);
			
			// Recalculate all points based on the type name
			if (!self::$types[$typename]->check_criteria($condition['conditionid'], $userinfo))
			{
				if (VB_AREA == 'AdminCP' AND THIS_SCRIPT == 'vbactivity')
				{
					throw new Exception($typename, $condition['conditionid']);
				}
				else
				{
					// We didn't meet this criteria
					return false;
				}
			}
		}
		
		// We made it this far, we must have met all criteria
		return true;
	}

	/**
	* Checks whether we should be awarded certain features
	*
	* @param	string	The feature we're checking
	* @param	array	The list of types we are checking
	* @param	array	Information regarding the user we're checking
	* @param	boolean	(Optional) Whether we need to cache anything
	*/	
	public static function check_feature_by_typenames($feature, $typenames, &$userinfo)
	{
		if (!is_array($typenames))
		{
			$typenames = array();
		}
		
		foreach ((array)self::$cache['type'] as $typeid => $type)
		{
			if (!$type['active'] OR !($type['settings'] & 16) OR in_array($type['typename'], $typenames))
			{
				// Not a default type
				continue;
			}
			
			// Ensure we also check these rewards
			$typenames[] = $type['typename'];
		}
		
		// Ensure the cache is valid
		self::verify_rewards_cache($userinfo);
		
		$features = array();
		foreach ((array)self::$cache['conditionbridge'] as $conditionbridge)
		{
			if ($conditionbridge['feature'] != $feature)
			{
				// We're only checking a certain feature
				continue;
			}
			
			// Shorthand
			$condition 	= self::$cache['condition'][$conditionbridge['conditionid']];
			$typename 	= self::$cache['type'][$condition['typeid']]['typename'];
			
			if (!in_array($typename, $typenames))
			{
				// We're not checking this feature
				continue;
			}
			
			// This feature is in
			$features[] = $conditionbridge['featureid'];
		}
		
		$rebuild = false;
		foreach ($features as $featureid)
		{
			// Recalculate all points based on the type name
			if (!self::check_feature($feature, $featureid, $userinfo))
			{
				if (!is_array($userinfo['dbtech_vbactivity_rewardscache']))
				{
					// We need to rebuild
					$rebuild = true;
					break;
				}
				
				// Didn't meet criteria
				foreach ($userinfo['dbtech_vbactivity_rewardscache'] as $rewardid => $reward)
				{
					if ($reward['feature'] == $feature AND $reward['featureid'] == $featureid)
					{
						// we had this reward, let's kill it
						self::$vbulletin->db->query_first_slave("DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards WHERE rewardid = " . intval($rewardid));
						
						// We need to rebuild the rewards cache
						$rebuild = true;
					}
				}				

				continue;
			}
			
			// We hit this feature			
			self::add_reward($feature, $featureid, $userinfo);
		}
		
		if ($rebuild)
		{
			// Build the rewards cache
			self::build_rewards_cache($userinfo);
		}
	}
	
	/**
	* Adds a notification for a certain event.
	*
	* @param	string	The event we are adding a notification for
	* @param	integer	The user ID of the person receiving the notification
	*/	
	public static function add_notification($feature, $featureid = 0, $userid = 0, $count = 1)
	{
		// Ensure the user id is set properly
		$userid = ($userid ? $userid : self::$vbulletin->userinfo['userid']);
		
		require_once(DIR . '/includes/class_bitfield_builder.php');
		$bitfields = vB_Bitfield_Builder::return_data();
		
		if (!(self::$vbulletin->options['dbtech_vbactivity_notifications'] & (int)$bitfields['nocache']['dbtech_vbactivity_notifications']["dbtech_vbactivity_{$feature}s"]))
		{
			// Notifications for this feature was disabled
			return;
		}
		
		// Update the notifications area
		self::$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "user SET dbtech_vbactivity_{$feature}count = dbtech_vbactivity_{$feature}count + $count WHERE userid = " . $userid);

		global $vbphrase;
		if (!self::$cachedUsers[$userid])
		{
			// Fetch winner user info
			self::$cachedUsers[$userid] = fetch_userinfo($userid);
		}

		if (
			!(self::$vbulletin->options['dbtech_vbactivity_notifications_pm'] & (int)$bitfields['nocache']['dbtech_vbactivity_notifications']["dbtech_vbactivity_{$feature}s"]) OR 
			(self::$cachedUsers[$userid]['dbtech_vbactivity_settings'] & 4096)
		)
		{
			// We don't want PM notifs
			return;
		}

		if ($feature == 'trophy')
		{
			if (!is_array($featureid))
			{
				// Ensure this is an array
				$featureid = array($featureid);
			}

			$featureTitle = array();
			foreach ($featureid as $featureId)
			{
				// Store the phrase for this type
				$featureTitle[] = $vbphrase['dbtech_vbactivity_condition_' . $featureId];
			}

			// Impode this
			$featureTitle = implode("\n[*]", $featureTitle);
		}
		else 
		{
			// Shorthand
			$featureTitle = isset(self::$cache[$feature][$featureid]['title_translated']) ? self::$cache[$feature][$featureid]['title_translated'] : self::$cache[$feature][$featureid]['title'];
		}
		
		// Grab title and message
		$title = $vbphrase["dbtech_vbactivity_new_{$feature}_title"];
		$message = construct_phrase($vbphrase["dbtech_vbactivity_new_{$feature}_body"],
			$featureTitle,
			self::$vbulletin->options['bburl'],
			$userid,
			self::$vbulletin->options['bbtitle']
		);

		// Send a new PM
		self::sendPM(self::$cachedUsers[$userid], $title, $message, 'dbtech_vbactivity_new_' . $feature . '_pm');
	}
	
	/**
	* Removes notifications for a certain event.
	*
	* @param	string	The event we are removing notifications for
	* @param	integer	The user ID of the person involved
	*/	
	public static function remove_notification($feature, $userid = 0)
	{
		// Ensure the user id is set properly
		$userid = ($userid ? $userid : self::$vbulletin->userinfo['userid']);
		
		// Update the notifications area
		self::$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "user SET dbtech_vbactivity_{$feature}count = 0 WHERE userid = " . $userid);
		
		
		if ($userid == self::$vbulletin->userinfo['userid'])
		{
			// Also remove the count
			self::$vbulletin->userinfo["dbtech_vbactivity_{$feature}count"] = 0;
		}
	}
	
	/**
	* Adds a reward for meeting certain criteria.
	*
	* @param	string	The type of reward we earned
	* @param	integer	The ID of the reward we earned
	* @param	array	The user who earned this reward
	* @param	string	(Optional) The reason for gaining this award
	*/
	public static function add_reward($feature, $featureid, &$userinfo, $reason = '', $hideNotification = false)
	{
		if (!$userinfo['userid'])
		{
			// This isn't a valid user
			return false;
		}
		
		if ($feature != 'medal')
		{
			// Verify that the cache is enabled
			self::verify_rewards_cache($userinfo);
					
			foreach ((array)$userinfo['dbtech_vbactivity_rewardscache'] AS $rewardid => $reward)
			{
				if ($reward['feature'] != $feature)
				{
					// Wrong feature or medal - we're always gonna re-add medals
					continue;
				}
				
				if ($reward['featureid'] == $featureid)
				{
					// We already had this reward
					return false;
				}
			}
		}
		
		if ($feature == 'promotion')
		{
			// Shorthand
			$info = self::$cache['promotion'][$featureid];
			
			if (!isset($userinfo['usergroupid']))
			{
				// Add usergroup to the userinfo array
				$userinfo = array_merge((array)$userinfo, fetch_userinfo($userinfo['userid']));
			}
			
			if ($userinfo['usergroupid'] == $info['fromusergroupid'])
			{
				// Insert the reward
				self::insert_reward($feature, $featureid, $userinfo);
				
				if (!$hideNotification)
				{
					// Add a notification about gained reward
					self::add_notification($feature, $featureid, $userinfo['userid']);
				}
				
				// Apply the promotion
				self::$vbulletin->db->query_write("
					UPDATE " . TABLE_PREFIX . "user
					SET usergroupid = " . self::$vbulletin->db->sql_prepare($info['tousergroupid']) . "
					WHERE userid = " . $userinfo['userid']
				);
			}
		}
		else
		{
			// Insert the reward
			self::insert_reward($feature, $featureid, $userinfo, $reason);
			
			if (!$hideNotification)
			{
				// Add a notification about gained reward
				self::add_notification($feature, $featureid, $userinfo['userid']);
			}
		}
		
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_add_reward_end')) ? eval($hook) : false;
	}
	
	/**
	* Ensures the rewards cache is enabled and working
	*
	* @param	array	The user whose cache we're ensuring
	* @param	boolean	(Optional) Do run cache update query?
	* @param	boolean	(Optional) Do attempt to unserialize?
	*/
	public static function verify_rewards_cache(&$userinfo, $updatecache = true, $unserialize = true)
	{
		if (!$userinfo['userid'])
		{
			// This isn't a valid user
			$userinfo['dbtech_vbactivity_rewardscache'] = array();
			return false;
		}
		
		if (!isset($userinfo['dbtech_vbactivity_rewardscache']))
		{
			// Add rewards to the userinfo array
			$extrauserinfo = fetch_userinfo($userinfo['userid'], 1);
			$userinfo['dbtech_vbactivity_rewardscache'] = $extrauserinfo['dbtech_vbactivity_rewardscache'];
		}
		
		if ($unserialize AND !is_array($userinfo['dbtech_vbactivity_rewardscache']))
		{
			// Attempt to unserialize
			$userinfo['dbtech_vbactivity_rewardscache'] = @unserialize($userinfo['dbtech_vbactivity_rewardscache']);
		}
		
		// Default to not refresh cache
		$refreshcache = false;
		if (empty($userinfo['dbtech_vbactivity_rewardscache']))
		{
			// Do refresh the cache
			$refreshcache = true;
		}
		
		if ($refreshcache AND $updatecache)
		{
			// Rebuild the rewards cache for this user
			self::build_rewards_cache($userinfo);
		}
	}
	
	/**
	* Rebuilds the rewards cache
	*
	* @param	array	The user whose cache we're ensuring
	*/	
	public static function build_rewards_cache(&$userinfo)
	{
		if (!$userinfo['userid'])
		{
			// This isn't a valid user
			return false;
		}
		
		// Has no rewards cache generated
		$userinfo['dbtech_vbactivity_rewardscache'] = array();
		
		// Insert the reward
		$rewards_q = self::$vbulletin->db->query_read_slave("
			SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards
			WHERE userid = " . $userinfo['userid'] . "
			ORDER BY dateline DESC
		");
		while ($rewards_r = self::$vbulletin->db->fetch_array($rewards_q))
		{
			// Set the cache
			$userinfo['dbtech_vbactivity_rewardscache'][$rewards_r['rewardid']] = $rewards_r;
		}
		self::$vbulletin->db->free_result($rewards_q);
		unset($rewards_r);				
		
		// Update the database
		self::$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET dbtech_vbactivity_rewardscache = '" . self::$vbulletin->db->escape_string(trim(serialize($userinfo['dbtech_vbactivity_rewardscache']))) . "'
			WHERE userid = " . $userinfo['userid'] . "
		");
	}
	
	/**
	* Adds a reward for meeting certain criteria.
	*
	* @param	string	The type of reward we earned
	* @param	integer	The ID of the reward we earned
	* @param	array	The user who earned this reward
	* @param	string	(Optional) The reason for gaining this award
	*/	
	private static function insert_reward($feature, $featureid, &$userinfo, $reason = '')
	{
		// Grant the reward to the user
		$rewardid = self::$db->insert('dbtech_vbactivity_rewards', array(
			'userid' 	=> $userinfo['userid'],
			'feature' 	=> $feature,
			'featureid' => $featureid,
			'dateline' 	=> TIMENOW,
			'reason' 	=> $reason,
		));
		
		if ($feature != 'medal')
		{
			// Rebuild the cache
			self::build_rewards_cache($userinfo);
		}
	}
	
	/**
	* Rebuilds the datastore with the avg points in the past 7 days
	*/
	public static function build_points_cache()
	{	
		// Set the excluded parameters
		self::set_excluded_param();
		
		$typeids = array();
		foreach (self::$cache['type'] as $typeid => $type)
		{
			if (!(int)$type['display'] & 2)
			{
				// Hide this type
				$typeids[] = $typeid;
				continue;
			}
		}
		
		// Ensure we have this working
		$typeids = (count($typeids) ? 'pointslog.typeid NOT IN(' . implode(',', $typeids) . ') AND ' : '');
	
		$points = 0.0;
		$count = 0;
		$points_q = self::$vbulletin->db->query_read_slave("
			SELECT user.userid, SUM(points) AS totalpoints
			FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog AS pointslog
			LEFT JOIN " . TABLE_PREFIX . "user AS user USING(userid)
			LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON (usergroup.usergroupid = user.usergroupid)
			WHERE $typeids
				user.dbtech_vbactivity_excluded_tmp = '0'
				AND pointslog.dateline >= (" . TIMENOW . " - 604800)
			GROUP BY userid
			HAVING totalpoints >= " . doubleval(self::$vbulletin->options['dbtech_vbactivity_activity_target_minpoints']) . "
		");
		while ($points_r = self::$vbulletin->db->fetch_array($points_q))
		{
			// Generate the points
			$points += $points_r['totalpoints'];
			$count++;
		}
		self::$vbulletin->db->free_result($points_q);
		unset($points_r);
		
		// Ensure the target is valid so we don't divide by zero
		$count = (intval($count) < 1 ? 1 : $count);
		$points = (intval($points) < 1 ? 1 : round(doubleval($points / $count), 2));
		
		// Set the points cache
		self::$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "setting
			SET value = $points
			WHERE varname = 'dbtech_vbactivity_activity_target'
		");		
		
		if (!function_exists('build_options'))
		{
			// Ensure we have this function
			require(DIR . '/includes/adminfunctions.php');
		}
		
		// Build the settings
		build_options();		
	}
	
	/**
	* Fetches excluded users / usergroupids
	*
	* @param	string	The action we're performing
	*
	* @return	string	The exclude SQL string
	*/
	public static function set_excluded_param($action = '')
	{
		$excluded = array();
		foreach (self::$vbulletin->usergroupcache as $usergroupid => $usergroup)
		{
			if (!($usergroup['genericoptions'] & self::$vbulletin->bf_ugp_genericoptions['isnotbannedgroup']) OR 
				($usergroup['dbtech_vbactivitypermissions'] & self::$vbulletin->bf_ugp_dbtech_vbactivitypermissions['isexcluded'])
			)
			{
				// Banned or excluded group
				$excluded[] = $usergroupid;
			}
			
			switch ($action)
			{
				case 'trophy':
					if (!in_array($usergroupid, $excluded) AND ($usergroup['dbtech_vbactivitypermissions'] & self::$vbulletin->bf_ugp_dbtech_vbactivitypermissions['isexcluded_trophies']))
					{
						// Banned or excluded group
						$excluded[] = $usergroupid;
					}
					break;
				
				case 'contest':
					if (!in_array($usergroupid, $excluded) AND ($usergroup['dbtech_vbactivitypermissions'] & self::$vbulletin->bf_ugp_dbtech_vbactivitypermissions['isexcluded_contests']))
					{
						// Banned or excluded group
						$excluded[] = $usergroupid;
					}
					break;
					
				default:
					// Do nothing
					break;
			}
		}
		
		self::$vbulletin->db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET dbtech_vbactivity_excluded_tmp = IF(dbtech_vbactivity_excluded = '1'
				" . ($excluded ? "OR FIND_IN_SET(" . implode(', membergroupids) OR FIND_IN_SET(', $excluded) . ", membergroupids)
				OR usergroupid IN(" . implode(',', $excluded) . ")" : '') . ", '1', '0')
		");
	}
	
	/**
	* Adds a new type to the mix
	*
	* @param	string	The action we're performing
	*
	* @return	string	The exclude SQL string
	*/	
	public static function add_type($typename, $text, $product, $filename, $showforum = false)
	{
		// Ensure we can do language verifications
		require_once(DIR . '/includes/adminfunctions_language.php');

		if (!preg_match('#^[a-z0-9_\[\]]+$#i', $typename)) // match a-z, A-Z, 0-9, ',', _ only .. allow [] for help items
		{
			print_stop_message('invalid_phrase_varname');
		}

		/*insert query*/
		self::$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, varname, text, fieldname, product, username, dateline, version)
			VALUES
				(-1,
				'dbtech_vbactivity_condition_" . $typename . "',
				'" . self::$vbulletin->db->escape_string($text) . "',
				'global',
				'" . self::$vbulletin->db->escape_string($product) . "',
				'" . self::$vbulletin->db->escape_string(self::$vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'1.0.0')
		");
		
		/*insert query*/
		self::$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "phrase
				(languageid, varname, text, fieldname, product, username, dateline, version)
			VALUES
				(-1,
				'dbtech_vbactivity_condition_per" . $typename . "',
				'Points For " . self::$vbulletin->db->escape_string($text) . "',
				'global',
				'" . self::$vbulletin->db->escape_string($product) . "',
				'" . self::$vbulletin->db->escape_string(self::$vbulletin->userinfo['username']) . "',
				" . TIMENOW . ",
				'1.0.0')
		");
		
		// Rebuild the language
		build_language(-1);
		
		if (self::fetch_type($typename))
		{
			// Already added
			return false;
		}
				
		// init data manager
		$dm =& self::initDataManager('Type', self::$vbulletin, ERRTYPE_CP);
			$dm->set('typename', 	$typename);
			$dm->set('filename', 	$filename);
			$dm->set('points', 		1);
			$dm->set('settings', 	($showforum ? 47 : 15));
			$dm->set('display', 	7);
		$dm->save();
		unset($dm);
		
		return true;
	}	
}

// #############################################################################
// database functionality class

/**
* Class that handles database wrapper
*/
class vBActivity_Database
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
	* The query result we executed
	*
	* @private	MySQL_Result
	*/	
	private $resultLoopable;
	
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
	 * Hides DB errrors
	 * 
	 * @return void
	 */
	public function hideErrors()
	{
		$this->db->hide_errors();
	}

	/**
	 * Shows DB errrors
	 * 
	 * @return void
	 */
	public function showErrors()
	{
		$this->db->show_errors();
	}

	/**
	 * Inserts a table row with specified data.
	 *
	 * @param mixed $table The table to insert data into.
	 * @param array $bind Column-value pairs.
	 * @param array $exclusions Array of field names that should be ignored from the $queryvalues array
	 * @param boolean $displayErrors Whether SQL errors should be displayed
	 * @param string $type Whether it's insert, insert ignore or replace
	 * 
	 * @return int The number of affected rows.
	 */
	public function insert($table, array $bind, array $exclusions = array(), $displayErrors = true, $type = 'insert')
	{
		// Store the query
		$sql = fetch_query_sql($bind, $table, '', $exclusions);

		switch ($type)
		{
			case 'ignore':
				$sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql);
				break;

			case 'replace':
				$sql = str_replace('INSERT INTO', 'REPLACE INTO', $sql);
				break;
		}
		
		if ($this->debug)
		{
			echo "<pre>";
			echo $sql;
			echo "</pre>";
			die();
		}
		
		if (!$displayErrors)
		{
			$this->db->hide_errors();
		}
		$this->db->query_write($sql);
		if (!$displayErrors)
		{
			$this->db->show_errors();
		}

		// Return insert ID if only one row was inserted, otherwise return number of affected rows
		$affected = $this->db->affected_rows();
		return($affected === 1 ? $this->db->insert_id() : $affected);
	}

	/**
	 * Inserts a table row with specified data, ignoring duplicates.
	 *
	 * @param mixed $table The table to insert data into.
	 * @param array $bind Column-value pairs.
	 * @param array $exclusions Array of field names that should be ignored from the $queryvalues array
	 * @param boolean $displayErrors Whether SQL errors should be displayed
	 * 
	 * @return int The number of affected rows.
	 */
	public function insertIgnore($table, array $bind, array $exclusions = array(), $displayErrors = true)
	{
		return $this->insert($table, $bind, $exclusions, $displayErrors, 'ignore');
	}

	/**
	 * Inserts a table row with specified data, replacing duplicates.
	 *
	 * @param mixed $table The table to insert data into.
	 * @param array $bind Column-value pairs.
	 * @param array $exclusions Array of field names that should be ignored from the $queryvalues array
	 * @param boolean $displayErrors Whether SQL errors should be displayed
	 * 
	 * @return int The number of affected rows.
	 */
	public function replace($table, array $bind, array $exclusions = array(), $displayErrors = true)
	{
		return $this->insert($table, $bind, $exclusions, $displayErrors, 'replace');
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
	 * Fetches all SQL result rows and returns loopable object.
	 *
	 * @param string $sql  An SQL SELECT statement.
	 * @param mixed  $bind Data to bind into SELECT placeholders.
	 * 
	 * @return array
	 */
	public function fetchAllObject($sql, $bind = array())
	{
		$this->resultLoopable = $this->query($sql, $bind, 'query_read');
		return $this->resultLoopable;
	}
	
	/**
	 * Fetches all SQL result rows and returns loopable object.
	 *
	 * @param string $sql  An SQL SELECT statement.
	 * @param mixed  $bind Data to bind into SELECT placeholders.
	 * 
	 * @return array
	 */
	public function fetchCurrent()
	{
		return $this->db->fetch_array($this->resultLoopable);
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

		if (in_array($which, array('query_read', 'query_first')))
		{
			// Support slave servers
			$which .= '_slave';
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
			$values[] = "'" . (is_numeric($val) ? $val : $this->db->escape_string($val)) . "'";
		}
		
		if (!count($values))
		{
			// Ensure there's no SQL errors
			$values[] = "'0'";
		}
		
		return 'IN(' . implode(', ', $values) . ')';
	}
}

// #############################################################################
// filter functionality class

/**
* Class that handles filtering arrays
*/
class VBACTIVITY_FILTER
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
		return ($array[$idfield] == $idval);
	}
}

if (!function_exists('is_pcre_unicode'))
{
// #############################################################################
/**
 * Checks if PCRE supports unicode
 *
 * @return bool
 */
function is_pcre_unicode()
{
	static $enabled;

	if (NULL !== $enabled)
	{
		return $enabled;
	}

	return $enabled = @preg_match('#\pN#u', '1');
}
}

if (!function_exists('ncrencode_matches'))
{
/**
 * NCR encodes matches from a preg_replace.
 * Single byte characters are preserved.
 *
 * @param	string	The character to encode
 * @return	string	The encoded character
 */
function ncrencode_matches($matches, $skip_ascii = false, $skip_win = false)
{
	$ord = ord_uni($matches[0]);

	if ($skip_win)
	{
		$start = 254;
	}
	else
	{
		$start = 128;
	}

	if ($skip_ascii AND $ord < $start)
	{
		return $matches[0];
	}

	return '&#' . ord_uni($matches[0]) . ';';
}
}

if (!function_exists('ord_uni'))
{
/**
 * Gets the Unicode Ordinal for a UTF-8 character.
 *
 * @param	string	Character to convert
 * @return	int		Ordinal value or false if invalid
 */
function ord_uni($chr)
{
	// Valid lengths and first byte ranges
	static $check_len = array(
		1 => array(0, 127),
		2 => array(192, 223),
		3 => array(224, 239),
		4 => array(240, 247),
		5 => array(248, 251),
		6 => array(252, 253)
	);

	// Get length
	$blen = strlen($chr);

	// Get single byte ordinals
	$b = array();
	for ($i = 0; $i < $blen; $i++)
	{
		$b[$i] = ord($chr[$i]);
	}

	// Check expected length
	foreach ($check_len AS $len => $range)
	{
		if (($b[0] >= $range[0]) AND ($b[0] <= $range[1]))
		{
			$elen = $len;
		}
	}

	// If no range found, or chr is too short then it's invalid
	if (!isset($elen) OR ($blen < $elen))
	{
		return false;
	}

	// Normalise based on octet-sequence length
	switch ($elen)
	{
		case (1):
			return $b[0];
		case (2):
			return ($b[0] - 192) * 64 + ($b[1] - 128);
		case (3):
			return ($b[0] - 224) * 4096 + ($b[1] - 128) * 64 + ($b[2] - 128);
		case (4):
			return ($b[0] - 240) * 262144 + ($b[1] - 128) * 4096 + ($b[2] - 128) * 64 + ($b[3] - 128);
		case (5):
			return ($b[0] - 248) * 16777216 + ($b[1] - 128) * 262144 + ($b[2] - 128) * 4096 + ($b[3] - 128) * 64 + ($b[4] - 128);
		case (6):
			return ($b[0] - 252) * 1073741824 + ($b[1] - 128) * 16777216 + ($b[2] - 128) * 262144 + ($b[3] - 128) * 4096 + ($b[4] - 128) * 64 + ($b[5] - 128);
	}
}
}