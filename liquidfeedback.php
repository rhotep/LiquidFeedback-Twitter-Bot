<?php

require_once "tools.php";

/** LF-API documentation http://www.public-software-group.org/liquid_feedback_frontend_api

/**
 * Queries the LiquidFeeback instance provided by $lf with $query
 * @params:
 * 		$lf['base_dir']		:the base of the LiquidFeedback instance, e.g. 'lqpp.de/be'
 *		$lf['api_key']		:your LiquidFeeback API key
 *		$query				:the query to sent, e.g. 'min_id=0' to get all initiatives
 * @returns an array of initiatives
 * 
 * As of now the LF-API throws an error when using the state selector.
 */  


function lf_query($lf, $query){
	$query=$lf['base_dir'].'/api/initiative.html?key='.$lf['api_key'].'&api_engine=json&'.$query;
	$ch = curl_init($query);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	$response=curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);	
	if ($httpcode != 200) {
		log_this("error: tried to fetch \"$query\" from ".$lf['base_dir'], $response);
		return(false);
	}else{
		log_this("success: fetched \"$query\" from ".$lf['base_dir'], $response);
		return(my_json_decode($response));
	}

}

/**
 * Get the link to an issue
 * @params:
 * 		$lf					:array, see above for details
 *		$initative			:array, query_lf returns an array of initiatives, uses one of them here.
 * @returns a string containing a link to the issue the initiative is part of, not the to the initiative itself!
 */  

function get_issue_link($lf, $initiative){
	return($lf['base_dir']."/issue/show/".$initiative['issue_id'].".html");
}


?>
