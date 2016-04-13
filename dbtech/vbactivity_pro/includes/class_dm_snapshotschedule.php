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
* Class to do data save/delete operations for snapshotschedules
*
* @package	vbactivity
*/
class vBActivity_DataManager_Snapshotschedule extends vB_DataManager
{
	/**
	* Array of recognised and required fields for snapshotschedules, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'snapshotscheduleid' 	=> array(TYPE_UINT, 	REQ_INCR, 	VF_METHOD, 	'verify_nonzero'),
		'active' 				=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD, 	'verify_onoff'),
		'start_day' 			=> array(TYPE_UINT, 	REQ_YES),
		'start_hour' 			=> array(TYPE_UINT, 	REQ_YES),
		'end_day' 				=> array(TYPE_UINT, 	REQ_YES),
		'end_hour' 				=> array(TYPE_UINT, 	REQ_YES),
		'loadsnapshotid' 		=> array(TYPE_UINT, 	REQ_YES, 	VF_METHOD, 	'verify_snapshotid'),
		'revertsnapshotid' 		=> array(TYPE_UINT, 	REQ_YES, 	VF_METHOD, 	'verify_snapshotid'),
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
	var $table = 'dbtech_vbactivity_snapshotschedule';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('snapshotscheduleid = %1$d', 'snapshotscheduleid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vBActivity_DataManager_Snapshotschedule(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_snapshotscheduledata_start')) ? eval($hook) : false;
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
	* Verifies that the napshotid is valid
	*
	* @param	string	napshotid
	*
	* @return	boolean
	*/
	function verify_snapshotid(&$snapshotid)
	{
		// Validate onoff
		$existing = $this->registry->db->query_first_slave("SELECT snapshotid FROM " . TABLE_PREFIX . "dbtech_vbactivity_snapshot WHERE snapshotid = " . intval($snapshotid));
		return is_array($existing);
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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_snapshotscheduledata_presave')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_snapshotscheduledata_predelete')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_snapshotscheduledata_postsave')) ? eval($hook) : false;

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_snapshotscheduledata_delete')) ? eval($hook) : false;
		
		return true;
	}
}