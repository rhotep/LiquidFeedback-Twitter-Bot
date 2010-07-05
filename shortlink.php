<?php

require_once "config.php";
require_once "tools.php";


/**
 * Shorens the provided url with bit.ly
 * @params:
 *		$url	:string, the url to be shortened
 * @returns the shortened url or false
 */  


function shortlink($url){ //Url verkürzen
	$ch = curl_init('http://api.bit.ly/v3/shorten?login='.BL_LOGIN.'&apiKey='.BL_API_KEY.'&uri='.urlencode($url).'&format=json');
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	$response=curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	$response=my_json_decode($response);
	if ($httpcode != 200) {
		log_this("error: tried to shorten '$url'", $reponse);
		return(false);
	}else{
		log_this("success: shortened '$url'", $reponse);
		return(stripslashes($response['data']['url']));
	}
}
?>
