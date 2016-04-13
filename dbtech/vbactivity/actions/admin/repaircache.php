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
@set_time_limit(0);
ignore_user_abort(1);

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// Rebuild the cache
VBACTIVITY_CACHE::buildAll();

if (VBACTIVITY::$permissions['options'])
{
	// Only redirect if we can see settings
	define('CP_REDIRECT', 'vbactivity.php?do=options');
}
print_stop_message('dbtech_vbactivity_x_y', $vbphrase['dbtech_vbactivity_cache'], $vbphrase['dbtech_vbactivity_repaired']);