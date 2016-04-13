<?php
class vBActivity_Type_profilecomplete extends vBActivity_Type_Core
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
		
		// query all required profile fields
		$profilefields = VBACTIVITY::$db->fetchAll('SELECT * FROM $profilefield AS profilefield WHERE editable IN (1,2) AND required IN(1,3)');
		
		$earnedPoints = true;
		foreach ($profilefields as $profilefield)
		{
			if (!isset($user['field' . $profilefield['profilefieldid']]))
			{
				// We need more info
				$user = array_merge($user, fetch_userinfo($user['userid']));
			}
			
			if ($user['field' . $profilefield['profilefieldid']] === '')
			{
				// Empty profile field
				$earnedPoints = false;
				break;
			}
		}
		
		if ($earnedPoints)
		{
			// Insert points log
			VBACTIVITY::insert_points($this->config['typename'], 0, $user['userid'], 1, 1);
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
		
		if (!$userinfo['userid'])
		{
			// Ignore this
			return false;
		}
		
		// grab us the type name
		$typename = VBACTIVITY::$cache['type'][$condition['typeid']]['typename'];
		
		if ($condition['type'] == 'points' OR $userinfo[$typename])
		{
			// This has been cached
			return VBACTIVITY::check_criteria($conditionid, $userinfo);
		}
		
		// query all required profile fields
		$profilefields = VBACTIVITY::$db->fetchAll('SELECT * FROM $profilefield AS profilefield WHERE editable IN (1,2) AND required IN(1,3)');
		
		$earnedPoints = true;
		foreach ($profilefields as $profilefield)
		{
			if (!isset($user['field' . $profilefield['profilefieldid']]))
			{
				// We need more info
				$user = array_merge($user, fetch_userinfo($user['userid']));
			}
			
			if ($user['field' . $profilefield['profilefieldid']] === '')
			{
				// Empty profile field
				$earnedPoints = false;
				break;
			}
		}
		
		// We had this info
		$userinfo[$typename] = ($earnedPoints ? 2147483647 : 0);
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>