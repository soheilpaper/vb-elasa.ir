<?php
class LiveWall_ContentType_aptl extends LiveWall_ContentType_Core
{
	/**
	* Function to call before every action
	*/	
	public function preCheck()
	{
		if (
			!$this->registry->products['dbtech_thanks']
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
				entry.*,
				user.*,
				post.postid,
				post.title AS posttitle,
				thread.title AS threadtitle,
				thread.title AS title,
				thread.threadid,
				thread.forumid,				
				recipient.username AS recipientusername,
				recipient.userid AS recipientuserid,
				entry.dateline,
				entry.entryid AS contentid,
				\':contentType\' AS contenttypeid,
				favourite.userid IS NOT NULL AS isfavourite				
				:avatarQuery
			FROM (SELECT entryid FROM $dbtech_thanks_entry WHERE contenttype = \'post\'' . ($lastId != -1 ? 'AND entryid ' . ($fetchOne != false ? '= ' : '> ') . intval($lastId) : '') . ' ORDER BY entryid DESC LIMIT ' . $this->registry->options['dbtech_livewall_maxentries'] . ') AS tmp
			LEFT JOIN $dbtech_thanks_entry AS entry ON(entry.entryid = tmp.entryid)
			LEFT JOIN $user AS user ON(user.userid = entry.userid)
			LEFT JOIN $user AS recipient ON(recipient.userid = entry.receiveduserid)
			LEFT JOIN $dbtech_livewall_settings AS currentuser_settings ON(currentuser_settings.userid = :currentUser)
			LEFT JOIN $dbtech_livewall_settings AS actionuser_settings ON(actionuser_settings.userid = user.userid)
			LEFT JOIN $dbtech_livewall_favourite AS favourite ON(favourite.userid = :currentUser AND favourite.contenttypeid = \':contentType\' AND favourite.contentid = entry.entryid)			
			LEFT JOIN $dbtech_livewall_settings AS recipientuser_settings ON(recipientuser_settings.userid = entry.receiveduserid)
			LEFT JOIN $userlist AS actionuser_friend ON(actionuser_friend.userid = entry.userid AND actionuser_friend.relationid = :currentUser)
			LEFT JOIN $userlist AS recipientuser_friend ON(recipientuser_friend.userid = entry.receiveduserid AND recipientuser_friend.relationid = :currentUser)
			LEFT JOIN $post AS post ON(post.postid = entry.contentid)
			LEFT JOIN $thread AS thread ON(thread.threadid = post.threadid)
			:avatarJoin
			WHERE 1=1
				AND NOT FIND_IN_SET(:memberGroupIdsSetUser, user.membergroupids)
				AND NOT FIND_IN_SET(:memberGroupIdsSetRecipient, recipient.membergroupids)
				AND user.usergroupid NOT :memberGroupIds
				AND recipient.usergroupid NOT :memberGroupIds
				AND
				(
					entry.userid = :currentUser OR
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
					entry.receiveduserid = :currentUser OR
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
			':contentType' 					=> 'aptl',
			':memberGroupIdsSetUser' 		=> implode(', user.membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIdsSetRecipient' 	=> implode(', recipient.membergroupids) AND NOT FIND_IN_SET(', $excludedGroups),
			':memberGroupIds' 				=> LIVEWALL::$db->queryList($excludedGroups),
			':onlyUser' 					=> ($onlyUser ? 'AND (user.userid = ' . intval($onlyUser) . ' OR entry.receiveduserid = ' . intval($onlyUser) . ')' : ''),
			':fetchOne' 					=> ($fetchOne ? 'LIMIT 1' : 'ORDER BY tmp.entryid DESC'),
			':limit' 						=> ($lastId == -1 ? 'LIMIT ' . ($limit == -1 ? $this->registry->options['dbtech_livewall_perpage'] : $limit) : '')
		));
		
		foreach ((array)$data as $key => $info)
		{
			if (!($this->registry->userinfo['forumpermissions'][$info['forumid']] & $this->registry->bf_ugp_forumpermissions['canview']) OR !verify_forum_password($info['forumid'], $this->registry->forumcache[$info['forumid']]['password'], false))
			{
				// Set excluded forum
				unset($data[$key]);
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
		
		$lookup = array();
		foreach (THANKS::$cache['button'] as $button)
		{
			$lookup[$button['varname']] = $button;
		}
		
		return construct_phrase($vbphrase['dbtech_thanks_x_clicked_y_for_z_post_a'],
			'<a href="' . (LIVEWALL::$permissions['canviewuserwall'] ? 'livewall.php?' : 'member.php?') . $this->registry->session->vars['sessionurl'] . 'u=' . $info['userid'] . '" target="_blank">' . $info['username'] . '</a>',
			$lookup[$info['varname']]['title'],
			'<a href="showthread.php?' . $this->registry->session->vars['sessionurl'] . 'p=' . $info['postid'] . '#post' . $info['postid'] . '">' . $info['threadtitle'] . '</a>',
			'<a href="member.php?' . $this->registry->session->vars['sessionurl'] . 'u=' . $info['recipientuserid'] . '" target="_blank">' . $info['recipientusername'] . '</a>'
		);
	}	
}
?>