<?php

	#################################################################

	function flickr_users_create_user($user){

		$hash = array();

		foreach ($user as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert('FlickrUsers', $hash);

		if (!$rsp['ok']){
			return null;
		}

		$cache_key = "flickr_user_{$user['nsid']}";
		cache_set($cache_key, $user, "cache locally");

		return $user;
	}

	#################################################################

	function flickr_users_delete_user(&$user){

		$enc_id = AddSlashes($user['id']);

		$sql = "DELETE FROM FlickrUsers WHERE user_id='{$enc_id}'";
		$rsp = db_write($sql);

		if ($rsp['ok']){
			$cache_key = "flickr_user_{$user['nsid']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	function flickr_users_get_by_nsid($nsid){

		$cache_key = "flickr_user_{$user['nsid']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$enc_nsid = AddSlashes($nsid);

		$sql = "SELECT * FROM FlickrUsers WHERE nsid='{$enc_nsid}'";
		$rsp = db_fetch($sql);
		$user = db_single($rsp);

		if ($user){
			cache_set($cache_key, $user, "cache locally");
		}

		return $user;
	}

	#################################################################
?>
