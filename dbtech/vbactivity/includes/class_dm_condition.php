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
* Class to do data save/delete operations for conditions
*
* @package	vbactivity
*/
class vBActivity_DataManager_Condition extends vB_DataManager
{
	/**
	* Array of recognised and required fields for conditions, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'conditionid' 	=> array(TYPE_UINT, 	REQ_INCR, 	VF_METHOD, 	'verify_nonzero'),
		'typeid' 		=> array(TYPE_UINT, 	REQ_YES, 	VF_METHOD),
		'comparison' 	=> array(TYPE_STR, 		REQ_YES, 	VF_METHOD),
		'value' 		=> array(TYPE_UINT, 	REQ_YES),
		'type' 			=> array(TYPE_STR, 		REQ_YES, 	VF_METHOD),
		'forumid' 		=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD),
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
	var $table = 'dbtech_vbactivity_condition';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('conditionid = %1$d', 'conditionid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vBActivity_DataManager_Condition(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_conditiondata_start')) ? eval($hook) : false;
	}

	/**
	* Verifies that the typeid is valid
	*
	* @param	integer	typeid
	*
	* @return	boolean
	*/
	function verify_typeid(&$typeid)
	{
		// Validate typeid
		return is_array(VBACTIVITY::$cache['type'][$typeid]);
	}
	
	/**
	* Verifies that the comparison is valid
	*
	* @param	string	comparison
	*
	* @return	boolean
	*/
	function verify_comparison(&$comparison)
	{
		// Validate comparison
		return in_array($comparison, array('<', '<=', '=', '>=', '>'));
	}
	
	/**
	* Verifies that the type is valid
	*
	* @param	string	type
	*
	* @return	boolean
	*/
	function verify_type(&$type)
	{
		// Validate type
		$type = (in_array($type, array('points', 'value')) ? $type : 'value');
		
		return true;
	}

	/**
	* Verifies that the specified forumid is valid
	*
	* @param	integer	Forum ID (allow -1 = all forums)
	*
	* @return	boolean
	*/
	function verify_forumid(&$forumid)
	{
		if (!$forumid)
		{
			return true;
		}

		if (empty($this->registry->forumcache[$forumid]))
		{
			$this->error('invalid_forum_specified');
			return false;
		}

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
		global $vbphrase;
		
		if ($this->presave_called !== null)
		{
			return $this->presave_called;
		}
		
		$type = VBACTIVITY::$cache['type'][$this->fetch_field('typeid')];
		if (!($type['settings'] & 8))
		{
			// Enforce value for this type
			$this->set('type', 'value');
		}

		/*DBTECH_PRO_START*/
		if (!($type['settings'] & 64) OR $this->fetch_field('type') == 'points')
		{
			// Remove forum ID
			$this->set('forumid', 0);
		}
		/*DBTECH_PRO_END*/
		
		if ($existing = $this->registry->db->query_first_slave("
			SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_condition
			WHERE `typeid` = " . $this->registry->db->sql_prepare($this->fetch_field('typeid')) . "
				AND `type` = " . $this->registry->db->sql_prepare($this->fetch_field('type')) . "
				AND `comparison` = " . $this->registry->db->sql_prepare($this->fetch_field('comparison')) . "
				AND `value` = " . $this->registry->db->sql_prepare($this->fetch_field('value')) . "
				AND `forumid` = " . $this->registry->db->sql_prepare($this->fetch_field('forumid')) . 
				($this->condition ? " AND `conditionid` != " . $this->registry->db->sql_prepare($this->existing['conditionid']) : '')
		))
		{
			// Shorthand
			$type = VBACTIVITY::$cache['type']["$existing[typeid]"]['typename'];
			
			$this->error('dbtech_vbactivity_x_already_exists_y', $vbphrase['dbtech_vbactivity_condition'], $vbphrase['dbtech_vbactivity_condition_' . $type] . ' ' . $existing['comparison'] . ' ' . $existing['value']);
			return false;
		}

		$return_value = true;
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_conditiondata_presave')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_conditiondata_predelete')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_conditiondata_postsave')) ? eval($hook) : false;

		// Rebuild the cache
		VBACTIVITY_CACHE::build('condition');

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_conditiondata_delete')) ? eval($hook) : false;
		
		$this->registry->db->query_write("
			DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_conditionbridge`
			WHERE `conditionid` = " . $this->registry->db->sql_prepare($this->existing['conditionid'])
		);
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('condition');
		VBACTIVITY_CACHE::build('conditionbridge');
		
		return true;
	}
}