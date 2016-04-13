<?php
if ($this->registry->userinfo['userid'] AND !$this->condition)
{
	// Avoid poisoning a variable just in case
	$thread_info = $this->dbobject->query_first_slave("
		SELECT forumid
		FROM " . TABLE_PREFIX . "thread
		WHERE pollid = " . $this->fetch_field('pollid')
	);
	
	VBACTIVITY::insert_points('pollvote', $this->fetch_field('pollvoteid'), $this->fetch_field('userid'), 1, TIMENOW, $thread_info['forumid']);
	
	$vbactivity_typenames = array(
		'pollvote',
	);
	$vbactivity_loc = 'pollvote';
	
	/*DBTECH_PRO_START*/
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $this->registry->userinfo);
	/*DBTECH_PRO_END*/

	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $this->registry->userinfo);	
}

?>