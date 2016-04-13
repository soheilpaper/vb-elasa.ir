<?php
$vbulletin->input->clean_gpc('p', 'dbtech_livewalladminperms', TYPE_ARRAY_INT);
foreach ((array)$vbulletin->GPC['dbtech_livewalladminperms'] AS $field => $value)
{
	$admindm->set_bitfield('dbtech_livewalladminperms', $field, $value);
}
?>