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

if (!VBACTIVITY::$permissions['grantawards'])
{
	// No permissions!
	print_cp_message($vbphrase['dbtech_vbactivity_nopermission_cp']);
}

$_REQUEST['action'] = 'requests';

require(DIR . '/dbtech/vbactivity/actions/admin/award.php');