<?php
if (!$messageinfo AND $edit_discussion)
{
	// create a new discussion
	VBACTIVITY::insert_points('sgdiscussion', 	$discussionid);
	
	$vbactivity_typenames = array(
		'sgdiscussion',
	);
	$vbactivity_loc = 'sgdiscussion';
	
	/*DBTECH_PRO_START*/
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbulletin->userinfo);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbulletin->userinfo);	
}
?>