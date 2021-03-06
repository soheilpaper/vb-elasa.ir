<?php
class vBActivity_Type_activitylevel extends vBActivity_Type_Core
{
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
		
		if (!$userinfo['dbtech_vbactivity_points'] OR !$userinfo['dbtech_vbactivity_pointscache'])
		{
			// We need more info
			$userinfo = array_merge(fetch_userinfo($userinfo['userid']), $userinfo);
		}
		
		// Transform this
		$userinfo['points'] 		= doubleval($userinfo['dbtech_vbactivity_points']);
		$userinfo['pointscache'] 	= doubleval($userinfo['dbtech_vbactivity_pointscache']);
		
		// Set the activity level
		VBACTIVITY::fetch_activity_level($userinfo);
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>