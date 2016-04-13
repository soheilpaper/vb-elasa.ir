<?php
global $vbulletin, $vbphrase, $template_hook;

// Fetch required classes
require_once(DIR . '/dbtech/vbactivity/includes/class_core.php');
require_once(DIR . '/dbtech/vbactivity/includes/class_cache.php');
if (intval($vbulletin->versionnumber) == 3 AND !class_exists('vB_Template'))
{
	// Ensure we have this
	require_once(DIR . '/dbtech/vbactivity/includes/class_template.php');
}
	
if (isset($this) AND is_object($this))
{
	// Loads the cache class
	VBACTIVITY_CACHE::init($vbulletin, $this->datastore_entries);
}
else
{
	// Loads the cache class
	VBACTIVITY_CACHE::init($vbulletin, $specialtemplates);
}

foreach (VBACTIVITY::$cache['type'] as $typeid => $type)
{
	if (!file_exists(DIR . $type['filename']))
	{
		// Probably Pro onry
		VBACTIVITY::$cache['type'][$typeid]['settings'] = VBACTIVITY::$cache['type'][$typeid]['active'] = 0;
	}
}

if (!$vbulletin->bf_ugp_dbtech_vbactivitypermissions['isexcluded'])
{
	// For some reason.
	$vbulletin->bf_ugp_dbtech_vbactivitypermissions['isexcluded'] = 16;
}

// Initialise forumon
VBACTIVITY::init($vbulletin);

if (VBACTIVITY::$permissions['canview'])
{
	$show['vbactivity'] = $vbulletin->options['dbtech_vbactivity_navbar'];
	$show['vbactivity_ispro'] = VBACTIVITY::$isPro;	
	if ($vbulletin->options['dbtech_vbactivity_integration'] & 1)
	{
		$show['vbactivity_ql'] = true;
	}
	if ($vbulletin->options['dbtech_vbactivity_integration'] & 2)
	{
		$show['vbactivity_com'] = true;
	}
}

// Show branding or not
$show['vbactivity_branding'] = $vbulletin->options['dbtech_vbactivity_branding_free'] != base64_decode('dmJ3YXJlei5uZXQ=');
$show['dbtech_vbactivity_producttype'] = (VBACTIVITY::$isPro ? ' (Pro)' : ' (Lite)');

if (THIS_SCRIPT == 'vbactivity' AND $show['vbactivity_branding'] AND !$show['_dbtech_branding_override'])
{
	$brandingVariables = array(
		'flavour' 			=> 'Activity Tracking provided by ',
		'productid' 		=> 3,
		'utm_source' 		=> str_replace('www.', '', htmlspecialchars_uni($_SERVER['HTTP_HOST'])),		
		'utm_content' 		=> (VBACTIVITY::$isPro ? 'Pro' : 'Lite'),
		'referrerid' 		=> $vbulletin->options['dbtech_vbactivity_referral'],
		'title' 			=> 'vBActivity & Awards',
		'displayversion' 	=> $vbulletin->options['dbtech_vbactivity_displayversion'],
		'version' 			=> VBACTIVITY::$version,
		'producttype' 		=> $show['dbtech_vbactivity_producttype'],
		'showhivel' 		=> (!VBACTIVITY::$isPro AND !$vbulletin->options['dbtech_vbactivity_nohivel'])
	);

	$str = $brandingVariables['flavour'] . '
		<a rel="nofollow" href="http://www.dragonbyte-tech.com/vbecommerce.php' . ($brandingVariables['productid'] ? '?productid=' . $brandingVariables['productid'] . '&do=product&' : '?') . 'utm_source=' . $brandingVariables['utm_source'] . '&utm_campaign=Footer%2BLinks&utm_medium=' . urlencode(str_replace(' ', '+', $brandingVariables['title'])) . '&utm_content=' . $brandingVariables['utm_content'] . ($brandingVariables['referrerid'] ? '&referrerid=' . $brandingVariables['referrerid'] : '') . '" target="_blank">' . $brandingVariables['title'] . ($brandingVariables['displayversion'] ? ' v' . $brandingVariables['version'] : '') . $brandingVariables['producttype'] . '</a> - 
		<a rel="nofollow" href="http://www.dragonbyte-tech.com/?utm_source=' . $brandingVariables['utm_source'] . '&utm_campaign=Footer%2BLinks&utm_medium=' . urlencode(str_replace(' ', '+', $brandingVariables['title'])) . '&utm_content=' . $brandingVariables['utm_content'] . ($brandingVariables['referrerid'] ? '&referrerid=' . $brandingVariables['referrerid'] : '') . '" target="_blank">vBulletin Mods &amp; Addons</a> Copyright &copy; ' . date('Y') . ' DragonByte Technologies Ltd.' . 
		($brandingVariables['showhivel'] ? ' Runs best on <a rel="nofollow" href="http://www.hivelocity.net/?utm_source=Iain%2BKidd&utm_medium=back%2Blink&utm_term=Dedicated%2BServer%2BSponsor&utm_campaign=Back%2BLinks%2Bfrom%2BIain%2BKidd" target="_blank">HiVelocity Hosting</a>.' : '');
	$vbulletin->options['copyrighttext'] = (trim($vbulletin->options['copyrighttext']) != '' ? $str . '<br />' . $vbulletin->options['copyrighttext'] : $str);
}



if (VB_AREA != 'Forum')
{
	// We need this
	require_once(DIR . '/dbtech/vbactivity/hooks/global_setup_complete.php');
}
?>