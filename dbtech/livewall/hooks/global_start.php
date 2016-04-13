<?php
// Fetch required classes
require_once(DIR . '/dbtech/livewall/includes/class_core.php');
require_once(DIR . '/dbtech/livewall/includes/class_cache.php');
if (intval($vbulletin->versionnumber) == 3 AND !class_exists('vB_Template'))
{
	// We need the template class
	require_once(DIR . '/dbtech/livewall/includes/class_template.php');
}

if (is_object($this))
{
	// Loads the cache class
	LIVEWALL_CACHE::init($vbulletin, $this->datastore_entries);
}
else
{
	// Loads the cache class
	LIVEWALL_CACHE::init($vbulletin, $specialtemplates);
}

// Initialise forumon
LIVEWALL::init($vbulletin);

if (LIVEWALL::$permissions['canview'])
{
	$show['livewall'] = $vbulletin->options['dbtech_livewall_navbar'];
	$show['livewall_ispro'] = LIVEWALL::$isPro;
	$show['livewall_recententries'] = (LIVEWALL::$isPro AND $vbulletin->options['dbtech_livewall_recent_entries']);
	$show['livewall_favourites'] = (LIVEWALL::$isPro AND $vbulletin->options['dbtech_livewall_recent_entries'] AND $vbulletin->userinfo['userid']);
	if ($vbulletin->options['dbtech_livewall_integration'] & 1)
	{
		$show['livewall_ql'] = true;
	}
	if ($vbulletin->options['dbtech_livewall_integration'] & 2)
	{
		$show['livewall_com'] = true;
	}
}

foreach ((array)LIVEWALL::$cache['contenttype'] as $contenttypeid => $contenttype)
{
	// Set active flag off for the ones that don't have existing files
	LIVEWALL::$cache['contenttype'][$contenttypeid]['enabled'] = file_exists(DIR . '/' . $contenttype['filename']);
}