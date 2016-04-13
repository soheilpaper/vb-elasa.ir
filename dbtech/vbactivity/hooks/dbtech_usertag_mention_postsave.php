<?php
// create a new discussion
VBACTIVITY::insert_points('mentionsgiven', 		$post['postid'], $userinfo['userid'], 		1, TIMENOW, $threadinfo['forumid']);
VBACTIVITY::insert_points('mentionsreceived', 	$post['postid'], $usertaginfo['userid'], 	1, TIMENOW, $threadinfo['forumid']);

$whoadded = array('userid' => $userinfo['userid']);
$currentuser = array('userid' => $usertaginfo['userid']);

$vbactivity_typenames = array(
	'mentionsgiven',
	'mentionsreceived',
);
$vbactivity_loc = 'mention';

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);	
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);	
?>