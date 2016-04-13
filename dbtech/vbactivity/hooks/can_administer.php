<?php
foreach($do AS $field)
{
    if ($admin['dbtech_vbactivityadminperms']  & $vbulletin->bf_misc_dbtech_vbactivityadminperms["$field"])
    {
        $return_value = true;
    }
} 
?>