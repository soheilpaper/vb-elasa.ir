<?php
if (!$new_discussion AND !$gmid)
{
	VBACTIVITY::insert_points('sgmessage', 	$this->fetch_field('lastpostid'));
	
	$vbactivity_typenames = array(
		'sgmessage',
	);
	$vbactivity_loc = 'sgmessage';
	
	/*DBTECH_PRO_START*/
	// Check promotions
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $this->registry->userinfo);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $this->registry->userinfo);	
}
?>