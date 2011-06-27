<?php

	include("include/init.php");
	loadlib("oauth");
	loadlib("flickr_users");

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
		$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
		exit();
	}

	#

	$oauth_cookie = login_get_cookie('o');
	login_unset_cookie('o');

	if (! $oauth_cookie){
		$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
		exit();
	}

	$request = crypto_decrypt($oauth_cookie, $GLOBALS['cfg']['crypto_cookie_secret']);
	$request = explode(":", $request, 2);

	# TODO:
	# make sure that $_GET contains required parameters

	# exchange the frob for a token

	$keys = array(
		'oauth_key' => $GLOBALS['cfg']['flickr_oauth_key'],
		'oauth_secret' => $GLOBALS['cfg']['flickr_oauth_secret'],
		'request_key' => $request[0],
		'request_secret' => $request[1],
	);

	$ok = oauth_get_access_token($keys, 'http://www.flickr.com/services/oauth/access_token/', $_GET);

	if (! $ok){
		$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
		exit();
	}

	# TODO:
	# get user data here (keys, username, nsid)
	# will require poking lib_oauth

	$flickr_user = flickr_users_get_by_nsid($nsid);

	if ($user_id = $flickr_user['user_id']){
		$user = users_get_by_id($user_id);
	}

	else if (! $GLOBALS['cfg']['enable_feature_signup']){
		$GLOBALS['smarty']->display("page_signup_disabled.txt");
		exit();
	}

	else {

		$password = random_string(32);

		$user = users_create_user(array(
			"username" => $username,
			"email" => "{$username}@donotsend-flickr.com",
			"password" => $password,
		));

		if (! $user){
			$GLOBALS['error']['dberr_user'] = 1;
			$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
			exit();
		}

		$flickr_user = flickr_users_create_user(array(
			'user_id' => $user['id'],
			'nsid' => $nsid,
			'oauth_token' => $oauth_token,
			'oauth_secret' => $oauth_secret,
		));

		if (! $flickr_user){
			$GLOBALS['error']['dberr_flickruser'] = 1;
			$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
			exit();
		}
	}

	$redir = (isset($extra['redir'])) ? $extra['redir'] : '';

	login_do_login($user, $redir);
	exit();

?>
