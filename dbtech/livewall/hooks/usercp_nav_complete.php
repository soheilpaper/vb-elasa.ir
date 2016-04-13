<?php
if (method_exists($dbtech_livewall_nav, 'register'))
{
	// Register important variables
	$dbtech_livewall_nav->register('navclass', 			$navclass);
	$dbtech_livewall_nav->register('template_hook', 	$template_hook);
	
	$template_hook['usercp_navbar_bottom'] .= $dbtech_livewall_nav->render();
}
?>