<?php
if ($vbulletin->GPC['referrername'])
{
	VBACTIVITY::insert_points('referral', 	$userid, $userdata->fetch_field('referrerid'));
	
	$vbactivity_typenames = array(
		'referral',
	);
	$vbactivity_loc = 'referral';
	
	/*DBTECH_PRO_START*/
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbulletin->userinfo);
	/*DBTECH_PRO_END*/

	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbulletin->userinfo);
}

VBACTIVITY::insert_points('registration', 	$userid);

$vbactivity_typenames = array(
	'registration',
);
$vbactivity_loc = 'registration';

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbulletin->userinfo);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbulletin->userinfo);
?>