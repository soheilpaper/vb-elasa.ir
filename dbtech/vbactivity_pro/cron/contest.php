<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

if (!is_object($vbulletin->db))
{
	exit;
}


// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

foreach ((array)VBACTIVITY::$cache['contest'] as $contestid => $contest)
{
	if ($contest['end'] > TIMENOW)
	{
		// Contest hasn't ended
		continue;
	}
	
	if (count($contest['winners']))
	{
		// We've already drawn the winners
		continue;
	}
	
	if (!$contest['numwinners'])
	{
		// Contest has no winners
		continue;
	}

	// Load the contest type info
	$contestObj = VBACTIVITY::initContest($contest);

	if (method_exists($contestObj, 'finishContest'))
	{
		// Finish contest
		$contestObj->finishContest();
	}
}

foreach ((array)VBACTIVITY::$cache['contest'] as $contestid => $contest)
{
	if ($contest['end'] > TIMENOW)
	{
		// Contest hasn't ended
		continue;
	}
	
	$contest['recurtime'] = $contest['end'] + $contest['recurring'];
	if (
		mktime(0, 0, 0, date('n', TIMENOW), 				date('j', TIMENOW), 				date('Y', TIMENOW)) != 
		mktime(0, 0, 0, date('n', $contest['recurtime']), 	date('j', $contest['recurtime']), 	date('Y', $contest['recurtime']))
	)
	{
		// This contest has already recurred
		continue;
	}
	
	if (!$contest['recurring'])
	{
		// Contest isn't recurring
		continue;
	}

	if ($contest['recurtime'] > TIMENOW)
	{
		// Hold on, the contest isn't due yet
		continue;
	}

	// Grab the difference in seconds
	$difference = $contest['end'] - $contest['start'];

	// Grab our current values
	$month = vbdate('n', 	$contest['end'], false, false);
	$day = vbdate('j', 		$contest['end'], false, false);
	$year = vbdate('Y', 	$contest['end'], false, false);
	$hour = vbdate('G', 	$contest['start'], false, false);
	$minute = vbdate('i', 	$contest['start'], false, false);

	switch ($contest['recurring'])
	{
		case 86400: $day++; break;
		case 604800: $day += 7; break;
		case 2419200: $month++; break;
		case 29030400: $year++; break;

	}

	// Set new start date
	$contest['start'] = vbmktime($hour, $minute, 0, $month, $day, $year);

	// Set new end date
	$contest['end'] = $contest['start'] + $difference;

	// Get rid of this data
	unset($contest['contestid'], $contest['winners']);

	// init data manager
	$dm =& VBACTIVITY::initDataManager('Contest', $vbulletin, ERRTYPE_CP);

	// contest fields
	foreach ($contest AS $key => $val)
	{
		$dm->set($key, $val);
	}
	
	// Save! Hopefully.
	$dm->save();
}

log_cron_action('', $nextitem, 1);