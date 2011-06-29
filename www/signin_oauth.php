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

	if (! $GLOBALS['cfg']['crypto_oauth_cookie_secret']){
		$GLOBALS['error']['oauth_missing_secret'] = 1;
		$GLOBALS['smarty']->display("page_signin_oauth.txt");
		exit();
	}

	#

	$keys = array(
		'oauth_key' => $GLOBALS['cfg']['flickr_oauth_key'],
		'oauth_secret' => $GLOBALS['cfg']['flickr_oauth_secret'],
	);

	$more = array(
		# 'oauth_callback' => $GLOBALS['cfg']['abs_root_url'] . 'auth/',
		'oauth_callback' => $GLOBALS['cfg']['abs_root_url'] . 'auth_callback_oauth.php',
	);

	$rsp = oauth_get_auth_token($keys, 'http://www.flickr.com/services/oauth/request_token/', $more);

	if (! $rsp['ok']){
		$GLOBALS['error']['oauth_request_token'] = 1;
		$GLOBALS['smarty']->display("page_signin_oauth.txt");
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
		$rsp['data']['oauth_token'],
		$rsp['data']['oauth_token_secret'],
	));

	$request = crypto_encrypt($request, $GLOBALS['cfg']['crypto_oauth_cookie_secret']);

	#

	$url = oauth_get_auth_url($keys, 'http://www.flickr.com/services/oauth/authorize/', $more);

	#

	login_set_cookie('o', $request);
	header("location: {$url}");

	exit();
?>
