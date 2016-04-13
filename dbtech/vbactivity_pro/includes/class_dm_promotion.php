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
* Class to do data save/delete operations for promotions
*
* @package	vbactivity
*/
class vBActivity_DataManager_Promotion extends vB_DataManager
{
	/**
	* Array of recognised and required fields for promotions, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'promotionid'		=> array(TYPE_UINT, 	REQ_INCR, 	VF_METHOD, 	'verify_nonzero'),
		'fromusergroupid' 	=> array(TYPE_UINT, 	REQ_YES, 	VF_METHOD, 	'verify_usergroupid'),
		'tousergroupid' 	=> array(TYPE_UINT, 	REQ_YES, 	VF_METHOD, 	'verify_usergroupid'),
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
	var $table = 'dbtech_vbactivity_promotion';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('promotionid = %1$d', 'promotionid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vBActivity_DataManager_Promotion(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_promotiondata_start')) ? eval($hook) : false;
	}
	
	/**
	* Verifies that the usergroupid is valid
	*
	* @param	integer	usergroupid
	*
	* @return	boolean
	*/
	function verify_usergroupid(&$usergroupid)
	{
		// Validate usergroupid
		return is_array($this->registry->usergroupcache["$usergroupid"]);
	}
	
	/**
	* Verifies that the categoryid is valid
	*
	* @param	integer	categoryid
	*
	* @return	boolean
	*/
	function verify_categoryid(&$categoryid)
	{
		// Validate categoryid
		return is_array(VBACTIVITY::$cache['category']["$categoryid"]);
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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_promotiondata_presave')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_promotiondata_predelete')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_promotiondata_postsave')) ? eval($hook) : false;
		
		if (!empty($this->registry->GPC['condition']))
		{
			$conditions = array();
			$added = array();
			foreach ($this->registry->GPC['condition'] as $key => $conditionid)
			{
				if (!$this->registry->GPC['removecondition']["$key"] AND !$added["$conditionid"])
				{
					$conditions[] = "($conditionid, 'promotion', " . $this->fetch_field('promotionid') . ")";
					$added["$conditionid"] = true;
				}
			}
			
			if ($this->condition)
			{
				// Remove all årevious condition bridges
				$this->registry->db->query_write("DELETE FROM " . TABLE_PREFIX ."dbtech_vbactivity_conditionbridge WHERE feature = 'promotion' AND featureid = " . $this->fetch_field('promotionid'));
			}
			
			// Insert new condition bridges
			$this->registry->db->query_write("
				INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_conditionbridge`
					(`conditionid`, `feature`, `featureid`)
				VALUES
					" . implode(',', $conditions)
			);
			
			// Rebuild the cache
			VBACTIVITY_CACHE::build('conditionbridge');
		}
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('promotion');

		return true;
	}

	/**
	* Additional data to update after a delete call (such as denormalized values in other tables).
	*
	* @param	boolean	Do the query?
	*/
	function post_delete($doquery = true)
	{
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_promotiondata_delete')) ? eval($hook) : false;
		
		$this->registry->db->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET dbtech_vbactivity_rewardscache = NULL
			WHERE userid IN (
				SELECT userid FROM `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`
				WHERE `feature` = 'promotion'
					AND `featureid` = " . $this->registry->db->sql_prepare($this->existing['promotionid']) . "
			)
		");
		$this->registry->db->query_write("
			DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`
			WHERE `feature` = 'promotion'
				AND `featureid` = " . $this->registry->db->sql_prepare($this->existing['promotionid'])
		);
		$this->registry->db->query_write("
			DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_conditionbridge`
			WHERE `feature` = 'promotion'
				AND `featureid` = " . $this->registry->db->sql_prepare($this->existing['promotionid'])
		);		
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('promotion');
		VBACTIVITY_CACHE::build('conditionbridge');
		
		return true;
	}
}