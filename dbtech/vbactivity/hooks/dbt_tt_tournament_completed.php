<?php
VBACTIVITY::insert_points('tttournywon', 	$tournament['tid'], $match_winner);

$vbactivity_typenames = array(
	'tttournywon',
);
$vbactivity_loc = 'triad';

$vbauserinfo = array('userid' => $match_winner);

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $vbauserinfo);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $vbauserinfo);	
?>