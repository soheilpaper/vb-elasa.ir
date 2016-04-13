<?php
if ($userdata->pre_save())
{
	// query all required profile fields
	$profilefields = VBACTIVITY::$db->fetchAll('SELECT * FROM $profilefield AS profilefield WHERE editable IN (1,2) AND required IN(1,3)');
	
	$earnedPoints = false;
	foreach ($profilefields as $profilefield)
	{
		if ($vbulletin->userinfo['field' . $profilefield['profilefieldid']] === '')
		{
			// Empty profile field
			$earnedPoints = true;
			break;
		}
	}
	
	if ($earnedPoints)
	{
		// We've earned points because we filled out a field that was previously empty
		VBACTIVITY::insert_points('profilecomplete', 0, $vbulletin->userinfo['userid']);		
	}
}
?>