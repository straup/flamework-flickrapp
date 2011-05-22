<?php

	#
	# $Id$
	#

	loadlib("http");

	#################################################################

	function flickr_auth_url($perms, $extra=null){

		$args = array(
			'api_key' => $GLOBALS['cfg']['flickr_apikey'],
			'perms' => $perms,
		);

		if ($extra){

			$extra = http_build_query($extra);
			$args['extra'] = $extra;
		}

		$api_sig = _flickr_api_sign_args($args);
		$args['api_sig'] = $api_sig;

		$url = "http://flickr.com/services/auth/?" . http_build_query($args);
		return $url;
	}

	#################################################################

	function flickr_api_call($method, $args=array()){

		$args['api_key'] = $GLOBALS['cfg']['flickr_apikey'];

		$args['method'] = $method;
		$args['format'] = 'json';
		$args['nojsoncallback'] = 1;

		if (isset($args['auth_token'])){
			$api_sig = _flickr_api_sign_args($args);
			$args['api_sig'] = $api_sig;
		}

		$url = "http://api.flickr.com/services/rest";

		$rsp = http_post($url, $args);

		# $url = $url . "?" . http_build_query($args);
		# $rsp = http_get($url);

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
		return array( 'ok' => 1, 'rsp' => $json );
	}

	#################################################################

	function _flickr_api_sign_args($args){

		$parts = array(
			$GLOBALS['cfg']['flickr_apisecret']
		);

		$keys = array_keys($args);
		sort($keys);

		foreach ($keys as $k){
			$parts[] = $k . $args[$k];
		}

		$raw = implode("", $parts);
		return md5($raw);
	}

	#################################################################
?>
