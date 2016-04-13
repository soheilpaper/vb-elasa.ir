<?php
if ($delete != 1)
{
	// create a new discussion
	VBACTIVITY::insert_points('dbgalleryupload', 	$vbulletin->GPC['id']);
	
	$vbactivity_typenames = array(
		'dbgalleryupload',
	);
	$vbactivity_loc = 'dbgalleryupload';
	
	/*DBTECH_PRO_START*/
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbulletin->userinfo);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbulletin->userinfo);
}
?>