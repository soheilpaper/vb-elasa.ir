<?php
class vBActivity_Type_ttbattledraw extends vBActivity_Type_Core
{
	/**
	* Function to call before every action
	*/	
	public function action($user)
	{
		if (!parent::action($user))
		{
			// This type is inactive
			return false;
		}
		
		// We made it!
		return ($this->registry->products['_dbtech_triple_triad']);
	}
	
	/**
	* What happens on recalculate points
	*
	* @param	array	The user info
	*/	
	public function recalculate_points($user)
	{
		if (!$this->action($user))
		{
			// Disabled
			return false;
		}
		
		// Reset the points
		parent::reset_points($user);
		
		$tbl 				= 'dbt_triad_players';
		$idfield 			= 'pid';
		$hook_query_select 	= ', 1 AS dateline';
		$hook_query_where 	= " AND p_userid = " . $user['userid'];
			
		$result = $this->registry->db->query_first_slave("
			SELECT p_draw AS multiplier $hook_query_select
			FROM " . TABLE_PREFIX . "$tbl AS $tbl
			WHERE 1=1 
				$hook_query_where
		");
		
		// Insert points log
		VBACTIVITY::insert_points($this->config['typename'], 0, $user['userid'], $result['multiplier'], $result['dateline']);
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

		// Ensure we've got points cached
		parent::check_criteria($conditionid, $userinfo);
		
		if (!$condition = VBACTIVITY::$cache['condition'][$conditionid])
		{
			// condition doesn't even exist
			return false;
		}
		
		// grab us the type name
		$typename = VBACTIVITY::$cache['type'][$condition['typeid']]['typename'];
		
		if ($condition['type'] == 'points' OR $userinfo[$typename])
		{
			// This has been cached
			return VBACTIVITY::check_criteria($conditionid, $userinfo);
		}
		
		if (!$userinfo[$typename])
		{
			// We need more info
			$additionalinfo = $this->registry->db->query_first_slave("
				SELECT p_draw AS $typename
				FROM " . TABLE_PREFIX . "dbt_triad_players AS dbt_triad_players
				WHERE p_userid = " . $userinfo['userid'] . "
			");
			
			// We had this info
			$userinfo[$typename] = intval($additionalinfo[$typename]);
		}		
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>