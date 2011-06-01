<?php

	include("include/init.php");

	loadlib("flickr");
	loadlib("flickr_users");
	loadlib("random");

	$frob = get_str("frob");
	$extra = get_str("extra");

	if (! $frob){
		$GLOBALS['error']['missing_frob'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback.txt");
		exit();
	}

	if ($extra){
		$_extra = urldecode($extra);
		parse_str($_extra, $extra);
	}

	$args = array(
		"frob" => $frob,
	);

	$more = array(
		'sign' => 1,
	);

	$rsp = flickr_api_call("flickr.auth.getToken", $args, $more);

	if (! $rsp['ok']){
		$GLOBALS['error']['missing_token'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback.txt");
		exit();
	}

	$auth = $rsp['rsp']['auth'];

	$nsid = $auth['user']['nsid'];
	$username = $auth['user']['username'];
	$token = $auth['token']['_content'];

	$flickr_user = flickr_users_get_by_nsid($nsid);

	if ($user_id = $flickr_user['user_id']){
		$user = users_get_by_id($user_id);
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
			$GLOBALS['smarty']->display("page_auth_callback.txt");
			exit();
		}

		$flickr_user = flickr_users_create_user(array(
			'user_id' => $user['id'],
			'nsid' => $nsid,
			'auth_token' => $token,
		));

		if (! $flickr_user){
			$GLOBALS['error']['dberr_flickruser'] = 1;
			$GLOBALS['smarty']->display("page_auth_callback.txt");
			exit();
		}
	}

	$redir = (isset($extra['redir'])) ? $extra['redir'] : '';

	login_do_login($user, $redir);
	exit();

?>
