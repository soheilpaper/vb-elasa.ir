<?php
class vBActivity_Type_post extends vBActivity_Type_Core
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
		
		$tbl 				= 'post';
		$idfield 			= 'postid';
		$datefield 			= 'post.dateline';
		$hook_query_select 	= " , thread.forumid, post.dateline";
		$hook_query_join 	= " LEFT JOIN " . TABLE_PREFIX . "thread AS thread USING(threadid)";
		$hook_query_where 	= " AND post.userid = " . $user['userid'] . " AND post.postid != thread.firstpostid";
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
					FROM $post AS post
					INNER JOIN $thread AS thread USING(threadid)
					WHERE thread.forumid = ?
						AND post.userid = :userId
						AND post.postid != thread.firstpostid
						#AND thread.postuserid != :userId
				', array(
					$condition['forumid'],
					':userId' => $userinfo['userid'],
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
		
		if (!$userinfo['posts'])
		{
			// We need more info
			$userinfo = array_merge(fetch_userinfo($userinfo['userid']), $userinfo);
		}
		
		// We had this info
		$userinfo[$typename] = $userinfo['posts'];
		
		// Pass criteria checking onwards
		return VBACTIVITY::check_criteria($conditionid, $userinfo);
	}	
}
?>