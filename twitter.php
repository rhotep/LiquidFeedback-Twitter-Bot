<?php

require_once "twitteroauth/twitteroauth.php";
require_once "tools.php";

/**
 * Sends $tweet through the twitter account provided by $twitter
 * You have to register an application on http://dev.twitter.com/apps to get comsumer and access keys.
 * @params:
 *		$twitter		:array
 *	 		$twitter['consumer_key']	:string
 *			$twitter['consumer_secret']	:string
 *	 		$twitter['access_key']		:string
 *			$twitter['access_secret']	:string
 *		$tweet			:string, the message to be posted
 *@returns true in success, false otherwise
 */  


function twitter($twitter, $tweet){
	$connection = new TwitterOAuth ($twitter['consumer_key'] ,$twitter['consumer_secret'] , $twitter['access_key'] , $twitter['access_secret']);
	$connection->decode_json =false;
	$response = $connection->post('statuses/update', array('status' => $tweet));

	$http_info = $connection->http_info;

	if ($http_info['http_code'] != 200) {
		log_this("error: tried to twitter \"$tweet\" on @".$twitter['name'], $response);
		return(false);
	}else{
		log_this("success: twittered \"$tweet\" on @".$twitter['name'], $response);
		return(true);
	}
}


/**
 * Gets the current status message of the twitter account provided by $twitter
 * @params:
 *		$twitter		:array
 *	 		$twitter['consumer_key']	:string
 *			$twitter['consumer_secret']	:string
 *	 		$twitter['access_key']		:string
 *			$twitter['access_secret']	:string
 *@returns a string containing the status on success or false otherwise
 */  

function get_status($twitter){
	$connection = new TwitterOAuth ($twitter['consumer_key'] ,$twitter['consumer_secret'] , $twitter['access_key'] , $twitter['access_secret']);
	$response = $connection->get("users/show", array("id" => $twitter['name']));
	$status=$response->status->text;

	$http_info = $connection->http_info;

	if ($http_info['http_code'] != 200) {
		log_this("......... error: tried to fetch status message from @".$twitter['name'], json_encode($response));
		return(false);
	}else{
		log_this("......... success: received status message from @".$twitter['name'], json_encode($response));
		return($status);
	}

}


/**
 * Gets the user iamge of the twitter account provided by $twitter
 * @params:
 *		$twitter		:array
 *	 		$twitter['consumer_key']	:string
 *			$twitter['consumer_secret']	:string
 *	 		$twitter['access_key']		:string
 *			$twitter['access_secret']	:string
 *@returns the image url on success, false otherwise
 */  

function get_pic($twitter){
	$connection = new TwitterOAuth ($twitter['consumer_key'] ,$twitter['consumer_secret'] , $twitter['access_key'] , $twitter['access_secret']);
	$response = $connection->get("users/show", array("id" => $twitter['name']));
	$url=$response->profile_image_url;

	$http_info = $connection->http_info;

	if ($http_info['http_code'] != 200) {
		log_this("......... error: tried to fetch user image from @".$twitter['name'], json_encode($response));
		return(false);
	}else{
		log_this("......... success: received user image from @".$twitter['name'], json_encode($response));
		return($url);
	}

}

?>
