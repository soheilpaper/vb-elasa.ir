<?php
// Delete all points from this user
$this->dbobject->query_write("
	DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_pointslog
	WHERE userid = " . $this->existing['userid'] . "
");
$this->dbobject->query_write("
	DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_points
	WHERE userid = " . $this->existing['userid'] . "
");
$this->dbobject->query_write("
	DELETE FROM " . TABLE_PREFIX . "dbtech_vbactivity_rewards
	WHERE userid = " . $this->existing['userid'] . "
");
?>