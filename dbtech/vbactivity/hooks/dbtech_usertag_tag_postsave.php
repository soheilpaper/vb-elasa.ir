<?php
// create a new discussion
VBACTIVITY::insert_points('tagsgiven', 		$threadid, $vbulletin->userinfo['userid'], 	1, TIMENOW, $threadinfo['forumid']);
VBACTIVITY::insert_points('tagsreceived', 	$threadid, $results_r['userid'], 			1, TIMENOW, $threadinfo['forumid']);

$whoadded = array('userid' => $vbulletin->userinfo['userid']);
$currentuser = array('userid' => $results_r['userid']);

$vbactivity_typenames = array(
	'tagsgiven',
	'tagsreceived',
);
$vbactivity_loc = 'tag';

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);	
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);	
?>