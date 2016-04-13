<?php
$cache[] = 'dbtech_vbactivity_memberaction_dropdown';
switch (THIS_SCRIPT)
{
	case 'member':
		$cache = array_merge(array(
			'dbtech_vbactivity_memberinfo_trophybit',
		), $cache);
		break;
}

if (intval($vbulletin->versionnumber) == 3)
{
	$cache[] = 'dbtech_vbactivity.css';
	
	$globaltemplates = array_merge($globaltemplates, $cache);
}
?>