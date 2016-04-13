<?php
if ($vbulletin->userinfo['userid'])
{
	VBACTIVITY::insert_points('pollposted', $pollid, $vbulletin->userinfo['userid'], 1, TIMENOW, $threadinfo['forumid']);
	
	$vbactivity_typenames = array(
		'pollposted',
	);
	$vbactivity_loc = 'pollpost';
	
	/*DBTECH_PRO_START*/
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbulletin->userinfo);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbulletin->userinfo);	
}
?>