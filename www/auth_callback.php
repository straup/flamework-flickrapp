<?php

	include("include/init.php");
	loadlib("flickr");
	loadlib("flickr_users");
	loadlib("random");

	$frob = get_str("frob");

	if (! $frob){
		$GLOBALS['error']['missing_frob'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback.txt");
		exit();
	}

	$args = array(
		"frob" => $frob,
	);

	# ensure this is signed...

	$rsp = flickr_api_call("flickr.auth.getToken", $args);

	if (! $rsp['ok']){
		$GLOBALS['error']['missing_token'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback.txt");
		exit();
	}

	$rsp = $rsp['rsp'];

	# are these actually right...
	$nsid = $rsp['id'];
	$token = $rsp['token']['_content'];

	$flickr_user = flickr_users_get_by_nsid($nsid);

	if ($user_id = $flickr_user['user_id']){
		$user = users_get_by_id($user_id);
	}

	else {

		$username = "fix me";
		$password = random_string(32);

		$user = users_create_user($array(
			"username" => $username,
			"email" => "{$username}@donotsend-flickr.com",
			"password" => $password,
		));

		$flickr_user = flickr_users_create_user(array(
			'user_id' => $user['id'],
			'nsid' => $nsid,
			'auth_token' => $token,
		));
	}

	# check for redir here...

	login_do_login($user);
	exit();
?>
