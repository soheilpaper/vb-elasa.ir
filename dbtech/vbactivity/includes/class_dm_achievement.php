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
* Class to do data save/delete operations for achievements
*
* @package	vbactivity
*/
class vBActivity_DataManager_Achievement extends vB_DataManager
{
	/**
	* Array of recognised and required fields for achievements, and their types
	*
	* @var	array
	*/
	var $validfields = array(
		'achievementid'	=> array(TYPE_UINT, 	REQ_INCR, 	VF_METHOD, 	'verify_nonzero'),
		'parentid'		=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD),
		'categoryid'	=> array(TYPE_UINT, 	REQ_YES, 	VF_METHOD),
		'title' 		=> array(TYPE_STR, 		REQ_YES),
		'description' 	=> array(TYPE_STR, 		REQ_NO),
		'icon' 			=> array(TYPE_STR, 		REQ_NO),
		'displayorder' 	=> array(TYPE_UINT, 	REQ_NO),
		'sticky' 		=> array(TYPE_UINT, 	REQ_NO, 	VF_METHOD, 'verify_onoff'),
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
	var $table = 'dbtech_vbactivity_achievement';

	/**
	* Condition for update query
	*
	* @var	array
	*/
	var $condition_construct = array('achievementid = %1$d', 'achievementid');

	/**
	* Constructor - checks that the registry object has been passed correctly.
	*
	* @param	vB_Registry	Instance of the vBulletin data registry object - expected to have the database object as one of its $this->db member.
	* @param	integer		One of the ERRTYPE_x constants
	*/
	function vBActivity_DataManager_Achievement(&$registry, $errtype = ERRTYPE_STANDARD)
	{
		parent::vB_DataManager($registry, $errtype);

		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_achievementdata_start')) ? eval($hook) : false;
	}
	
	/**
	* Verifies that the parentid is valid
	*
	* @param	integer	parentid
	*
	* @return	boolean
	*/
	function verify_parentid(&$parentid)
	{
		// Validate parentid
		return is_array(VBACTIVITY::$cache['achievement']["$parentid"]);
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
	* Verifies that the title is valid
	*
	* @param	string	Title of the achievement
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
		
		// Check for existing achievement of this name
		if ($existing = $this->dbobject->query_first_slave("
			SELECT `title`
			FROM `" . TABLE_PREFIX . "dbtech_vbactivity_achievement`
			WHERE `title` = " . $this->dbobject->sql_prepare($title) . "
				" . ($this->existing['achievementid'] ? "AND `achievementid` != " . $this->dbobject->sql_prepare($this->existing['achievementid']) : '') . "			
			LIMIT 1
		"))
		{
			// Whoopsie, exists
			$this->error('dbtech_vbactivity_x_already_exists_y', $vbphrase['dbtech_vbactivity_achievement'], $title);
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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_achievementdata_presave')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_achievementdata_predelete')) ? eval($hook) : false;

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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_achievementdata_postsave')) ? eval($hook) : false;
		
		if (!empty($this->registry->GPC['condition']))
		{
			$conditions = array();
			$added = array();
			foreach ($this->registry->GPC['condition'] as $key => $conditionid)
			{
				if (!$this->registry->GPC['removecondition']["$key"] AND !$added["$conditionid"])
				{
					$conditions[] = "($conditionid, 'achievement', " . $this->fetch_field('achievementid') . ")";
					$added["$conditionid"] = true;
				}
			}
			
			if ($this->condition)
			{
				// Remove all årevious condition bridges
				$this->dbobject->query_write("DELETE FROM " . TABLE_PREFIX ."dbtech_vbactivity_conditionbridge WHERE feature = 'achievement' AND featureid = " . $this->fetch_field('achievementid'));
			}
			
			// Insert new condition bridges
			$this->dbobject->query_write("
				INSERT INTO `" . TABLE_PREFIX . "dbtech_vbactivity_conditionbridge`
					(`conditionid`, `feature`, `featureid`)
				VALUES
					" . implode(',', $conditions)
			);
			
			// Rebuild the cache
			VBACTIVITY_CACHE::build('conditionbridge');
		}
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('achievement');

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
					'dbtech_vbactivity_achievement_" . $this->fetch_field('achievementid') . "_title',
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
					'dbtech_vbactivity_achievement_" . $this->fetch_field('achievementid') . "_description',
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
		($hook = vBulletinHook::fetch_hook('dbtech_vbactivity_achievementdata_delete')) ? eval($hook) : false;
		
		$this->dbobject->query_write("
			UPDATE `" . TABLE_PREFIX . "dbtech_vbactivity_achievement`
			SET parentid = 0
			WHERE `parentid` = " . $this->dbobject->sql_prepare($this->existing['achievementid'])
		);
		$this->dbobject->query_write("
			UPDATE " . TABLE_PREFIX . "user
			SET dbtech_vbactivity_rewardscache = NULL
			WHERE userid IN (
				SELECT userid FROM `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`
				WHERE `feature` = 'achievement'
					AND `featureid` = " . $this->dbobject->sql_prepare($this->existing['achievementid']) . "
			)
		");
		$this->dbobject->query_write("
			DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_rewards`
			WHERE `feature` = 'achievement'
				AND `featureid` = " . $this->dbobject->sql_prepare($this->existing['achievementid'])
		);
		$this->dbobject->query_write("
			DELETE FROM `" . TABLE_PREFIX . "dbtech_vbactivity_conditionbridge`
			WHERE `feature` = 'achievement'
				AND `featureid` = " . $this->dbobject->sql_prepare($this->existing['achievementid'])
		);		
		
		// Rebuild the cache
		VBACTIVITY_CACHE::build('achievement');
		VBACTIVITY_CACHE::build('conditionbridge');
		
		return true;
	}
}