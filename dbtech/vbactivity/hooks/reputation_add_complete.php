<?php
if ($vbulletin->userinfo['userid'])
{
	$repid = $db->query_first_slave("
		SELECT reputationid
		FROM " . TABLE_PREFIX . "reputation
		WHERE postid = $postid
			AND reputation = $score
			AND userid = $userid
			AND whoadded = " . $vbulletin->userinfo['userid'] . "
	");
	
	VBACTIVITY::insert_points('gottenrep', 	$repid['reputationid'], $userid, 						$score, TIMENOW, $foruminfo['forumid']);
	VBACTIVITY::insert_points('givenrep', 	$repid['reputationid'], $vbulletin->userinfo['userid'], $score, TIMENOW, $foruminfo['forumid']);
	
	$whoadded 		= array('userid' => $vbulletin->userinfo['userid']);
	$currentuser 	= array('userid' => $userid);
	
	$vbactivity_typenames = array(
		'givenrep',
		'gottenrep',
	);
	$vbactivity_loc = 'reputation';
	
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