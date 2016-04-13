<?php
if (strpos($userinfo['activity'], 'dbtech_vbactivity_') === 0)
{
	$handled = true;	
	switch ($userinfo['activity'])
	{
		case 'dbtech_vbactivity_activity':
			// Archive HO
			$userinfo['action'] = $vbphrase['dbtech_vbactivity_viewing_activity'];
			$userinfo['where'] = '<a href="' . $userinfo['activity_where_link'] . '">' . $vbphrase[$userinfo['activity_where_text']] . '</a>';			
			break;
			
		default:
			$handled = false;
			break;
	}
}
?>