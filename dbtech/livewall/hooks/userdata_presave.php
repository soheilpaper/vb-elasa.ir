<?php
if ($this->condition == "userid = " . $this->fetch_field('userid') AND sizeof($this->user) == 2 AND isset($this->user['avatarrevision']))
{
	$this->existing['userid'] = $this->fetch_field('userid');
	$this->existing['avatarrevision'] = $this->user['avatarrevision'] - 1;
}
?>