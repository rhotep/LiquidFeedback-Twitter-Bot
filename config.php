<?php
/*
	Requirements:
	You need accounts for twitter.com, bit.ly and your LiquidFeedback instance.

	You also need to register an application on http://dev.twitter.com/apps .

	Make sure not to leave a blank line at the end of this (or any other) file. Header needs to be send later on.
*/


//MYSQL	details:

define(DB_HOST, 				"localhost");
define(DB_USER,					"");
define(DB_PASS,					"");
define(DB, 						mysql_connect(DB_HOST, DB_USER, DB_PASS));
define(DB_NAME,					"");
define(DB_TABLE_LOG, 			"log");

//bit.ly

define(BL_LOGIN, 				"");
define(BL_API_KEY,  			"");


mysql_select_db(DB_NAME, DB);


//BOTS


//************the berlin bot (@lqppbe)

$bots['be'] = array(		
			"lf"		=> 	array(		//LiquidFeedback details
				"base_dir"	=> 	"https://lqpp.de/be",	
				"api_key" 	=> 	"",
			),				

			"twitter"	=>	array(		//Twitter OAuth details
				"name"				=> "",
				"consumer_key"		=> "",
				"consumer_secret"	=> "",
				"access_key"		=> "",
				"access_secret"		=> "",
			),

			"daily_hour"			=>	20,									//Hour of the day when the summary is tweeted. Put 20 for 20:00

			"daily_tweet"			=>	'Was zuletzt in %1$s passierte:',	//Beginning of the daily tweet, keep it short! %1 is replaced by the hashtag. (Single Quotes!)

			"hashtag"				=>	"#lqppbe",							//The hashtag to include in your tweets

			"starting_id"			=>  700,		
				/*
					Some rather recent initiative id. earlier initiative will be ignored.
					The state parameter for the LF-query currently doesn't work. So for some jobs one has to fetch all
					initiatives, which in some cases may just be too much.
				*/

			"jobs"		=> 	array(		
				/*
					Jobs only work with issues, there is no way to twitter special initiatives on their own.
					You cannot for example twitter recent changes of an initiative. It would be great if someone
					added the feature.
				*/
				"Neue Schnellverfahren" =>	array(											
					/*
						look for issues in phase 'new' or 'accepted' with policy 9
						and send a tweet formatted like $format
					*/
					"query"			=> 	"policy_id=9",	
						/*	
							all initiatives whose corresponing issue uses policy 9 
							"Eilverfahren" on lqppbe
							watch out: the policy ids vary on different instances!
						*/															
					"states" 		=>	array("new", "accepted"), 
						/*
							only pick initiatives whose corresponding issue is in phase 'new' or 'accepted', 
							other examples are 'frozen' or 'voting'
							it can happen that an issue proceeds from 'new' to 'accepted' before the bot notices,
							that's why I provide an array here rather than just the string  'new'
						*/
					"format"		=>  'ACHTUNG: Neues Schnellverfahren (%3$s) auf %1$s eröffnet: %2$s',				
						/*
							format of the message to be tweeted
							%1$s is replaced by the hashtag (see above)
							%2$s is replaced by the shortened link to the initiative
							%3$s is replaced by the issue's id
							make sure to use single quotes here!
						*/
					"dont_retweet" => array(),
						/*
							tell the bot to ignore issues already handled by other jobs
							see 'Neue Verfahren' below
						*/
				),

				"Neue Verfahren" =>	array(											
						/*
							look for any issues in phase 'new' or 'accepted'
							and send a tweet formatted like $format
							issues that already have been twittered wont be again.
						*/
					"query"			=> 	"min_id=0",	
						// all initiatives, yet this will be overridden my 'min-id=starting_id' 															
					"states" 		=>	array("new", "accepted"), 	
						/*
							only pick initiatives whose corresponding issue is in phase 'new' or 'accepted', 
							other examples are 'frozen' or 'voting'
							it can happen that an issue proceeds from 'new' to 'discussion' before the bot notices,
							that's why I provide an array here rather than just the string  'new'
						*/
					"format"		=>  'Neues Verfahren (%3$s) auf %1$s eröffnet: %2$s',				
						//

					"dont_retweet"	=> "Neue Schnellverfahren",
						/*
							issues already tweeted for job 'Neue Schnellverfahren' will be ignored
						*/
				),

				"Abstimmungen"		=> array(
					/*
						send a tweet for each issue that is currently voted on
					*/
					"query"			=> 	"min_id=0",	
						//all initiatives 															
					"state" 		=>	"voting",			
						//only those in state 'voting' 
					"format"		=>  'Neue Abstimmung in %1$s gestartet: %2$s',
						//
				),
			),
);

/************

Add as many bots as you like.

$bots['test'] = array(...
		
*************/

?>
