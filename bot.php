<?php

require_once "config.php";
require_once "tools.php";
require_once "liquidfeedback.php";
require_once "shortlink.php";
require_once "twitter.php";


/**
 * Logs an event to make sure it's not triggered again
 * @params:
 *		$event						:array
 *	 		$event['issue_id']		:integer, the id of the issue to be logeed with the event
 *			$event['initiative_id']	:integer, the id of the initiative to be looged with the event
 *			$event['job']			:string, the job that handled the issue
 * @returns true on success, dies otherwise
 */  

function log_event($event){

	global $db_events_table;//the events table's name for each bot is defined in update_twitter_bot.php right before the bot is processed

	$time = time();
	if(!table_exists($db_events_table)){
		create_table($db_events_table, 'time INT, issue_id INT, initiative_id INT, job CHAR(80)');
	}
	$query="INSERT INTO $db_events_table (time, issue_id, initiative_id, job) VALUES($time, {$event['issue_id']} , {$event['initiative_id']} , '{$event['job']}')";

	$result = mysql_query($query);
	$error = mysql_errno();



	if($error != 0){
		log_this("error: tried to log event ({$event['issue_id']} , {$event['issue_id']}, '{$event['job']})", mysql_error().": \n".$query);
		die("Unable to log event. MySQL error: ".$error);
	}else{
		log_this("success: logged event ({$event['issue_id']} ,{$event['initiative_id']}, '{$event['job']})");
		return(true);
	}
}

/**
 * Get all logged after a given time
 * @params:
 *		$state		:string, the state of events your are looking for e.g. 'new' or 'voting'
 * @returns an array with the events whose job part is obe of $joblabels or all of them if $job_labels is empty
 */  

function get_logged_events($job_labels = '', $time = 0){

	$job_labels = (array) $job_labels;

	global $db_events_table; 				//the events table's name for each bot is defined in update_twitter_bot.php right before the bot is processed

	$out = array();

	foreach($job_labels as $job_label){
		$job_clause= $job_label!=''? "job = '$job_label'" : ''; 	
		$query="SELECT * FROM ".$db_events_table." WHERE time>=$time AND $job_clause ORDER BY time DESC";
		$result = mysql_query($query);
		$error = mysql_errno();
		if($error!=0){
			log_this("error: tried to get logged issues ($job_label, time > $time). Dont worry, maybe it's just that there has nothing been logged yet.", mysql_error());
		}else{
			log_this("success: retrieved logged issues ($job_label, time > $time)", "");
			while($row=mysql_fetch_assoc($result)){
				$out[]=$row;
			}
		}	
	}
	return($out);
}

function get_logged_issues($job_labels = '', $time = 0){

	$logged_events = get_logged_events($job_labels, $time);
	$out = array();

	foreach($logged_events as $event){
		$out[] = $event['issue_id'];
	}

	return($out);
}

function get_last_initiative($job_label, $default=0){

	global $db_events_table; 				//the events table's name for each bot is defined in update_twitter_bot.php right before the bot is processed

	$out = $default;

	$job_clause= $job_label!=''? "WHERE job = '$job_label'" : ''; 	
	$query="SELECT initiative_id FROM ".$db_events_table." $job_clause ORDER BY initiative_id DESC";
	$result = mysql_query($query);
	$error = mysql_errno();
	if($error!=0){
		log_this("error: tried to get last initiative for job '$job_label'. Dont worry, maybe it's just that there has nothing been logged yet.", mysql_error());
	}else{
		if($row=mysql_fetch_row($result)){
			$out=$row[0];
		}
		log_this("success: retrieved last initiative for job  '$job_label'.", $out);
	}	
	$out = $out < 0 ? $default: $out;
	return($out);
}

/**
 * Drops $db_events_table
 * @returns nothing
 */

function clear_logged_events(){

	global $db_events_table;

	$query="DROP TABLE $db_events_table";
	$result = mysql_query($query);
	log_this("cleared logged events", "dropped table '$db_events_table'");
}

/** 
 * Processes all jobs of a bot defined in config.php. 
 * @params
 *		$bot	:array, as defined in config.php
 * @returns nothing
 */


