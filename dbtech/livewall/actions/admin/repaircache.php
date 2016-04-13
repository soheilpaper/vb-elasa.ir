<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2007-2009 Fillip Hannisdal AKA Revan/NeoRevan/Belazor # ||
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
LIVEWALL_CACHE::build_cache('contenttype', 'ORDER BY `title` ASC');

($hook = vBulletinHook::fetch_hook('dbtech_livewall_repaircache')) ? eval($hook) : false;

define('CP_REDIRECT', 'livewall.php?do=options');
print_stop_message('dbtech_livewall_x_y', $vbphrase['dbtech_livewall_cache'], $vbphrase['dbtech_livewall_repaired']);

/*======================================================================*\
|| #################################################################### ||
|| # Created: 16:52, Thu Sep 18th 2008								  # ||
|| # SVN: $Rev$									 					  # ||
|| #################################################################### ||
\*======================================================================*/
?>