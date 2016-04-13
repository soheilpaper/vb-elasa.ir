<?php
if ($this->registry->userinfo['userid'])
{
	// Are we handeling a multi DM
	if (!$this->condition AND $this->existing['vote'] != $this->fetch_field('vote'))
	{
		VBACTIVITY::insert_points('threadrating', 			$this->fetch_field('threadrateid'), $this->registry->userinfo['userid'], 	1, TIMENOW, $threadinfo['forumid']);
		VBACTIVITY::insert_points('threadratingreceived', 	$this->fetch_field('threadrateid'), $threadinfo['postuserid'], 				1, TIMENOW, $threadinfo['forumid']);
		
		$currentuser = array('userid' => $threadinfo['postuserid']);
		
		$vbactivity_typenames = array(
			'threadrating',
			'threadratingreceived',
		);
		$vbactivity_loc = 'threadrating';

		/*DBTECH_PRO_START*/		
		// Check achievements
		VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $this->registry->userinfo);	
		
		if ($this->registry->userinfo['userid'] != $threadinfo['postuserid'])
		{
			// Check achievements
			VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
		}
		/*DBTECH_PRO_END*/
		
		// Check achievements
		VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $this->registry->userinfo);	
		
		if ($this->registry->userinfo['userid'] != $threadinfo['postuserid'])
		{
			// Check achievements
			VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);
		}
	}
}
?>