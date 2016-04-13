<?php
$featureinfo['title_translated'] = 
	'<a href="vbactivity.php?' . $vbulletin->session->vars['sessionurl'] . 'do=all' . ($feature == 'medal' ? 'award' : $feature) . 's&amp;' . $feature . 'id=' . $featureinfo["{$feature}id"] . '">' . 
		$featureinfo['title_translated'] . 
	'</a>'
;
?>