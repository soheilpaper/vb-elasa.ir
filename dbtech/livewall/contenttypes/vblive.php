<?php
class LiveWall_ContentType_vblive extends LiveWall_ContentType_Core
{
	/**
	* Function to call before every action
	*/	
	public function preCheck()
	{
		if (
			!$this->registry->products['dmods_live']
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
		
		$data = LIVEWALL::$db->fetchAll('
			SELECT
				status.*,
				user.*,
				status.dateline,
				status.streamid AS contentid,
				\':contentType\' AS contenttypeid,
				favourite.userid IS NOT NULL AS isfavourite				
				:avatarQuery
			FROM (SELECT streamid FROM $dmods_stream ' . ($lastId != -1 ? 'WHERE streamid ' . ($fetchOne != false ? '= ' : '> ') . intval($lastId) : '') . ' ORDER BY streamid DESC LIMIT ' . $this->registry->options['dbtech_livewall_maxentries'] . ') AS tmp
			LEFT JOIN $dmods_stream AS status ON(status.streamid = tmp.streamid)
			LEFT JOIN $user AS user ON(user.userid = status.userid)
			LEFT JOIN $dbtech_livewall_settings AS currentuser_settings ON(currentuser_settings.userid = :currentUser)
			LEFT JOIN $dbtech_livewall_settings AS actionuser_settings ON(actionuser_settings.userid = user.userid)
			LEFT JOIN $dbtech_livewall_favourite AS favourite ON(favourite.userid = :currentUser AND favourite.contenttypeid = \':contentType\' AND favourite.contentid = status.streamid)
			LEFT JOIN $userlist AS actionuser_friend ON(actionuser_friend.userid = user.userid AND actionuser_friend.relationid = :currentUser)
			:avatarJoin
			WHERE status.type = \'status\'
				AND
				(
					status.touserid = 0 OR 
					status.touserid = :currentUser
				)
				AND NOT FIND_IN_SET(:memberGroupIdsSet, membergroupids)
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
			':contentType' 			=> 'vblive',
			':forumIds' 			=> $this->getForumIds(),
			':memberGroupIdsSet' 	=> implode(', membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIds' 		=> LIVEWALL::$db->queryList($excludedGroups),
			':onlyUser' 			=> ($onlyUser ? 'AND user.userid = ' . intval($onlyUser) : ''),
			':fetchOne' 			=> ($fetchOne ? 'LIMIT 1' : 'ORDER BY tmp.streamid DESC'),
			':limit' 				=> ($lastId == -1 ? 'LIMIT ' . ($limit == -1 ? $this->registry->options['dbtech_livewall_perpage'] : $limit) : '')
		));
		
		foreach ((array)$data as $key => $info)
		{
			if (!$arr = @unserialize($info['parameters']))
			{
				// Unserialize failed, possibly utf8 characters stored here
				if (!$arr = @unserialize(utf8_encode($info['parameters'])))
				{
					// There's nothing we can do here =[
					$data[$key]['pagetext'] = 'N/A';
				}
				else
				{
					// Decode UTF-8 characters
					$data[$key]['pagetext'] = utf8_decode($arr['status']);					
				}
			}
			else
			{
				// Worked right off the bat
				$data[$key]['pagetext'] = $arr['status'];					
			}
		}
		
		return $data;
	}
	
	/**
	* Creates a phrase based on the action in question
	*/	
	public function constructPhrase($info)
	{
		global $vbphrase;
		
		return construct_phrase($vbphrase['dbtech_livewall_x_updated_status'], 
			'<a href="' . (LIVEWALL::$permissions['canviewuserwall'] ? 'livewall.php?' : 'member.php?') . $this->registry->session->vars['sessionurl'] . 'u=' . $info['userid'] . '">' . $info['username'] . '</a>'
		);
	}	
}
?>