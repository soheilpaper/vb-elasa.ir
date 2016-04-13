<?php
if ($this->registry->userinfo['userid'] AND !$this->condition)
{
	// Set forum ID
	$forumid = (!$this->fetch_field('postid') ? 0 : 
		VBACTIVITY::$db->fetchOne('
			SELECT thread.forumid
			FROM $post AS post
			LEFT JOIN $thread AS thread ON(thread.threadid = post.threadid)
			WHERE post.postid = ?
		', array(
			$this->fetch_field('postid')
		))
	);
	
	VBACTIVITY::insert_points('infractiongiven', 	$this->fetch_field('infractionid'), $this->fetch_field('whoadded'),	$this->fetch_field('points'), TIMENOW, $forumid);
	VBACTIVITY::insert_points('infractionreceived', $this->fetch_field('infractionid'), $this->fetch_field('userid'),	$this->fetch_field('points'), TIMENOW, $forumid);
	
	$whoadded = array('userid' => $this->fetch_field('whoadded'));
	$currentuser = array('userid' => $this->fetch_field('userid'));
	
	$vbactivity_typenames = array(
		'infractiongiven',
		'infractionreceived',
	);
	$vbactivity_loc = 'infraction';
	
	/*DBTECH_PRO_START*/
	// Check promotion
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $whoadded);
	VBACTIVITY::check_feature_by_typenames('promotion', $vbactivity_typenames, $currentuser);
	/*DBTECH_PRO_END*/
	
	// Check achievements
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $whoadded);	
	VBACTIVITY::check_feature_by_typenames('achievement', $vbactivity_typenames, $currentuser);
}
?>