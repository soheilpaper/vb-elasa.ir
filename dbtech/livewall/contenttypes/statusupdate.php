<?php
class LiveWall_ContentType_statusupdate extends LiveWall_ContentType_Core
{
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
				status.*,
				user.*,
				status.dateline,
				status.statusid AS contentid,
				\':contentType\' AS contenttypeid,
				favourite.userid IS NOT NULL AS isfavourite				
				:avatarQuery
			FROM (SELECT statusid FROM $dbtech_livewall_status ' . ($lastId != -1 ? 'WHERE statusid ' . ($fetchOne != false ? '= ' : '> ') . intval($lastId) : '') . ' ORDER BY statusid DESC LIMIT ' . $this->registry->options['dbtech_livewall_maxentries'] . ') AS tmp
			LEFT JOIN $dbtech_livewall_status AS status ON(status.statusid = tmp.statusid)
			LEFT JOIN $user AS user ON(user.userid = status.userid)
			LEFT JOIN $dbtech_livewall_settings AS currentuser_settings ON(currentuser_settings.userid = :currentUser)
			LEFT JOIN $dbtech_livewall_settings AS actionuser_settings ON(actionuser_settings.userid = user.userid)
			LEFT JOIN $dbtech_livewall_favourite AS favourite ON(favourite.userid = :currentUser AND favourite.contenttypeid = \':contentType\' AND favourite.contentid = status.statusid)
			LEFT JOIN $userlist AS actionuser_friend ON(actionuser_friend.userid = user.userid AND actionuser_friend.relationid = :currentUser)
			:avatarJoin
			WHERE NOT FIND_IN_SET(:memberGroupIdsSet, membergroupids)
				AND usergroupid NOT :memberGroupIds
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
								)
								OR
								(
									currentuser_settings.:contentType_display = 2 AND
									actionuser_friend.type = \'buddy\'
								)
								OR
								(
									currentuser_settings.:contentType_display = 3 AND
									actionuser_friend.type = \'buddy\' AND
									actionuser_friend.friend = \'yes\'
								)
							)
						)
						AND
						(
							ISNULL(actionuser_settings.:contentType_privacy) OR 
							(
								actionuser_settings.:contentType_privacy = 0 OR
								(
									actionuser_settings.:contentType_privacy = 1 AND
									:currentUser > 0
								)
								OR
								(
									actionuser_settings.:contentType_privacy = 2 AND
									actionuser_friend.type = \'buddy\'
								)
								OR
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
			':contentType' 			=> 'statusupdate',
			':forumIds' 			=> $this->getForumIds(),
			':memberGroupIdsSet' 	=> implode(', membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIds' 		=> LIVEWALL::$db->queryList($excludedGroups),
			':onlyUser' 			=> ($onlyUser ? 'AND user.userid = ' . intval($onlyUser) : ''),
			':fetchOne' 			=> ($fetchOne ? 'LIMIT 1' : 'ORDER BY tmp.statusid DESC'),
			':limit' 				=> ($lastId == -1 ? 'LIMIT ' . ($limit == -1 ? $this->registry->options['dbtech_livewall_perpage'] : $limit) : '')
		));
	}
	
	/**
	* Creates a phrase based on the action in question
	*/	
	public function constructPhrase($info)
	{
		global $vbphrase;
		
		return construct_phrase($vbphrase['dbtech_livewall_x_updated_status'], 
			($info['userid'] ? '<a href="' . (LIVEWALL::$permissions['canviewuserwall'] ? 'livewall.php?' : 'member.php?') . $this->registry->session->vars['sessionurl'] . 'u=' . $info['userid'] . '">' . $info['username'] . '</a>' : $vbphrase['guest'])
		);
	}	
}
?>