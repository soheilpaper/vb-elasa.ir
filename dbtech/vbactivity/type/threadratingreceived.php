<?php
class vBActivity_Type_threadratingreceived extends vBActivity_Type_Core
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
		
		$tbl 				= 'threadrate';
		$idfield 			= 'threadrateid';
		//$hook_query_select 	= ', ' . TIMENOW . ' AS dateline';
		$hook_query_select 	= ', 1 AS dateline, thread.forumid';
		$hook_query_join 	= " LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON (thread.threadid = threadrate.threadid)";
		$hook_query_where 	= " AND thread.postuserid = " . $user['userid'];
		$multiplier 		= 1;
			
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
			VBACTIVITY::insert_points($this->config['typename'], $result[$idfield], $user['userid'], $multiplier, $result['dateline'], $result['forumid']);
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
		
		$changedTypeName = false;
		if ($condition['forumid'])
		{
			if (!$userinfo[$typename . '_forumid_' . $condition['forumid']])
			{
				// Fetch this
				$userinfo[$typename . '_forumid_' . $condition['forumid']] = VBACTIVITY::$db->fetchOne('
					SELECT COUNT(*)
					FROM $threadrate AS threadrate
					WHERE threadid IN(
						SELECT threadid
						FROM $thread
						WHERE forumid = ?
							AND postuserid = ?
					)
				', array(
					$condition['forumid'],
					$userinfo['userid'],
				));
			}

			// Override type name
			$changedTypeName = $typename . '_forumid_' . $condition['forumid'];
		}
		
		if ($condition['type'] == 'points' OR $userinfo[$typename] OR $condition['forumid'])
		{
			// This has been cached
			return VBACTIVITY::check_criteria($conditionid, $userinfo, $changedTypeName);
		}
		
		// Copypaste shorthand
		$userid = $userinfo['userid'];		
		
		if (!$userinfo[$typename])
		{
			// We need more info
			$additionalinfo = $this->registry->db->query_first_slave("
				SELECT COUNT(*) AS $typename
				FROM " . TABLE_PREFIX . "threadrate AS threadrate
				WHERE threadid IN(
					SELECT threadid
					FROM " . TABLE_PREFIX . "thread
					WHERE postuserid = " . $userid . "
				)
			");
			
			// We had this info
			$userinfo[$typename] = intval($additionalinfo[$typename]);
		}		
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>