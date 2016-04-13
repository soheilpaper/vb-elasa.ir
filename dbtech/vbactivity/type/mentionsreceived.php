<?php
class vBActivity_Type_mentionsreceived extends vBActivity_Type_Core
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
		return ($this->registry->products['dbtech_usertag']);
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
		
		$tbl 				= 'dbtech_usertag_mention';
		$idfield 			= 'postid';
		$hook_query_select 	= " , thread.forumid";
		$hook_query_join 	= " LEFT JOIN " . TABLE_PREFIX . "post AS post USING(postid)
								LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING(threadid)
		";		
		$hook_query_where 	= " AND mentionedid = " . $user['userid'];
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
			if ($result['type'] != 'post')
			{
				// Ensure this doesn't snafu
				$result['forumid'] = 0;
			}
			
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
		
		if (!$userinfo['userid'])
		{
			// Ignore this
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
					FROM $dbtech_usertag_mention AS mention
					INNER JOIN $post AS post USING(postid)
					INNER JOIN $thread AS thread USING(threadid)
					WHERE thread.forumid = ?
						AND mention.mentionedid = ?
						AND mention.type = \'post\'
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
		
		if (!isset($userinfo['dbtech_usertag_mentions']))
		{
			// We need more info
			$userinfo = array_merge(fetch_userinfo($userinfo['userid']), $userinfo);
		}
		
		// We had this info
		$userinfo[$typename] = $userinfo['dbtech_usertag_mentions'];
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>