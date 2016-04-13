<?php
if (!$this->condition)
{
	if ($this->fetch_field('userid') != $this->fetch_field('postuserid'))
	{		
		// Not someone posting on their own profile
		VBACTIVITY::insert_points('vmgiven', 	$vmid, $this->fetch_field('postuserid'));
		VBACTIVITY::insert_points('vmreceived', $vmid, $this->fetch_field('userid'));
		
		$whoadded = array('userid' => $this->fetch_field('postuserid'));
		$currentuser = array('userid' => $this->fetch_field('userid'));

		// List all type names
		$vbactivity_typenames = array(
			'vmgiven',
			'vmreceived',
		);
		$vbactivity_loc = 'visitormessage';
		
		/*DBTECH_PRO_START*/
		// Check promotions
		VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
		VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
		/*DBTECH_PRO_END*/
		
		// Check achievements
		VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);	
		VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);
	}
}
?>