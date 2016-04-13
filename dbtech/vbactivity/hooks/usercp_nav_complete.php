<?php
if (method_exists($dbtech_vbactivity_nav, 'register'))
{
	// Register important variables
	$dbtech_vbactivity_nav->register('navclass', 		$navclass);
	$dbtech_vbactivity_nav->register('template_hook', 	$template_hook);
	
	$template_hook['usercp_navbar_bottom'] .= $dbtech_vbactivity_nav->render();
}
?>