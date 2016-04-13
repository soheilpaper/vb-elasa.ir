<?php
if (!$this->condition)
{
	// New user
	$this->dbobject->query_write("
		INSERT IGNORE INTO " . TABLE_PREFIX . "dbtech_livewall_settings
			(userid)
		VALUES (
			$userid
		)
	");
}
?>