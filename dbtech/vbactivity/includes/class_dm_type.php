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

if (!class_exists('vB_DataManager', false))
{
	exit;
}

/**
* Class to do data save/delete operations for types
*
* @package	vbactivity
*/
class vBActivity_DataManager_Type extends vB_DataManager
{
	/**
	* Array of recognised and required fields for types, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'typeid' 		=> array(TYPE_UINT, 	REQ_INCR, 	VF_METHOD, 	'verify_nonzero'),
		'typename' 		=> array(TYPE_STR, 		REQ_YES, 	VF_METHOD),
		'active' 		=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD, 	'verify_onoff'),
		'userid' 		=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD, 	'verify_userid'),
		'trophyname' 	=> array(TYPE_STR, 		REQ_NO),
		'icon' 			=> array(TYPE_STR, 		REQ_NO),
		'points' 		=> array(TYPE_NUM, 		REQ_NO),
		'pointsperforum'=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 	'verify_serialized'),
		'display' 		=> array(TYPE_UINT, 	REQ_NO),
		'settings' 		=> array(TYPE_UINT, 	REQ_NO),
		'filename' 		=> array(TYPE_STR, 		REQ_NO),
		'sortorder' 	=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD, 	'verify_onoff'),
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
	var $table = 'dbtech_vbactivity_type';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('typeid = %1$d', 'typeid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vBActivity_DataManager_Type(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_typedata_start')) ? eval($hook) : false;
	}

	/**
	* Verifies that the typename is valid
	*
	* @param	string	typename of the type
	*
	* @return	boolean
	*/
	function verify_typename(&$typename)
	{
		global $vbphrase;
		
		$typename = strval($typename);
		if ($typename === '')
		{
			// Invalid
			return false;
		}
		
		// Check for existing type of this name
		if ($existing = $this->registry->db->query_first_slave("
			SELECT `typename`
			FROM `" . TABLE_PREFIX . "dbtech_vbactivity_type`
			WHERE `typename` = " . $this->registry->db->sql_prepare($typename) . "
				" . ($this->existing['typeid'] ? "AND `typeid` != " . $this->registry->db->sql_prepare($this->existing['typeid']) : '') . "			
			LIMIT 1
		"))
		{
			// Whoopsie, exists
			$this->error('dbtech_vbactivity_x_already_exists_y', $vbphrase['dbtech_vbactivity_type'], $typename);
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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_typedata_presave')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_typedata_predelete')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_typedata_postsave')) ? eval($hook) : false;

		if (!$this->condition)
		{
			// Grab the DBAlter class
			require_once(DIR . '/includes/class_dbalter.php');
			
			// Set some important variables
			$db_alter = new vB_Database_Alter_MySQL($this->registry->db);
			
			if ($db_alter->fetch_table_info('dbtech_vbactivity_points'))
			{
				$db_alter->add_field(array(
					'name'       => $this->fetch_field('typename'),
					'type'       => 'double',
					'null'       => false,	// True = NULL, false = NOT NULL
					'default'    => '0'
				));				
			}
		}

		// Rebuild the cache
		VBACTIVITY_CACHE::build('type');

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_typedata_delete')) ? eval($hook) : false;
		
		// Grab the DBAlter class
		require_once(DIR . '/includes/class_dbalter.php');
		
		// Set some important variables
		$db_alter = new vB_Database_Alter_MySQL($this->registry->db);
		
		if ($db_alter->fetch_table_info('dbtech_vbactivity_points'))
		{
			// Deleting a type
			$db_alter->drop_field($this->fetch_field('typename'));				
		}
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('type');
		
		return true;
	}
}