<?php
if (strpos($userinfo['activity'], 'dbtech_livewall_') === 0)
{
	$handled = true;	
	switch ($userinfo['activity'])
	{
		case 'dbtech_vbshop_':
			// Archive HO
			$userinfo['action'] = $vbphrase['dbtech_livewall_viewing_support'];
			$userinfo['where'] = '<a href="livewall.php' . $vbulletin->session->vars['sessionurl_q'] . '">' . $vbphrase['dbtech_livewall_wol_support'] . '</a>';			
			break;
			
		default:
			$handled = false;
			break;
	}
}
?>