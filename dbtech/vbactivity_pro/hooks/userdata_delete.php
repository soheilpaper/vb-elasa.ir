<?php
// Delete all trophies from this user
$this->dbobject->query_write("
	DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_trophylog
	WHERE userid = " . $this->existing['userid'] . "
");
$this->dbobject->query_write("
	UPDATE " . TABLE_PREFIX . "dbtech_vbactivity_type
	SET userid = 0
	WHERE userid = " . $this->existing['userid'] . "
");

VBACTIVITY_CACHE::build('type');
?>