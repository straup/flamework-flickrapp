<?php

	include("include/init.php");
	loadlib("oauth");

	$redir = (get_str('redir')) ? get_str('redir') : '/';

	if ($GLOBALS['cfg']['user']['id']){
		header("location: {$redir}");
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

	$keys = array(
		'oauth_key' => $GLOBALS['cfg']['flickr_oauth_key'],
		'oauth_secret' => $GLOBALS['cfg']['flickr_oauth_secret'],
	);

	$more = array(
		'oauth_callback' => $GLOBALS['cfg']['abs_root_url'] . 'oauth/';
	);

	$ok = oauth_get_auth_token($keys, 'http://www.flickr.com/services/oauth/request_token/', $more);

	if (! $ok){
		$GLOBALS['smarty']->display("page_signin_oauth_error.txt");
		exit();
	}

	#

	$extra = array();

	if ($redir = get_str('redir')){
		$extra[] = "redir=" . urlencode($redir);
	}

	$more = array(
		'perms' => 'read',
	);

	if (count($extra)){
		$more['extra'] = implode("&", $extra);
	}

	# This is kind of dirty and maybe crazy but it also works
	# so it's here mostly just as a reference. It may change.

	$request = implode(":", array(
		$keys['request_key'],
		$keys['request_secret'],
	));

	$request = crypto_encrypt($request, $GLOBALS['cfg']['crypto_oauth_cookie_secret']);

	#

	$url = oauth_get_auth_url($keys, 'http://www.flickr.com/services/oauth/authorize/', $more);

	#

	login_set_cookie('o', $request);
	header("location: {$url}");

	exit();
?>
