<?php
if ($threadedmode != 0)
{
	// $cache_postids
	$post_ids = explode(',', $cache_postids);
}
else
{
	if (intval($vbulletin->versionnumber) == 3)
	{
		// $cache_postids
		$post_ids = explode(',', $ids);
	}
	else
	{
		// $cache_postids
		$post_ids = $ids;
	}
}

$fetchIds = array(
	'threads' => array($threadinfo['threadid']),
	'posts' => $post_ids
);

LIVEWALL::$allIds = LIVEWALL::fetchCommentDataThreadbit($fetchIds);

// Hacks
//$data['posts'][$threadinfo['firstpostid']] = $data['threads'][$threadinfo['threadid']];
?>