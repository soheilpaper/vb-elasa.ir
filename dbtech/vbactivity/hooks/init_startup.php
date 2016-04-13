<?php
if (!defined('IN_VBACTIVITY'))
{
	// Fetch the extracache
	require(DIR . '/dbtech/vbactivity/includes/specialtemplates.php');
	$extrafetch = array();
	
	foreach ($extracache as $varname)
	{
		// datastore_fetch uses a different syntax
		$extrafetch[] = "'$varname'";
	}
	
	// Now merge the prepared entries
	$datastore_fetch = array_merge($datastore_fetch, $extrafetch);
	
	if (isset($this) AND is_object($this))
	{
		// Forum inits within a class
		$this->datastore_entries = array_merge((array)$this->datastore_entries, $extracache);
	}
	else
	{
		// AdminCP / ModCP inits normally
		$specialtemplates = array_merge((array)$specialtemplates, $extracache);
	}
}
?>