<?php

	include("include/init.php");
	loadlib("oauth");

	if ($GLOBALS['cfg']['user']['id']){
		header("location: {$GLOBALS['cfg']['abs_root_url']}");
		exit();
	}

	#

	if (! $GLOBALS['cfg']['enable_feature_signin']){
		$GLOBALS['smarty']->display("page_signin_disabled.txt");
		exit();
	}

	if (! $GLOBALS['cfg']['crypto_oauth_cookie_secret'])){
		$GLOBALS['smarty']->display("page_signin_oauth_error.txt");
		exit();
	}

	#

	$oauth_cookie = login_get_cookie('o');
	login_unset_cookie('o');

	if (! $oauth_cookie){
		$GLOBALS['smarty']->display("page_signin_oauth_error.txt");
		exit();
	}

	$request = crypto_decrypt($oauth_cookie, $GLOBALS['cfg']['crypto_cookie_secret']);
	$request = explode(":", $request, 2);

	#

	$keys = array(
		'oauth_key' => $GLOBALS['cfg']['flickr_oauth_key'],
		'oauth_secret' => $GLOBALS['cfg']['flickr_oauth_secret'],
		'request_key' => $request[0],
		'request_secret' => $request[1],
	);

	$ok = oauth_get_access_token($keys, 'http://www.flickr.com/services/oauth/access_token/', $_GET);

	if (! $ok){
		$GLOBALS['smarty']->display("page_signin_oauth_error.txt");
		exit();
	}

	# store/check tokens here

	# create new account, etc.

	# go go go!
?>
