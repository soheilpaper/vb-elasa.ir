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
* Class to do data save/delete operations for contests
*
* @package	vbactivity
*/
class vBActivity_DataManager_Contest extends vB_DataManager
{
	/**
	* Array of recognised and required fields for contests, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'contestid'			=> array(TYPE_UINT, 	REQ_INCR, 	VF_METHOD, 	'verify_nonzero'),
		'title' 			=> array(TYPE_STR, 		REQ_YES),
		'description' 		=> array(TYPE_STR, 		REQ_NO),
		'start' 			=> array(TYPE_UNIXTIME, REQ_YES),
		'end' 				=> array(TYPE_UNIXTIME, REQ_YES),
		'target' 			=> array(TYPE_UNUM, 	REQ_NO),
		'data' 				=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 	'verify_serialized'),
		'prizes' 			=> array(TYPE_NOCLEAN, 	REQ_YES, 	VF_METHOD, 	'verify_serialized'),
		'prizes2' 			=> array(TYPE_NOCLEAN, 	REQ_YES, 	VF_METHOD, 	'verify_serialized'),
		'winners' 			=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 	'verify_serialized'),
		'numwinners' 		=> array(TYPE_UINT, 	REQ_NO),
		'link' 				=> array(TYPE_STR, 		REQ_NO),
		'banner' 			=> array(TYPE_STR, 		REQ_NO),
		'banner_small' 		=> array(TYPE_STR, 		REQ_NO),
		'admin_notifs' 		=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 'verify_commalist'),
		'excludedcriteria' 	=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 'verify_serialized'),
		'excludedforums' 	=> array(TYPE_NOCLEAN, 	REQ_NO, 	VF_METHOD, 'verify_serialized'),
		'is_public' 		=> array(TYPE_STR, 		REQ_NO, 	VF_METHOD, 'verify_onoff'),
		'show_criteria' 	=> array(TYPE_STR, 		REQ_NO, 	VF_METHOD, 'verify_onoff'),
		'show_progress' 	=> array(TYPE_STR, 		REQ_NO, 	VF_METHOD, 'verify_onoff'),
		'winner_notifs'		=> array(TYPE_UINT, 	REQ_NO),
		'recurring'			=> array(TYPE_UINT, 	REQ_NO),
		'numusers'			=> array(TYPE_UINT, 	REQ_NO),
		'contesttypeid' 	=> array(TYPE_UINT, 	REQ_YES),
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
	var $table = 'dbtech_vbactivity_contest';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('contestid = %1$d', 'contestid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vBActivity_DataManager_Contest(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_contestdata_start')) ? eval($hook) : false;
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
		$onoff = (!in_array($onoff, array('0', '1')) ? '1' : $onoff);
		
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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_contestdata_presave')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_contestdata_predelete')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_contestdata_postsave')) ? eval($hook) : false;
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('contest');

		if (!$this->registry->debug)
		{
			// Ensure we can do language verifications
			require_once(DIR . '/includes/adminfunctions_language.php');

			/*
			if (!preg_match('#^[a-z0-9_\[\]]+$#i', $typename)) // match a-z, A-Z, 0-9, ',', _ only .. allow [] for help items
			{
				print_stop_message('invalid_phrase_varname');
			}
			*/

			/*insert query*/
			$this->dbobject->query_write("
				REPLACE INTO " . TABLE_PREFIX . "phrase
					(languageid, varname, text, fieldname, product, username, dateline, version)
				VALUES
					(-1,
					'dbtech_vbactivity_contest_" . $this->fetch_field('contestid') . "_title',
					'" . $this->dbobject->escape_string($this->fetch_field('title')) . "',
					'global',
					'dbtech_vbactivity',
					'Admin',
					" . TIMENOW . ",
					'1.0.0')
			");

			/*insert query*/
			$this->dbobject->query_write("
				REPLACE INTO " . TABLE_PREFIX . "phrase
					(languageid, varname, text, fieldname, product, username, dateline, version)
				VALUES
					(-1,
					'dbtech_vbactivity_contest_" . $this->fetch_field('contestid') . "_description',
					'" . $this->dbobject->escape_string($this->fetch_field('description')) . "',
					'global',
					'dbtech_vbactivity',
					'Admin',
					" . TIMENOW . ",
					'1.0.0')
			");
			
			// Rebuild the language
			build_language(-1);
		}

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_contestdata_delete')) ? eval($hook) : false;
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('contest');
		
		return true;
	}
}