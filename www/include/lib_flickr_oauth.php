<?php

	# THIS IS STILL A WORK IN MOTION
	# (20110630/straup)

	loadlib("oauth");
	loadlib("http");

	$GLOBALS['cfg']['flickr_api_endpoint'] = 'http://api.flickr.com/services/rest';

	#################################################################

	# token dance nonsense goes here...

	#################################################################

	function flickr_oauth_api_call($method, $args, $user_keys=array()){

		$keys = array(
			'oauth_key' => $GLOBALS['cfg']['flickr_oauth_key'],
			'oauth_secret' => $GLOBALS['cfg']['flickr_oauth_secret'],
		);

		# what oh what to call these stupid things....

		if (count($user_keys)){
			$keys['user_key'] = $user_keys['oauth_token'];
			$keys['user_secret'] = $user_keys['oauth_secret'];
		}

		$args['method'] = $method;
		$args['format'] = 'json';
		$args['nojsoncallback'] = 1;

		# Just keep things simple and assume we're always doing POSTs

		$url = oauth_sign_get($keys, $GLOBALS['cfg']['flickr_api_endpoint'], $args, 'POST');
		list($url, $postdata) = explode('?', $url, 2);

		$rsp = http_post($url, $postdata);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], 'as a hash');

		if (! $json){
			return array( 'ok' => 0, 'error' => 'failed to parse response' );
		}

		if ($json['stat'] != 'ok'){
			return array( 'ok' => 0, 'error' => $json['message']);
		}

		unset($json['stat']);
		return array( 'ok' => 1, 'data' => $json );
	}

	#################################################################
?>