function process($bot){

	foreach($bot['jobs'] as $job_label => $job){
		$firstrun = !sizeof(get_logged_issues($job_label)) > 0;					//Anything logged yet for this job?

		echo "\nProcessing job '$job_label'\n";
		echo "------------------------------------------------";
		$starting_id = get_last_initiative($job_label, $bot['starting_id']);	//the bot's starting id is only used as fallback
		echo "[starting with initiative $starting_id]\n";

		if($firstrun){ 
			$event = array("issue_id" => -2, "initiative_id" => -2, "job" => $job_label);	
			log_event($event);													//initiatiate log table
			echo "First run for this job on this bot. No tweets will be sent.\n\n"; 
		}

		$query = $job['query']. "&min_id=". $starting_id;						//make sure to get only recent initiatives
		$result = lf_query($bot['lf'], $query);				//get all initiatives needed for the current job
		$job_labels = (array) $job['dont_retweet'];			//this prevents issues beeing tweeted for multiple jobs 
		$job_labels[] = $job_label;		
		$logged_issues = get_logged_issues($job_labels);	//events already twittered	
		$todo = 0;											//as of now there is no event to process
		$twittered = 0; 									//nothing has been twittered yet
		$new_event_found = false;							//nothing new has been detected yet - suprise	

		foreach($result as $initiative){			

			log_and_flush(); // Log and flush the output buffer :)
			
			if(in_array($initiative['issue_state'], (array) $job['states'])){	//process only those whose state is among those of interest
				$todo++;								//one initiative found, so one more to do :)
				$issue_id = $initiative['issue_id'];	//get the issue_id, that is NOT the initiative's id!
				$initiative_id = $initiative['id'];	
				$event = array("issue_id" => $issue_id, "initiative_id" => $initiative_id,  "job" => $job_label);	//current event

				if(!in_array($issue_id, $logged_issues)){	//dont tweet the same issue twice

					$new_event_found = true;
					echo "\n\tIssue {$initiative['issue_id']} has not yet been twittered for one of the following job(s): ".implode(', ',$job_labels).". ($initiative_id)\n";
					$url=get_issue_link($bot['lf'],$initiative);
					echo "\t\tURL: $url\n";
	
					if($firstrun){
						$shorturl = true;
					}else{
						$shorturl = shortlink($url);		//bit.ly
					}

					if($shorturl){					//the url actually got shortened

						echo "\t\tShortened URL: $shorturl\n";

						$tweet = sprintf($job['format'], $bot['hashtag'], $shorturl, $issue_id);	
						echo "\t\tSending tweet: '$tweet'\n";
						
						if($firstrun){				// Prevent the bot from tweeting on the first run, there'd be tons of old stuff otherwise
							$done = true;
							echo "\t\tNo tweets on first run.\n";
						}else{
							$done = twitter($bot['twitter'], $tweet);	//Here we go!
						}
					
						if($done){							
							log_event($event);				//keep this one in mind to not twitter it again in future runs for the same job
							$logged_events[] = $event; 		//keep this one in mind not to twitter it again this run
							$twittered++;					//one more has been twittered		
							$todo--;						//this one is done :)
						}else{
							echo "\t\tAborted: unable to send tweet.\n";
						}						
					}else{
						echo "\t\tAborted: unable to shorten url.\n";
					}
				}else{
					$todo--; //we dont have to deal with this one anymore
					echo "\n\tIssue {$initiative['issue_id']} has already been twittered for one of the the following job(s): ".implode(', ',$job_labels).". ($initiative_id)\n";
				}
			}
		}

		if(!$new_event_found){
			echo "\n*No untwittered issues for job '$job_label'.\n\n";
		}else{
			echo "\n*$twittered issue(s) have been twittered for job '$job_label'.\n\n";
		}

		if($todo>0){
			echo "\n*There are ".$todo." initiative(s) left to process.\n\n"; 
		}

		echo"\n\n";

		log_and_flush(); //Log and flush the output buffer :)
	}
}


/** 
 * Once every day a summaray will be tweeted, so that no nightly issue slips through unnoticed. 
 * If nothing was tweeted since the last summary the summary is skipped.
 * @params
 *		$bot	:array, as defined in config.php
 * @returns nothing
 */


function summary_tweet($bot){
	echo "\nProcessing summary\n";
	echo "------------------------------------------------\n";

	$last_summaries = get_logged_events("summary");
	if(sizeof($last_summaries) <= 1){
		/*
			The first two attempts to post the summary is blocked. On the first run lots of 
			old issues get logged but not twittered. We dont want them to be included in the first summary.
			The second run may occur less than 24 hours later than the first one. It too would spam all
			the old issues. Blocking the first two attempts ensures that 24 hours have passed since the initial
			run of the bot, before posting any summary and thus no crap gets posted.
		*/
		echo "First regular run of the daily summary on this bot. No tweet will be sent.";
		$event = array("issue_id" => -1, "initiative_id" => -1, "job" => "summary");
		log_event($event);						//make sure the next run doesn't look like the first one
		return(true);
	}
	$last_summary = (integer) $last_summaries[0]["time"];

	if($last_summary == 0) {
		$since = time()-24*60*60;
	}else{
		$sinde = $last_summary;
	}

	$min_gap=1*60*60; 							//Minimum gap between two summaries, should be at least 60*60 seconds!
	
	$tweet_prefix = sprintf($bot['daily_tweet'], $bot['hashtag']);
	$tweet = "";

	if(	date("G")==$bot['daily_hour']				//during this hour the bot tries to post a summary
		and $last_summary < time()-$min_gap){		//as long as the last summary did not occur during $min_gap seconds

			foreach($bot['jobs'] as $job_label => $job){
				$last_24h_events=get_logged_events($job_label, $last_summary);
				$num=sizeof($last_24h_events);
				if($num > 0) {
					$tweet .= " $job_label $num,";
				}
				echo "\t$job_label $num\n";
			}			
			if($tweet ==""){
				echo "\tNothing happend during the last 24 hours. No tweet will be sent.";
			}else{
				$tweet[strlen($tweet)-1]=".";
				echo "\tSending tweet: '$tweet'\n";			

				if(twitter($bot['twitter'], $tweet_prefix.$tweet)){
					echo "\t\tSummary sent.";
				}else{
					echo "\t\tAborted: unable to send tweet.\n";
					return false;
				}
			}											
			$event = array("issue_id" => -1, "initiative_id" => -1, "job" => "summary");
			log_event($event);			
	}else{
		$date = date("D d.M Y, H:i", $last_summary);
		echo "It's not time for that: Waiting until ".$bot['daily_hour']."h.\n";
		if(sizeof($last_summaries) > 1){		
			echo "Last summary was tweeted at $date.";
		}else{
			echo "There has no summary been tweeted yet.";
		}
			
	}
	echo"\n\n";	
}

?>
