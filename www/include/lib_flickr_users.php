<?php

	#################################################################

	function flickr_users_get_by_nsid($nsid){

		$enc_nsid = AddSlashes($nsid);

		$sql = "SELECT * FROM FlickrUsers WHERE nsid='{$enc_nsid}'";
		$rsp = db_fetch($sql);
		return db_single($rsp);
	}

	#################################################################
?>
