<?php
if (!$eventinfo['eventid'])
{
	VBACTIVITY::insert_points('calendarevent', $eventid);
	
	$vbactivity_typenames = array(
		'calendarevent',
	);
	$vbactivity_loc = 'calendarevent';
	
	/*DBTECH_PRO_START*/
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbulletin->userinfo);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbulletin->userinfo);
}
?>