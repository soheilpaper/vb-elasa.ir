<?php
if (class_exists('LIVEWALL'))
{
	$SQL = array();
	foreach ((array)LIVEWALL::$cache['contenttype'] as $contenttypeid => $contenttype)
	{
		if (!$contenttype['active'])
		{
			// Inactive contenttype
			continue;
		}
		
		if (!$contenttype['enabled'])
		{
			// Pro only and we're in Lite
			continue;
		}
				
		// Setup the fields and tables
		$SQL[] = 'dbtech_livewall_settings.' . $contenttypeid . '_display';
		$SQL[] = 'dbtech_livewall_settings.' . $contenttypeid . '_privacy';
	}
	
	if (count($SQL))
	{
		$hook_query_fields .= ' , ' . implode(' , ', $SQL);
		$hook_query_joins .= ' LEFT JOIN ' . TABLE_PREFIX . 'dbtech_livewall_settings AS dbtech_livewall_settings ON(dbtech_livewall_settings.userid = user.userid)';
	}
}
else
{
	$hook_query_fields .= ' , dbtech_livewall_settings.*, user.userid';
	$hook_query_joins .= ' LEFT JOIN ' . TABLE_PREFIX . 'dbtech_livewall_settings AS dbtech_livewall_settings ON(dbtech_livewall_settings.userid = user.userid)';
}
?>