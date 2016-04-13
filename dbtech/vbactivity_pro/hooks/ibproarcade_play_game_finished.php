<?php
// create a new discussion
VBACTIVITY::insert_points('ibarcadegame', 0, $this->arcade->user['id']);

$vbactivity_typenames = array(
	'ibarcadegame',
);
$vbactivity_loc = 'ibproarcade';

$vbauserinfo = array('userid' => $this->arcade->user['id']);

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbauserinfo);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbauserinfo);	
?>