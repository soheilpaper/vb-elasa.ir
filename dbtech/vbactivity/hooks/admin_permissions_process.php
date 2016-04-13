<?php
$vbulletin->input->clean_gpc('p', 'dbtech_vbactivityadminperms', TYPE_ARRAY_INT);
foreach ((array)$vbulletin->GPC['dbtech_vbactivityadminperms'] AS $field => $value)
{
	$admindm->set_bitfield('dbtech_vbactivityadminperms', $field, $value);
}
?>