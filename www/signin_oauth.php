<?php

	include("include/init.php");
	loadlib("flickr_oauth");

	$redir = (get_str('redir')) ? get_str('redir') : '/';

	if ($GLOBALS['cfg']['user']['id']){
#		header("location: {$redir}");
#		exit();
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

	$rsp = flickr_oauth_get_request_token();

	if (! $rsp['ok']){
		$GLOBALS['error']['oauth_request_token'] = 1;
		$GLOBALS['smarty']->display("page_signin_oauth.txt");
		exit();
	}

	#

	$user_keys = $rsp['data'];

	# This is kind of dirty and maybe crazy but it also works
	# so it's here mostly just as a reference. It may change.

	$request = implode(":", array(
		$user_keys['oauth_token'],
		$user_keys['oauth_token_secret'],
	));

	$request = crypto_encrypt($request, $GLOBALS['cfg']['crypto_oauth_cookie_secret']);

	#

	$args = array(
		'perms' => 'read',
	);

	$extra = array();

	if ($redir = get_str('redir')){
		$extra[] = "redir=" . urlencode($redir);
	}

	if (count($extra)){
		$args['extra'] = implode("&", $extra);
	}

	#

	$url = flickr_oauth_get_auth_url($args, $user_keys);

	#

	login_set_cookie('o', $request);
	header("location: {$url}");

	exit();
?>
