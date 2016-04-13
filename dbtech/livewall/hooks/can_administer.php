<?php
foreach($do AS $field)
{
    if ($admin['dbtech_livewalladminperms']  & $vbulletin->bf_misc_dbtech_livewalladminperms["$field"])
    {
        $return_value = true;
    }
} 
?>