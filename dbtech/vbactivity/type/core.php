<?php
class vBActivity_Type_Core
{
	/**
	* The vBulletin registry
	*
	* @private	vBulletin
	*/	
	protected $registry = NULL;
	
	/**
	* The configuration array
	*
	* @private	array
	*/	
	protected $config = array();
	
	
	/**
	* The constructor
	*
	* @param	vBulletin	vBulletin registry
	* @param	array		Type info
	* 
	* @return	string	The SQL subquery
	*/	
	public function __construct(&$registry, &$type)
	{
		$this->registry =& $registry;
		$this->config =& $type;
	}
	
	/**
	* Function to call before every action
	*/	
	public function action($user)
	{
		if (!$this->config['active'])
		{
			// This type is inactive
			return false;
		}
		
		if (!$user['userid'])
		{
			// This type is inactive
			return false;
		}
		
		// We made it!
		return true;
	}
	
	/**
	* What happens on recalculate points
	*
	* @param	array	The user info
	*/	
	public function recalculate_points($user) {}
	
	/**
	* Resets the points for this points type
	*
	* @param	array	The user info
	*/	
	public function reset_points($user)
	{
		if (!$user['userid'])
		{
			// Just get out
			return true;
		}
		
		// Clear the contents
		$this->registry->db->query_read_slave("
			UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_points
			SET " . $this->config['typename'] . " = 0
			WHERE userid = " . $user['userid']
		);		
	}
	
	/**
	* Checks whether we meet a certain criteria
	*
	* @param	integer	The criteria ID we are checking
	* @param	array	Information regarding the user we're checking
	* 
	* @return	boolean	Whether this criteria has been met
	*/	
	public function check_criteria($conditionid, &$userinfo)
	{
		if (!$this->action($userinfo))
		{
			// Disabled
			return false;
		}
		
		if (VBACTIVITY::$points_cached)
		{
			// We've already cached this
			return false;
		}
		
		if (!$userinfo['userid'])
		{
			// We don't have an userid
			return false;
		}
		
		// Grab the points from the dbase
		$points = $this->registry->db->query_first_slave("
			SELECT * FROM " . TABLE_PREFIX . "dbtech_vbactivity_points
			WHERE userid = " . $userinfo['userid']
		);
		
		if (!is_array($points))
		{
			/*
			$typenames = array();
			$points = array();
			foreach ((array)VBACTIVITY::$cache['type'] as $typeid => $type)
			{
				if (!in_array($type['typename'], $typenames))
				{
					$typenames[] = $type['typename'];
					$points["$type[typename]"] = 0;
				}
			}
			
			$db->query_write("
				INSERT IGNORE INTO " . TABLE_PREFIX . "dbtech_vbactivity_points
					(userid, " . implode(',', $typenames) . ")
				VALUES (
					" . $userinfo['userid'] . ",
					" . implode(',', $points) . "
				)
			");
			*/
			return false;
		}
		
		foreach ($points as $typename => $points)
		{
			if ($typename == 'userid')
			{
				// We don't need this
				continue;
			}
			
			// Set userinfo
			$userinfo["per{$typename}"] = $points;
		}
		
		// Ensure we only query once
		VBACTIVITY::$points_cached = true;
		
		return false;		
	}
}
?>