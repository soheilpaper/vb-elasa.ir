<?php
do
{
	if ($this->fetch_field('userid') != $this->registry->userinfo['userid'] OR $this->condition)
	{
		// This got converted into a sysmsg
		break;
	}
	
	// create a new discussion
	VBACTIVITY::insert_points('shout', 	intval($this->fetch_field('shoutid')));
	
	$vbactivity_typenames = array(
		'shout',
	);
	$vbactivity_loc = 'shout';
	
	/*DBTECH_PRO_START*/
	// Check promotions
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $this->registry->userinfo);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $this->registry->userinfo);	
}
while (false);

?>