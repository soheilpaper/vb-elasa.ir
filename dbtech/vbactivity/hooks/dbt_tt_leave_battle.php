<?php
VBACTIVITY::insert_points('ttbattle' . $field, 	self::$battle['bid'], $player['p_userid']);

$vbactivity_typenames = array(
	'ttbattle' . $field,
);
$vbactivity_loc = 'triad';

$vbauserinfo = array('userid' => $player['p_userid']);

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbauserinfo);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbauserinfo);	
?>