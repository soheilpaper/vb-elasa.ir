<?php
class vBActivity_Type_blogpost extends vBActivity_Type_Core
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
		return $this->registry->products['vbblog'];
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
		
		$tbl 				= 'blog';
		$idfield 			= 'blogid';
		$hook_query_where 	= " AND userid = " . $user['userid'];
		$multiplier 		= 1;
			
		if (VBACTIVITY::$isPro)
		{
			if ($this->registry->GPC['startdate'])
			{
				// Set dateline
				$hook_query_where = " AND " . ($datefield ? $datefield : $tbl . '.dateline') . " >= " . $this->registry->GPC['startdate'] . "" . $hook_query_where;
			}
		}

		VBACTIVITY::$db->fetchAllObject('
			SELECT * :hookQuerySelect
			FROM $:tbl AS :tbl
			:hookQueryJoin
			WHERE 1=1 
				:hookQueryWhere
		', array(
			':hookQuerySelect' 	=> $hook_query_select,
			':hookQueryJoin' 	=> $hook_query_join,
			':hookQueryWhere' 	=> $hook_query_where,
			':tbl' 				=> $tbl,
		));
		while ($result = VBACTIVITY::$db->fetchCurrent())
		{
			// Insert points log
			VBACTIVITY::insert_points($this->config['typename'], $result[$idfield], $user['userid'], $multiplier, $result['dateline']);
		}
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
		
		// Copypaste shorthand
		$userid = $userinfo['userid'];		
		
		if (!$userinfo[$typename])
		{
			// We need more info
			$additionalinfo = $this->registry->db->query_first_slave("
				SELECT COUNT(*) AS $typename
				FROM " . TABLE_PREFIX . "blog
				WHERE userid = " . $userinfo['userid'] . "
			");
			
			// We had this info
			$userinfo[$typename] = intval($additionalinfo[$typename]);
		}		
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>