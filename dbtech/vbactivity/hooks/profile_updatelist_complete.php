<?php
if ($vbulletin->userinfo['userid'] AND !empty($add['approvals']))
{
	foreach ($add['approvals'] as $userid)
	{
		VBACTIVITY::insert_points('friend', $userid);
		VBACTIVITY::insert_points('friend', $vbulletin->userinfo['userid'], $userid);
	}
	$whoadded = array('userid' => $vbulletin->userinfo['userid']);
	$currentuser = array('userid' => $userid);
	
	$vbactivity_typenames = array(
		'friend',
	);
	$vbactivity_loc = 'friend';
	
	/*DBTECH_PRO_START*/
	// Check promotions
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);	
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);
}
?>