<?php
class LiveWall_ContentType_socialgroupthreads extends LiveWall_ContentType_Core
{
	/**
	* Function to call before every action
	*/	
	public function preCheck()
	{
		if (
			!($this->registry->options['socnet'] & $this->registry->bf_misc_socnet['enable_groups'])
			OR !($this->registry->userinfo['permissions']['forumpermissions'] & $this->registry->bf_ugp_forumpermissions['canview'])
			OR !($this->registry->userinfo['permissions']['socialgrouppermissions'] & $this->registry->bf_ugp_socialgrouppermissions['canviewgroups'])
			)
		{
			// This type is inactive
			return false;
		}
		
		// We made it!
		return parent::preCheck();
	}

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
				discussion.*,
				groupmessage.*,
				user.*,
				groupmessage.title AS threadtitle,
				socialgroup.name AS socialgroupname,
				socialgroup.groupid,
				groupmessage.dateline,
				discussion.discussionid AS contentid,
				\':contentType\' AS contenttypeid,
				favourite.userid IS NOT NULL AS isfavourite				
				' . ($this->registry->userinfo['userid'] ? ', socialgroupmember.type AS membertype ' : '') . '				
				:avatarQuery
			FROM (SELECT discussionid FROM $discussion ' . ($lastId != -1 ? 'WHERE discussionid ' . ($fetchOne != false ? '= ' : '> ') . intval($lastId) : '') . ' ORDER BY discussionid DESC LIMIT ' . $this->registry->options['dbtech_livewall_maxentries'] . ') AS tmp
			LEFT JOIN $discussion AS discussion ON(discussion.discussionid = tmp.discussionid)
			LEFT JOIN $groupmessage AS groupmessage ON(groupmessage.gmid = discussion.firstpostid)
			LEFT JOIN $socialgroup AS socialgroup ON (socialgroup.groupid = discussion.groupid)
			' . ($this->registry->userinfo['userid'] ?
			"LEFT JOIN " . TABLE_PREFIX . "socialgroupmember AS socialgroupmember ON (socialgroupmember.userid = " . $this->registry->userinfo['userid'] . " AND socialgroupmember.groupid = socialgroup.groupid)"
			: '') . '
			LEFT JOIN $user AS user ON(user.userid = groupmessage.postuserid)
			LEFT JOIN $dbtech_livewall_settings AS currentuser_settings ON(currentuser_settings.userid = :currentUser)
			LEFT JOIN $dbtech_livewall_settings AS actionuser_settings ON(actionuser_settings.userid = user.userid)
			LEFT JOIN $dbtech_livewall_favourite AS favourite ON(favourite.userid = :currentUser AND favourite.contenttypeid = \':contentType\' AND favourite.contentid = discussion.discussionid)
			LEFT JOIN $userlist AS actionuser_friend ON(actionuser_friend.userid = user.userid AND actionuser_friend.relationid = :currentUser)
			:avatarJoin
			WHERE groupmessage.state = \':state\'
				AND NOT FIND_IN_SET(:memberGroupIdsSet, membergroupids)
				AND usergroupid NOT :memberGroupIds
				AND
				(
					(
						NOT (socialgroup.options & ' . intval($this->registry->bf_misc_socialgroupoptions['join_to_view']) . ') OR
						' . intval($this->registry->options['sg_allow_join_to_view']) . ' = 0
					)
					' . ($this->registry->userinfo['userid'] ? 'OR socialgroupmember.type = \'member\'' : '') . '
				)
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
				)
				:onlyUser
			:fetchOne
			:limit
		', array(
			':avatarQuery' 			=> ($this->registry->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : ''),
			':avatarJoin' 			=> ($this->registry->options['avatarenabled'] ? 'LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)' : ''),
			':currentUser' 			=> intval($this->registry->userinfo['userid']),
			':contentType' 			=> 'socialgroupthreads',
			':state' 				=> 'visible',
			':forumIds' 			=> $this->getForumIds(),
			':memberGroupIdsSet' 	=> implode(', membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIds' 		=> LIVEWALL::$db->queryList($excludedGroups),
			':onlyUser' 			=> ($onlyUser ? 'AND user.userid = ' . intval($onlyUser) : ''),
			':fetchOne' 			=> ($fetchOne ? 'LIMIT 1' : 'ORDER BY tmp.discussionid DESC'),
			':limit' 				=> ($lastId == -1 ? 'LIMIT ' . ($limit == -1 ? $this->registry->options['dbtech_livewall_perpage'] : $limit) : '')
		));
	}
	
	/**
	* Creates a phrase based on the action in question
	*/	
	public function constructPhrase($info)
	{
		global $vbphrase;
		
		return construct_phrase($vbphrase['dbtech_livewall_x_created_social_group_y_thread_z'], 
			($info['userid'] ? '<a href="' . (LIVEWALL::$permissions['canviewuserwall'] ? 'livewall.php?' : 'member.php?') . $this->registry->session->vars['sessionurl'] . 'u=' . $info['userid'] . '">' . $info['username'] . '</a>' : $vbphrase['guest']),
			'<a href="group.php?' . $this->registry->session->vars['sessionurl'] . 'do=discuss&amp;gmid=' . $info['gmid'] . '#gmessage' . $info['gmid'] . '">' . $info['threadtitle'] . '</a>',
			'<a href="group.php?' . $this->registry->session->vars['sessionurl'] . 'groupid=' . $info['groupid'] . '">' . $info['socialgroupname'] . '</a>'			
		);
	}	
}
?>