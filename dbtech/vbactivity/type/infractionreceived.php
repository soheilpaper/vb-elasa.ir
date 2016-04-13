<?php
class vBActivity_Type_infractionreceived extends vBActivity_Type_Core
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
		
		$tbl 				= 'infraction';
		$idfield 			= 'infractionid';
		$hook_query_select 	= " , thread.forumid";
		$hook_query_join 	= " LEFT JOIN " . TABLE_PREFIX . "post AS post USING(postid)
								LEFT JOIN " . TABLE_PREFIX . "thread AS thread ON(thread.threadid = post.threadid)
		";		
		$hook_query_where 	= " AND infraction.userid = " . $user['userid'];
		$multiplier 		= 1;
			
		/*DBTECH_PRO_START*/
		if ($this->registry->GPC['startdate'])
		{
			// Set dateline
			$hook_query_where = " AND " . ($datefield ? $datefield : $tbl . '.dateline') . " >= " . $this->registry->GPC['startdate'] . $hook_query_where;
		}
		/*DBTECH_PRO_END*/

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
			$multiplier = $result['points'];
			
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
					SELECT SUM(infraction.points)
					FROM $infraction AS infraction
					INNER JOIN $post AS post USING(postid)
					INNER JOIN $thread AS thread USING(threadid)
					WHERE thread.forumid = ?
						AND infraction.userid = ?
						AND infraction.postid > 0
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
				SELECT SUM(points) AS $typename
				FROM " . TABLE_PREFIX . "infraction
				WHERE userid = " . $userid . "
			");
			
			// We had this info
			$userinfo[$typename] = intval($additionalinfo[$typename]);
		}		
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>