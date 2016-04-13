<?php
class LiveWall_ContentType_visitormessages extends LiveWall_ContentType_Core
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
				visitormessage.*,
				user.*,
				recipient.username AS recipientusername,
				recipient.userid AS recipientuserid,
				visitormessage.dateline,
				visitormessage.vmid AS contentid,
				\':contentType\' AS contenttypeid,
				favourite.userid IS NOT NULL AS isfavourite				
				:avatarQuery
			FROM (SELECT vmid FROM $visitormessage ' . ($lastId != -1 ? 'WHERE vmid ' . ($fetchOne != false ? '= ' : '> ') . intval($lastId) : '') . ' ORDER BY vmid DESC LIMIT ' . $this->registry->options['dbtech_livewall_maxentries'] . ') AS tmp
			LEFT JOIN $visitormessage AS visitormessage ON(visitormessage.vmid = tmp.vmid)
			LEFT JOIN $user AS user ON(user.userid = visitormessage.postuserid)
			LEFT JOIN $user AS recipient ON(recipient.userid = visitormessage.userid)
			LEFT JOIN $dbtech_livewall_settings AS currentuser_settings ON(currentuser_settings.userid = :currentUser)
			LEFT JOIN $dbtech_livewall_settings AS actionuser_settings ON(actionuser_settings.userid = visitormessage.postuserid)
			LEFT JOIN $dbtech_livewall_favourite AS favourite ON(favourite.userid = :currentUser AND favourite.contenttypeid = \':contentType\' AND favourite.contentid = visitormessage.vmid)			
			LEFT JOIN $dbtech_livewall_settings AS recipientuser_settings ON(recipientuser_settings.userid = visitormessage.userid)
			LEFT JOIN $userlist AS actionuser_friend ON(actionuser_friend.userid = visitormessage.postuserid AND actionuser_friend.relationid = :currentUser)
			LEFT JOIN $userlist AS recipientuser_friend ON(recipientuser_friend.userid = visitormessage.userid AND recipientuser_friend.relationid = :currentUser)
			LEFT JOIN $profileblockprivacy AS profileblockprivacy ON(profileblockprivacy.userid = visitormessage.userid AND profileblockprivacy.blockid = \'visitor_messaging\')
			:avatarJoin
			WHERE 1=1
				AND visitormessage.state = \':state\'
				AND NOT FIND_IN_SET(:memberGroupIdsSetUser, user.membergroupids)
				AND NOT FIND_IN_SET(:memberGroupIdsSetRecipient, recipient.membergroupids)
				AND user.usergroupid NOT :memberGroupIds
				AND recipient.usergroupid NOT :memberGroupIds
				AND
				(
					visitormessage.postuserid = :currentUser OR
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
						)
						AND
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
						AND
						(
							ISNULL(profileblockprivacy.requirement) OR 
							(
								profileblockprivacy.requirement = 0 OR
								(
									profileblockprivacy.requirement = 1 AND
									:currentUser > 0
								) OR
								(
									profileblockprivacy.requirement = 2 AND
									actionuser_friend.type = \'buddy\'
								) OR
								(
									profileblockprivacy.requirement = 3 AND
									actionuser_friend.type = \'buddy\' AND
									actionuser_friend.friend = \'yes\'
								)
							)
						)						
					)
				)
				AND
				(
					visitormessage.userid = :currentUser OR
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
									recipientuser_friend.type = \'buddy\'
								) OR
								(
									currentuser_settings.:contentType_display = 3 AND
									recipientuser_friend.type = \'buddy\' AND
									recipientuser_friend.friend = \'yes\'
								)
							)
						) AND
						(
							ISNULL(recipientuser_settings.:contentType_privacy) OR 
							(
								recipientuser_settings.:contentType_privacy = 0 OR
								(
									recipientuser_settings.:contentType_privacy = 1 AND
									:currentUser > 0
								) OR
								(
									recipientuser_settings.:contentType_privacy = 2 AND
									recipientuser_friend.type = \'buddy\'
								) OR
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
			':contentType' 					=> 'visitormessages',
			':state' 						=> 'visible',
			':memberGroupIdsSetUser' 		=> implode(', user.membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIdsSetRecipient' 	=> implode(', recipient.membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIds' 				=> LIVEWALL::$db->queryList($excludedGroups),
			':onlyUser' 					=> ($onlyUser ? 'AND (visitormessage.userid = ' . intval($onlyUser) . ' OR visitormessage.postuserid = ' . intval($onlyUser) . ')' : ''),
			':fetchOne' 					=> ($fetchOne ? 'LIMIT 1' : 'ORDER BY tmp.vmid DESC'),
			':limit' 						=> ($lastId == -1 ? 'LIMIT ' . ($limit == -1 ? $this->registry->options['dbtech_livewall_perpage'] : $limit) : '')
		));
	}
	
	/**
	* Creates a phrase based on the action in question
	*/	
	public function constructPhrase($info)
	{
		global $vbphrase;
		
		return construct_phrase($vbphrase['dbtech_livewall_x_left_visitormessage_y'], 
			($info['userid'] ? '<a href="' . (LIVEWALL::$permissions['canviewuserwall'] ? 'livewall.php?' : 'member.php?') . $this->registry->session->vars['sessionurl'] . 'u=' . $info['userid'] . '">' . $info['username'] . '</a>' : $vbphrase['guest']),
			'<a href="member.php?' . $this->registry->session->vars['sessionurl'] . 'u=' . $info['recipientuserid'] . '">' . $info['recipientusername'] . '</a>'
		);
	}	
}
?>