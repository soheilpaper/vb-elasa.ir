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

if (!class_exists('vB_DataManager', false))
{
	exit;
}

/**
* Class to do data save/delete operations for contenttypes
*
* @package	livewall
*/
class LiveWall_DataManager_Contenttype extends vB_DataManager
{
	/**
	* Array of recognised and required fields for contenttypes, and their contenttypes
	*
	* @var	array
	*/
	var $validfields = array(
		'contenttypeid' 	=> array(TYPE_STR, 		REQ_YES, 	VF_METHOD),
		'title' 			=> array(TYPE_STR, 		REQ_YES, 	VF_METHOD),
		'active' 			=> array(TYPE_STR, 		REQ_NO, 	VF_METHOD, 	'verify_onoff'),
		'filename' 			=> array(TYPE_STR, 		REQ_YES, 	VF_METHOD),
		'permissions' 		=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 	'verify_serialized'),
		'code' 				=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 	'verify_serialized'),
		'preview' 			=> array(TYPE_UINT, 	REQ_NO),
		'preview_sidebar' 	=> array(TYPE_UINT, 	REQ_NO),
	);

	/**
	* Array of field names that are bitfields, together with the name of the variable in the registry with the definitions.
	*
	* @var	array
	*/
	//var $bitfields = array('adminpermissions' => 'bf_ugp_adminpermissions');

	/**
	* The main table this class deals with
	*
	* @var	string
	*/
	var $table = 'dbtech_livewall_contenttype';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('contenttypeid = \'%1$s\'', 'contenttypeid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function LiveWall_DataManager_Contenttype(&$registry, $errcontenttype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errcontenttype);

		($hook = vBulletinHook::fetch_hook('dbtech_livewall_contenttypedata_start')) ? eval($hook) : false;
	}

	/**
	* Verifies that the contenttypeid is valid
	*
	* @param	string	contenttypeid of the contenttype
	*
	* @return	boolean
	*/
	function verify_contenttypeid(&$contenttypeid)
	{
		global $vbphrase;
		
		$contenttypeid = strval($contenttypeid);
		if ($contenttypeid === '')
		{
			// Invalid
			return false;
		}
		
		// Check for existing contenttype of this name
		if ($existing = $this->registry->db->query_first("
			SELECT `contenttypeid`
			FROM `" . TABLE_PREFIX . "dbtech_livewall_contenttype`
			WHERE `contenttypeid` = " . $this->registry->db->sql_prepare($contenttypeid) . "
				" . ($this->existing['contenttypeid'] ? "AND `contenttypeid` != " . $this->registry->db->sql_prepare($this->existing['contenttypeid']) : '') . "			
			LIMIT 1
		"))
		{
			// Whoopsie, exists
			$this->error('dbtech_livewall_x_already_exists_y', $vbphrase['dbtech_livewall_contenttype'], $contenttypeid);
			return false;
		}
		
		return true;
	}

	/**
	* Verifies that the title is valid
	*
	* @param	string	title of the contenttype
	*
	* @return	boolean
	*/
	function verify_title(&$title)
	{
		global $vbphrase;
		
		$title = strval($title);
		if ($title === '')
		{
			// Invalid
			return false;
		}
		
		// Check for existing contenttype of this name
		if ($existing = $this->registry->db->query_first("
			SELECT `title`
			FROM `" . TABLE_PREFIX . "dbtech_livewall_contenttype`
			WHERE `title` = " . $this->registry->db->sql_prepare($title) . "
				" . ($this->existing['contenttypeid'] ? "AND `contenttypeid` != " . $this->registry->db->sql_prepare($this->existing['contenttypeid']) : '') . "			
			LIMIT 1
		"))
		{
			// Whoopsie, exists
			$this->error('dbtech_livewall_x_already_exists_y', $vbphrase['dbtech_livewall_contenttype'], $title);
			return false;
		}
		
		return true;
	}

	/**
	* Verifies that the onoff flag is valid
	*
	* @param	string	On/Off flag
	*
	* @return	boolean
	*/
	function verify_onoff(&$onoff)
	{
		// Validate onoff
		$onoff = (!in_array((int)$onoff, array(0, 1)) ? 1 : $onoff);
		
		return true;
	}

	/**
	* Verifies that the filename is valid
	*
	* @param	string	filename
	*
	* @return	boolean
	*/
	function verify_filename(&$filename)
	{
		// Check whether the file name is valid
		return is_file(DIR . '/' . $filename);
	}

	/**
	* Any checks to run immediately before saving. If returning false, the save will not take place.
	*
	* @param	boolean	Do the query?
	*
	* @return	boolean	True on success; false if an error occurred
	*/
	function pre_save($doquery = true)
	{
		if ($this->presave_called !== null)
		{
			return $this->presave_called;
		}
		
		$return_value = true;
		($hook = vBulletinHook::fetch_hook('dbtech_livewall_contenttypedata_presave')) ? eval($hook) : false;

		$this->presave_called = $return_value;
		return $return_value;
	}
	
	/**
	* Additional data to update before a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function pre_delete($doquery = true)
	{
		$return_value = true;
		($hook = vBulletinHook::fetch_hook('dbtech_livewall_contenttypedata_predelete')) ? eval($hook) : false;

		$this->presave_called = $return_value;
		return $return_value;
	}

	/**
	* Additional data to update after a save call (such as denormalized values in other tables).
	* In batch updates, is executed for each record updated.
	*
	* @param	boolean	Do the query?
	*/
	function post_save_each($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_livewall_contenttypedata_postsave')) ? eval($hook) : false;
		
		if (!$this->condition)
		{
			if (!class_exists('vB_Database_Alter_MySQL'))
			{
				// Grab the dbalter class
				require(DIR . '/includes/class_dbalter.php');
			}
			
			// Init db alter
			$db_alter = new vB_Database_Alter_MySQL($this->registry->db);
			
			if ($db_alter->fetch_table_info('dbtech_livewall_settings'))
			{
				// Add the fields we need
				$db_alter->add_field(array(
					'name'       => $this->fetch_field('contenttypeid') . '_display',
					'type'       => 'tinyint',
					'length'     => '1',
					'attributes' => 'unsigned',
					'null'       => false,	// True = NULL, false = NOT NULL
					'default'    => '0'
				));
				$db_alter->add_field(array(
					'name'       => $this->fetch_field('contenttypeid') . '_privacy',
					'type'       => 'tinyint',
					'length'     => '1',
					'attributes' => 'unsigned',
					'null'       => false,	// True = NULL, false = NOT NULL
					'default'    => '0'
				));
			}
		}
		
		// Rebuild the cache
		LIVEWALL_CACHE::build_cache('contenttype', 'ORDER BY `title` ASC');

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_livewall_contenttypedata_delete')) ? eval($hook) : false;
		
		if (!class_exists('vB_Database_Alter_MySQL'))
		{
			// Grab the dbalter class
			require(DIR . '/includes/class_dbalter.php');
		}
		
		// Init db alter
		$db_alter = new vB_Database_Alter_MySQL($this->registry->db);
		
		if ($db_alter->fetch_table_info('dbtech_livewall_settings'))
		{		
			// Add the fields we need
			$db_alter->drop_field($this->fetch_field('contenttypeid') . '_display');
			$db_alter->drop_field($this->fetch_field('contenttypeid') . '_privacy');
		}		
		
		// Rebuild the cache
		LIVEWALL_CACHE::build_cache('contenttype', 'ORDER BY `title` ASC');
		
		return true;
	}
}


/*======================================================================*\
|| ####################################################################
|| # Created: 16:52, Sat Dec 26th 2009
|| # SVN: $ $Rev$ $ - $ $Date$ $
|| ####################################################################
\*======================================================================*/