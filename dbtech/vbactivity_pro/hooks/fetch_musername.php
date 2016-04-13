<?php
do
{
	if (!$vbulletin->options['dbtech_vbactivity_enable_trophies'])
	{
		// Trophies are disabled
		break;
	}

	if (isset($vbulletin->userinfo['dbtech_vbactivity_settings']) AND ($vbulletin->userinfo['dbtech_vbactivity_settings'] & 32768))
	{
		// User-disabled trophies
		break;
	}

	if (!$vbulletin->options['dbtech_vbactivity_trophy_location'])
	{
		// Trophies are in an invalid location
		break;
	}

	if (THIS_SCRIPT != 'showthread')
	{
		// Only display this on showthread
		break;
	}

	if (!class_exists('VBACTIVITY'))
	{
		// Class was missing
		break;
	}

	$trophylist = array();	
	foreach ((array)VBACTIVITY::$cache['type'] as $typeid => $trophy)
	{
		if (!$trophy['active'])
		{
			// Inactive
			continue;
		}
		
		if (!$trophy['icon'])
		{
			// No icon
			continue;
		}
		
		if ($trophy['userid'] != $user['userid'])
		{
			// Belongs to someone else
			continue;
		}
		
		// Store the trophy
		$trophylist["$typeid"] = $trophy;
		
		if (count($trophylist) == $vbulletin->options['dbtech_vbactivity_trophy_limit'])
		{
			// Aaand we're done
			break;
		}
	}
	
	$trophies = '';
	foreach ($trophylist as $typeid => $trophy)
	{		
		// Add the icon
		$trophies .= '<img border="0" src="' . (VB_AREA != 'Forum' ? '../' : '') . 'images/icons/vbactivity/' . $trophy['icon'] . '" alt="' . $trophy['trophyname'] . '" /> ';
	}
	
	if ($vbulletin->options['dbtech_vbactivity_trophy_location'] & 1)
	{
		// Before username
		$user['musername'] = $trophies . $user['musername'];
	}
	
	if ($vbulletin->options['dbtech_vbactivity_trophy_location'] & 2)
	{
		// After username
		$user['musername'] .= ' ' . $trophies;
	}
}
while (false);
?>