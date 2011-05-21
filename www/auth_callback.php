<?php

	include("include/init.php");
	loadlib("flickr");

	$frob = get_str("frob");

	if (! $frob){
		$GLOBALS['error']['missing_frob'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback.txt");
		exit();
	}

	$args = array(
		"frob" => $frob,
	);

	$rsp = flickr_api_call("flickr.auth.getToken", $args);

	if (! $rsp['ok']){
		$GLOBALS['error']['missing_token'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback.txt");
		exit();
	}

	# uh... do login stuff here...
?>
