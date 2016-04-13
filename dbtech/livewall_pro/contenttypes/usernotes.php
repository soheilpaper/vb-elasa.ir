<?php
class LiveWall_ContentType_usernotes extends LiveWall_ContentType_Core
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
				usernote.*,
				user.*,
				usernote.message AS pagetext,
				recipient.username AS recipientusername,
				recipient.userid AS recipientuserid,
				usernote.dateline,
				usernote.usernoteid AS contentid,
				\':contentType\' AS contenttypeid,
				favourite.userid IS NOT NULL AS isfavourite				
				:avatarQuery
			FROM (SELECT usernoteid FROM $usernote ' . ($lastId != -1 ? 'WHERE usernoteid ' . ($fetchOne != false ? '= ' : '> ') . intval($lastId) : '') . ' ORDER BY usernoteid DESC LIMIT ' . $this->registry->options['dbtech_livewall_maxentries'] . ') AS tmp
			LEFT JOIN $usernote AS usernote ON(usernote.usernoteid = tmp.usernoteid)
			LEFT JOIN $user AS user ON(user.userid = usernote.posterid)
			LEFT JOIN $user AS recipient ON(recipient.userid = usernote.userid)
			LEFT JOIN $dbtech_livewall_settings AS currentuser_settings ON(currentuser_settings.userid = :currentUser)
			LEFT JOIN $dbtech_livewall_settings AS actionuser_settings ON(actionuser_settings.userid = usernote.posterid)
			LEFT JOIN $dbtech_livewall_favourite AS favourite ON(favourite.userid = :currentUser AND favourite.contenttypeid = \':contentType\' AND favourite.contentid = usernote.usernoteid)			
			LEFT JOIN $dbtech_livewall_settings AS recipientuser_settings ON(recipientuser_settings.userid = usernote.userid)
			LEFT JOIN $userlist AS actionuser_friend ON(actionuser_friend.userid = usernote.posterid AND actionuser_friend.relationid = :currentUser)
			LEFT JOIN $userlist AS recipientuser_friend ON(recipientuser_friend.userid = usernote.userid AND recipientuser_friend.relationid = :currentUser)
			:avatarJoin
			WHERE 1=1
				AND NOT FIND_IN_SET(:memberGroupIdsSetUser, user.membergroupids)
				AND NOT FIND_IN_SET(:memberGroupIdsSetRecipient, recipient.membergroupids)
				AND user.usergroupid NOT :memberGroupIds
				AND recipient.usergroupid NOT :memberGroupIds
				AND
				(
					(
						usernote.userid = :currentUser AND
						' . (int)($this->registry->userinfo['permissions']['genericpermissions'] & $this->registry->bf_ugp_genericpermissions['canviewownusernotes']) . ' != 0
					)
					OR
					(
						usernote.posterid = :currentUser AND
						' . (int)($this->registry->userinfo['permissions']['genericpermissions'] & $this->registry->bf_ugp_genericpermissions['canviewothersusernotes']) . ' != 0
					)
				)
				AND
				(
					usernote.posterid = :currentUser OR
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
				AND
				(
					usernote.userid = :currentUser OR
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
									recipientuser_friend.type = \'buddy\'
								)
								OR
								(
									currentuser_settings.:contentType_display = 3 AND
									recipientuser_friend.type = \'buddy\' AND
									recipientuser_friend.friend = \'yes\'
								)
							)
						)
						AND
						(
							ISNULL(recipientuser_settings.:contentType_privacy) OR 
							(
								recipientuser_settings.:contentType_privacy = 0 OR
								(
									recipientuser_settings.:contentType_privacy = 1 AND
									:currentUser > 0
								)
								OR
								(
									recipientuser_settings.:contentType_privacy = 2 AND
									recipientuser_friend.type = \'buddy\'
								)
								OR
								(
									recipientuser_settings.:contentType_privacy = 3 AND
									recipientuser_friend.type = \'buddy\' AND
									recipientuser_friend.friend = \'yes\'
								)
							)
						)						
					)
				)
				:onlyUser
			:fetchOne
			:limit
		', array(
			':avatarQuery' 					=> ($this->registry->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : ''),
			':avatarJoin' 					=> ($this->registry->options['avatarenabled'] ? 'LEFT JOIN $avatar AS avatar ON (avatar.avatarid = user.avatarid) LEFT JOIN $customavatar AS customavatar ON (customavatar.userid = user.userid)' : ''),
			':currentUser' 					=> intval($this->registry->userinfo['userid']),
			':contentType' 					=> 'usernotes',
			':memberGroupIdsSetUser' 		=> implode(', user.membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIdsSetRecipient' 	=> implode(', recipient.membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIds' 				=> LIVEWALL::$db->queryList($excludedGroups),
			':onlyUser' 					=> ($onlyUser ? 'AND (user.userid = ' . intval($onlyUser) . ' OR usernote.posterid = ' . intval($onlyUser) . ')' : ''),
			':fetchOne' 					=> ($fetchOne ? 'LIMIT 1' : 'ORDER BY tmp.usernoteid DESC'),
			':limit' 						=> ($lastId == -1 ? 'LIMIT ' . ($limit == -1 ? $this->registry->options['dbtech_livewall_perpage'] : $limit) : '')
		));
	}
	
	/**
	* Creates a phrase based on the action in question
	*/	
	public function constructPhrase($info)
	{
		global $vbphrase;
		
		return construct_phrase($vbphrase['dbtech_livewall_x_left_usernote_y'], 
			'<a href="' . (LIVEWALL::$permissions['canviewuserwall'] ? 'livewall.php?' : 'member.php?') . $this->registry->session->vars['sessionurl'] . 'u=' . $info['userid'] . '">' . $info['username'] . '</a>',
			'<a href="usernote.php?' . $this->registry->session->vars['sessionurl'] . 'do=viewuser&amp;u=' . $info['recipientuserid'] . '">' . $info['recipientusername'] . '</a>'
		);
	}	
}
?>