<?php

	include("include/init.php");

	loadlib("flickr_users");
	loadlib("flickr_oauth");
	loadlib("random");

	# Some basic sanity checking like are you already logged in?

	if ($GLOBALS['cfg']['user']['id']){
		header("location: {$GLOBALS['cfg']['abs_root_url']}");
		exit();
	}


	if (! $GLOBALS['cfg']['enable_feature_signin']){
		$GLOBALS['smarty']->display("page_signin_disabled.txt");
		exit();
	}

	# See the notes in signin_oauth.php about cookies and request
	# tokens.

	if (! $GLOBALS['cfg']['crypto_oauth_cookie_secret']){
		$GLOBALS['error']['oauth_missing_secret'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
		exit();
	}

	# Grab the cookie and blow it away. This makes things a little
	# bit of a nuisance if something goes wrong below because you'll
	# need to re-auth a user but there you go.

	$oauth_cookie = login_get_cookie('o');
	login_unset_cookie('o');

	if (! $oauth_cookie){
		$GLOBALS['error']['oauth_missing_cookie'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
		exit();
	}

	$request = crypto_decrypt($oauth_cookie, $GLOBALS['cfg']['crypto_oauth_cookie_secret']);
	$request = explode(":", $request, 2);

	# Make sure that we've got the minimum set of parameters
	# we expect Flickr to send back.

	$verifier = get_str('oauth_verifier');
	$token = get_str('oauth_token');

	if ((! $verifier) || (! $token)){
		$GLOBALS['error']['oauth_missing_args'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
		exit();
	}

	# Now we exchange the request token/secret for a more permanent set
	# of OAuth credentials. In plain old Flickr auth language this is
	# where we exchange the frob (the oauth_verifier) for an auth token.
	# The only difference is that we sign the request using both the app's
	# signing secret and the user's (temporary) request secret.

	$user_keys = array(
		'oauth_token' => $request[0],
		'oauth_secret' => $request[1],
	);

	$args = array(
		'oauth_verifier' => $verifier,
		'oauth_token' => $token,
	);

	$rsp = flickr_oauth_get_access_token($args, $user_keys);

	if (! $rsp['ok']){
		$GLOBALS['error']['oauth_access_token'] = 1;
		$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
		exit();
	}

	# Hey look! If we've gotten this far then that means we've been able
	# to use the Flickr API to validate the user and we've got an OAuth
	# key/secret pair.

	$data = $rsp['data'];

	$username = $data['username'];
	$nsid = $data['user_nsid'];

	# The first thing we do is check to see if we already have an account
	# matching that user's Flickr NSID.

	$flickr_user = flickr_users_get_by_nsid($nsid);

	if ($user_id = $flickr_user['user_id']){

		$user = users_get_by_id($user_id);

		# Even if we do then check to make sure that we've stored
		# their OAuth credentials.

		if ((! $flickr_user['oauth_token']) || ($flickr_user['oauth_token'] != $keys['user_key'])){

			$update = array(
				'oauth_token' => $keys['user_key'],
				'oauth_secret' => $keys['user_secret'],
			);

			$rsp = flickr_users_update_user($flickr_user, $update);

			if (! $rsp['ok']){
				$GLOBALS['error']['dberr_flickruser_update'] = 1;
				$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
				exit();
			}
		}
	}

	# If we don't ensure that new users are allowed to create
	# an account (locally).

	else if (! $GLOBALS['cfg']['enable_feature_signup']){
		$GLOBALS['smarty']->display("page_signup_disabled.txt");
		exit();
	}

	# Hello, new user! This part will create entries in two separate
	# databases: Users and FlickrUsers that are joined by the primary
	# key on the Users table.

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
			'oauth_token' => $keys['user_key'],
			'oauth_secret' => $keys['user_secret'],
		));

		if (! $flickr_user){
			$GLOBALS['error']['dberr_flickruser'] = 1;
			$GLOBALS['smarty']->display("page_auth_callback_oauth.txt");
			exit();
		}
	}

	# Okay, now finish logging the user in (setting cookies, etc.) and
	# redirecting them to some specific page if necessary.

	$redir = (isset($extra['redir'])) ? $extra['redir'] : '';

	login_do_login($user, $redir);
	exit();

?>
