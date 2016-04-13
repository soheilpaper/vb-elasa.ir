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

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// Add to the navbits
$navbits[''] = $pagetitle = $vbphrase['dbtech_vbactivity_main'];

// draw cp nav bar
VBACTIVITY::setNavClass('main');

// Create the archive template
$templater = vB_Template::create('dbtech_vbactivity_main');
	$templater->register('pagetitle', $pagetitle);
$HTML = $templater->render();