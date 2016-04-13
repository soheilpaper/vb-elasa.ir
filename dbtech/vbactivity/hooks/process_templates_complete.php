<?php
global $vbulletin, $vbphrase, $template_hook;

if (VBACTIVITY::$permissions['canview'] AND version_compare($vbulletin->versionnumber, '4.2.0', '<'))
{
	if (defined('IN_VBACTIVITY'))
	{
		$vbulletin->options['selectednavtab'] = 'dbtech_vbactivity';
	}
	
	if (intval($vbulletin->versionnumber) > 3 AND (defined('CMS_SCRIPT') OR defined('VBA_SCRIPT')) AND !defined('VBACTIVITY_NAV_LOOPED') AND THIS_SCRIPT != 'vbactivity')
	{
		// vB4 have an awkward design quirk with the Suite, we'll fire the plugin elsewhere
		define('VBACTIVITY_NAV_LOOPED', true);
		$vbulletin->pluginlist['process_templates_complete'] .= "\r\nrequire(DIR . '/dbtech/vbactivity/hooks/process_templates_complete.php');";
		vBulletinHook::set_pluginlist($vbulletin->pluginlist);
	}
	else
	{	
		if ($vbulletin->options['dbtech_vbactivity_integration'] & 1)
		{
			$template_hook['navbar_quick_links_menu_pos4'] .= vB_Template::create('dbtech_vbactivity_quicklinks_link')->render();
		}
		if ($vbulletin->options['dbtech_vbactivity_integration'] & 2)
		{
			$template_hook['navbar_community_menu_end'] .= vB_Template::create('dbtech_vbactivity_quicklinks_link')->render();
		}
		
		if ($vbulletin->options['dbtech_vbactivity_navbar'])
		{
			if (intval($vbulletin->versionnumber) == 3)
			{
				$template_hook['navbar_buttons_right'] .= vB_Template::create('dbtech_vbactivity_navbar_link')->render();
			}
			else
			{
				// Hook into nav tab
				$template_hook['navtab_middle'] .= vB_Template::create('dbtech_vbactivity_navbar_link')->render();
			}
		}
	}
}
?>