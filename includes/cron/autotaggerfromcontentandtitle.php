<?php
/*======================================================================*\
|| #################################################################### ||
|| # Automatic Thread Tagger                                          # ||
|| # ---------------------------------------------------------------- # ||
|| # Originally created by NLP-er (1.0.0)                             # ||
|| # Copyright 2009 Michal Podbielski. All Rights Reserved.           # ||
|| #################################################################### ||
\*======================================================================*/ 

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
if (!is_object($vbulletin->db))
{
    exit;
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################


if ($vbulletin->options['autotaggerfromcontentandtitle_cron_userid']) {
  require_once(DIR . '/includes/functions_autotaggerfromcontentandtitle.php');
  $time = time();
  $result = addTagsToOld($vbulletin->options['autotaggerfromcontentandtitle_cron_userid'], ($time - 24*60*60 - 5*60));
  log_cron_action(' Working time: ' . (time() - $time) . ' seconds. In this time '.$result[0].' new tags was considered, and '.$result[1]
    .' tags was added to '.$result[2].' threads.', $nextitem);  
}

?>