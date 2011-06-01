<?php

	include("include/init.php");
	loadlib("flickr");

	$redir = (get_str('redir')) ? get_str('redir') : '/';

	if ($GLOBALS['cfg']['user']['id']){
		header("location: {$redir}");
		exit();
	}

	if (! $GLOBALS['cfg']['enable_feature_signin']){
		$GLOBALS['smarty']->display("page_signin_disabled.txt");
		exit;
	}

	$extra = array();

	if ($redir = get_str('redir')){
		$extra['redir'] = $redir;
	}

	$url = flickr_auth_url("read", $extra);

	header("location: {$url}");
	exit();
?>
