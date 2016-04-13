<?php
class vBActivity_Type_lastvisit extends vBActivity_Type_Core
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
		
		// Set the multiplier
		$multiplier = floor((TIMENOW - $user['lastvisit']) / 86400);
		
		// Insert points log
		VBACTIVITY::insert_points($this->config['typename'], 0, $user['userid'], $multiplier, 1);
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
		
		if (!$userinfo['userid'])
		{
			// Ignore this
			return false;
		}
		
		// grab us the type name
		$typename = 'vbaa_' . VBACTIVITY::$cache['type'][$condition['typeid']]['typename'];
		
		if ($condition['type'] == 'points')
		{
			// This has been cached
			return VBACTIVITY::check_criteria($conditionid, $userinfo);
		}
		
		if ($userinfo[$typename])
		{
			// This has been cached
			return VBACTIVITY::check_criteria($conditionid, $userinfo, $typename);
		}
		
		if (!$userinfo['lastvisit'])
		{
			// We need more info
			$userinfo = array_merge(fetch_userinfo($userinfo['userid']), $userinfo);
		}
		
		// We had this info
		$userinfo[$typename] = floor((TIMENOW - $userinfo['lastvisit']) / 86400);

		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo, $typename);
	}	
}
?>