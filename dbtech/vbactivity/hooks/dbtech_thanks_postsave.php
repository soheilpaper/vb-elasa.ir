<?php
// create a new discussion
VBACTIVITY::insert_points($varname . 'given', 		$entryid, $vbulletin->userinfo['userid'], 	1, TIMENOW, $post['forumid']);
VBACTIVITY::insert_points($varname . 'received', 	$entryid, $post['userid'], 					1, TIMENOW, $post['forumid']);

$whoadded = array('userid' => $vbulletin->userinfo['userid']);
$currentuser = array('userid' => $post['userid']);

$vbactivity_typenames = array(
	$varname . 'given',
	$varname . 'received',
);
$vbactivity_loc = 'thanks';

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);
?>