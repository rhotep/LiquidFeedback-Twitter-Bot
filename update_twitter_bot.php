<?php

require_once "bot.php";

header("Content-Type: text/plain");

ob_start(); 	//start output buffer
log_this("\_________ update script called", hash('sha256', $_SERVER['REMOTE_ADDR']));		// logs all calls of the update sript


// clear_log();	

// print_table(DB_TABLE_LOG);


foreach($bots as $name => $bot){	
	log_this("––––––––––––  processing bot '$name'");
	echo "** BOT '$name' running \n*****************************\n";

	log_and_flush(); //Log and flush the output buffer :)

	$db_events_table = $name."_events"; // every bot gets his own table to log events
	//clear_logged_events();			// reset the bot
	process($bot, !table_exists($db_events_table)); 
	/* 
		If the events table does not exists nothing will be twittered.
		This way old stuff wont be twittered in the first run. 
		Instead the table is created and logs the old events.
	*/
	summary_tweet($bot);	//Daily summary.
	echo "\n\n\n\n";

	//print_table($db_events_table);	// shows all logged events for dubbing
}

log_and_flush(); 		//Log and flush the output buffer for the last time
ob_end_clean();				//end output buffer

log_this("––––––––––––– done. ", $log);		//write the output of this script to the log table
mysql_close(DB);
?>



