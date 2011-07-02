<?php

	include("include/init.php");
	loadlib("flickr_oauth");

	$redir = (get_str('redir')) ? get_str('redir') : '/';

	# Some basic sanity checking like are you already logged in?

	if ($GLOBALS['cfg']['user']['id']){
		header("location: {$redir}");
		exit();
	}

	if (! $GLOBALS['cfg']['enable_feature_signin']){
		$GLOBALS['smarty']->display("page_signin_disabled.txt");
		exit();
	}

	# Because we need to have the request token/secret on both
	# sides of the auth URL dance we're going to encrypt them
	# and pass them around as a cookie. Whether or not this is
	# insane is an open question but it works so, for now, that's
	# what we're doing. The whole idea rests on the idea that
	# we've got an encrypted cookie (whose contents are only
	# meaningful for the duration of the auth dance session) so
	# make sure that there's an actual signing secret. If not,
	# go home.

	if (! $GLOBALS['cfg']['crypto_oauth_cookie_secret']){
		$GLOBALS['error']['oauth_missing_secret'] = 1;
		$GLOBALS['smarty']->display("page_signin_oauth.txt");
		exit();
	}

	# Use the application key/secret pair to create a signed
	# request for a temporary set of user-specific key/secret
	# credentials. These will be paired with the application's
	# auth-y bits to sign subsequent requests.

	$rsp = flickr_oauth_get_request_token();

	if (! $rsp['ok']){
		$GLOBALS['error']['oauth_request_token'] = 1;
		$GLOBALS['smarty']->display("page_signin_oauth.txt");
		exit();
	}

	$user_keys = $rsp['data'];

	# See above inre: request tokens and bad craziness

	$request = implode(":", array(
		$user_keys['oauth_token'],
		$user_keys['oauth_token_secret'],
	));

	$request = crypto_encrypt($request, $GLOBALS['cfg']['crypto_oauth_cookie_secret']);

	# Now we build the actual auth URL request – this is the
	# part where we redirect the user to Flickr to ask for
	# permission to exchange the request token/secret for a
	# permanent set of credentials to call the API with. Here
	# we're padding along the desired access levels to the
	# Flickr API along with any extra bits that we'll need to
	# finish logging the user in when they are redirected back
	# to this site.

	$perms = $GLOBALS['cfg']['flickr_api_perms'];

	$args = array(
		'perms' => $perms,
	);

	$extra = array();

	if ($redir = get_str('redir')){
		$extra[] = "redir=" . urlencode($redir);
	}

	if (count($extra)){
		$args['extra'] = implode("&", $extra);
	}

	$url = flickr_oauth_get_auth_url($args, $user_keys);

	# Okay, now go! (Note the crazy-in-the-head cookie setting.)

	login_set_cookie('o', $request);
	header("location: {$url}");

	exit();
?>
