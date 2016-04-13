<?php
switch ($filename)
{
	case 'livewall.php':
		if ($values['do'] == 'main' OR !$values['do'])
		{
			$userinfo['activity'] = 'dbtech_livewall_';
		}
		break;
}
?>