<?php
$whoadded = array('userid' => $this->fetch_field('userid', 'filedata'));

if (!$failed)
{
	VBACTIVITY::insert_points('attachments', $this->fetch_field('filedataid', 'filedata'), $whoadded['userid']);
}
else
{
	if ($existing = VBACTIVITY::$db->fetchRow('
		SELECT * FROM $dbtech_vbactivity_pointslog
		WHERE typeid = ?
			AND idfield = ?
			AND userid = ?
	', array(
		VBACTIVITY::fetch_type('attachments'),
		$this->fetch_field('filedataid', 'filedata'),
		$whoadded['userid']
	)))
	{
		// Negate the points
		VBACTIVITY::insert_points('attachments', $this->fetch_field('filedataid', 'filedata'), $whoadded['userid'], -1);
	}
}

$vbactivity_typenames = array(
	'attachments',
);
$vbactivity_loc = 'attachments';

/*DBTECH_PRO_START*/
// Check promotions
VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
/*DBTECH_PRO_END*/

// Check achievements
VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);	
?>