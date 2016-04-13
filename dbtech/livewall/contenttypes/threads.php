<?php
class LiveWall_ContentType_threads extends LiveWall_ContentType_Core
{
	/*DBTECH_PRO_START*/
	/**
	* Prints out an AdminCP form
	*/	
	public function printAdminForm($code)
	{
		global $vbphrase;
		print_forum_chooser($vbphrase['dbtech_livewall_excluded_forums'], 			'contenttype[code][excludedforums][]',			$code['excludedforums'], null, false, true);
	}
	/*DBTECH_PRO_END*/
	
	/**
	* Fetches data newer than the lastId
	*
	* @param integer Last ID or -1 for initial load
	* @param integer If we're fetching data for only one userID
	*/	
	public function fetchData($lastId = -1, $onlyUser = 0, $limit = -1, $fetchOne = false)
	{
		// Grab excluded usergroups
		$excludedGroups = $this->getUserGroupIds();
		
		return LIVEWALL::$db->fetchAll('
			SELECT
				thread.*,
				post.*,
				user.*,
				thread.title AS threadtitle,
				thread.dateline,
				thread.threadid AS contentid,
				\':contentType\' AS contenttypeid,
				favourite.userid IS NOT NULL AS isfavourite				
				:avatarQuery
			FROM (SELECT threadid FROM $thread ' . ($lastId != -1 ? 'WHERE threadid ' . ($fetchOne != false ? '= ' : '> ') . intval($lastId) : '') . ' ORDER BY threadid DESC LIMIT ' . $this->registry->options['dbtech_livewall_maxentries'] . ') AS tmp
			LEFT JOIN $thread AS thread ON(thread.threadid = tmp.threadid)
			LEFT JOIN $post AS post ON(post.postid = thread.firstpostid)
			LEFT JOIN $user AS user ON(user.userid = thread.postuserid)
			LEFT JOIN $dbtech_livewall_settings AS currentuser_settings ON(currentuser_settings.userid = :currentUser)
			LEFT JOIN $dbtech_livewall_settings AS actionuser_settings ON(actionuser_settings.userid = user.userid)
			LEFT JOIN $dbtech_livewall_favourite AS favourite ON(favourite.userid = :currentUser AND favourite.contenttypeid = \':contentType\' AND favourite.contentid = thread.threadid)
			LEFT JOIN $userlist AS actionuser_friend ON(actionuser_friend.userid = user.userid AND actionuser_friend.relationid = :currentUser)
			:avatarJoin
			WHERE NOT ISNULL(thread.threadid)
				AND post.visible = 1
				AND thread.visible = 1
				AND thread.open != 10
				AND thread.forumid NOT IN(:forumIds)
				AND NOT FIND_IN_SET(:memberGroupIdsSet, membergroupids)
				AND usergroupid NOT :memberGroupIds
				' . (!$fetchOne ? '
				AND
				(
					user.userid = :currentUser OR
					(
						(
							ISNULL(currentuser_settings.:contentType_display) OR 
							(
								currentuser_settings.:contentType_display = 0 OR
								(
									currentuser_settings.:contentType_display = 1 AND
									:currentUser > 0
								) OR
								(
									currentuser_settings.:contentType_display = 2 AND
									actionuser_friend.type = \'buddy\'
								) OR
								(
									currentuser_settings.:contentType_display = 3 AND
									actionuser_friend.type = \'buddy\' AND
									actionuser_friend.friend = \'yes\'
								)
							)
						) AND
						(
							ISNULL(actionuser_settings.:contentType_privacy) OR 
							(
								actionuser_settings.:contentType_privacy = 0 OR
								(
									actionuser_settings.:contentType_privacy = 1 AND
									:currentUser > 0
								) OR
								(
									actionuser_settings.:contentType_privacy = 2 AND
									actionuser_friend.type = \'buddy\'
								) OR
								(
									actionuser_settings.:contentType_privacy = 3 AND
									actionuser_friend.type = \'buddy\' AND
									actionuser_friend.friend = \'yes\'
								)
							)
						)						
					)
				)' : '') . '
				:onlyUser
			:fetchOne
			:limit
		', array(
			':avatarQuery' 			=> ($this->registry->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : ''),
			':avatarJoin' 			=> ($this->registry->options['avatarenabled'] ? 'LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)' : ''),
			':currentUser' 			=> intval($this->registry->userinfo['userid']),
			':contentType' 			=> 'threads',
			':forumIds' 			=> $this->getForumIds(),
			':memberGroupIdsSet' 	=> implode(', membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIds' 		=> LIVEWALL::$db->queryList($excludedGroups),
			':onlyUser' 			=> ($onlyUser ? 'AND user.userid = ' . intval($onlyUser) : ''),
			':fetchOne' 			=> ($fetchOne ? 'LIMIT 1' : 'ORDER BY tmp.threadid DESC'),
			':limit' 				=> ($lastId == -1 ? 'LIMIT ' . ($limit == -1 ? $this->registry->options['dbtech_livewall_perpage'] : $limit) : '')
		));
	}
	
	/**
	* Creates a phrase based on the action in question
	*/	
	public function constructPhrase($info)
	{
		global $vbphrase;
		
		return construct_phrase($vbphrase['dbtech_livewall_x_created_thread_y'], 
			($info['userid'] ? '<a href="' . (LIVEWALL::$permissions['canviewuserwall'] ? 'livewall.php?' : 'member.php?') . $this->registry->session->vars['sessionurl'] . 'u=' . $info['userid'] . '">' . $info['username'] . '</a>' : $vbphrase['guest']),
			'<a href="showthread.php?' . $this->registry->session->vars['sessionurl'] . 'p=' . $info['postid'] . '#post' . $info['postid'] . '">' . $info['threadtitle'] . '</a>'
		);
	}	
}
?>