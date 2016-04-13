<?php
class vBActivity_Type_contestswon extends vBActivity_Type_Core
{
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
		
		foreach ((array)VBACTIVITY::$cache['contest'] as $contestid => $contest)
		{
			if ($this->registry->GPC['startdate'] AND $contest['enddate'] < $this->registry->GPC['startdate'])
			{
				// Skip this
				continue;
			}
			
			foreach ((array)$contest['winners'] as $place => $winner)
			{
				if ($winner != $user['userid'])
				{
					// Skip this winner
					continue;
				}
				
				// Insert the points
				VBACTIVITY::insert_points($this->config['typename'], $contestid, $user['userid'], 1, $contest['enddate']);
				
				// We're done here
				break;
			}
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
			$userinfo[$typename] = 0;
			
			foreach ((array)VBACTIVITY::$cache['contest'] as $contestid => $contest)
			{
				foreach ((array)$contest['winners'] as $place => $winner)
				{
					if ($winner != $userinfo['userid'])
					{
						// Skip this winner
						continue;
					}
					
					// Insert the points
					$userinfo[$typename]++;
					
					// We're done here
					break;
				}
			}
		}		
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>