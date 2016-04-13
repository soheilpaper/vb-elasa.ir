<?php
do
{
	if ($this->condition)
	{
		// Was a post edit, ignore
		break;
	}

	if (!$this->fetch_field('postuserid'))
	{
		// Guest post
		break;
	}

	if (!$this->info['forum']['countposts'])
	{
		// Post count disabled
		break;
	}

	if ($this->fetch_field('postuserid') == $this->registry->userinfo['userid'])
	{
		// Grab current user info
		$postuserinfo =& $this->registry->userinfo;
		$postuserinfo['posts']++;
	}
	else
	{
		// Grab user info
		$postuserinfo = fetch_userinfo($this->fetch_field('postuserid'));
	}

	// Type was thread
	$points = 'thread';
	
	// Thread id
	$idfield = $this->fetch_field('threadid');
	
	// Insert points
	VBACTIVITY::insert_points($points, $idfield, $postuserinfo['userid'], 1, TIMENOW, $this->info['forum']['forumid']);
	
	$vbactivity_typenames = array(
		'post',
		'thread',
	);
	$vbactivity_loc = 'threadpost';
	
	/*DBTECH_PRO_START*/
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $postuserinfo);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $postuserinfo);

	if ($this->registry->options['dbtech_vbactivity_postping_interval'])
	{
		global $vbphrase;

		$intervals = preg_split('#\s*,\s*#s', $this->registry->options['dbtech_vbactivity_postping_interval'], -1, PREG_SPLIT_NO_EMPTY);

		foreach ($intervals as $postcount)
		{
			if ($postuserinfo['posts'] == $postcount)
			{
				// Grab title and message
				$title = construct_phrase($vbphrase['dbtech_vbactivity_postping_title'], vb_number_format($postcount));
				$message = construct_phrase($vbphrase['dbtech_vbactivity_postping_body'],
					vb_number_format($postcount),
					$this->registry->options['bbtitle']
				);

				// Send a new PM
				VBACTIVITY::sendPM($postuserinfo, $title, $message, 'dbtech_vbactivity_postping_pm');
			}
		}
	}
}
while (false);
?>