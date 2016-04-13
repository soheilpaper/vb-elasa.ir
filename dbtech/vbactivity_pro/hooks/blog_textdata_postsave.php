<?php
if (
	$this->registry->userinfo['userid'] AND
	//!$vbulletin->userinfo['dbtech_vbshout_banned'] AND
	$this->fetch_field('state') == 'visible' AND
	!$this->condition
)
{
	// create a new discussion
	VBACTIVITY::insert_points('blogcomment', 	intval($this->fetch_field('blogtextid')));
	
	$vbactivity_typenames = array(
		'blogcomment',
	);
	$vbactivity_loc = 'blogcomment';
	
	// Check promotions
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $this->registry->userinfo);
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $this->registry->userinfo);	

}
?>