<?php
// Delete all ignored users from this user
$this->dbobject->query_write("
	DELETE FROM " . TABLE_PREFIX . "dbtech_livewall_settings
	WHERE userid = " . $this->existing['userid']
);
?>