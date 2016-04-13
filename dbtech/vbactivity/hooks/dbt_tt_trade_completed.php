<?php
VBACTIVITY::insert_points('tttrade', 	$trade['tid'], $trade['t_userid']);
VBACTIVITY::insert_points('tttrade', 	$trade['tid'], $vbulletin->userinfo['userid']);

$whoadded = array('userid' => $trade['t_userid']);
$currentuser = $vbulletin->userinfo;

$vbactivity_typenames = array(
	'tttrade',
);
$vbactivity_loc = 'triadtrade';

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);	
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);	
?>