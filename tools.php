<?php

require_once "config.php";


/**
 * Drops the log table
 * @returns nothing
 */

function clear_log(){
	$query="DROP TABLE ".DB_TABLE_LOG;
	$result = mysql_query($query);
	log_this("new log");
}

/**
 * Checks a table named $table exists
 * @params
 * 		$table	:string, name of the table to look for
 * @returns true if the table exists, false otherwise
 */

function table_exists($table){
	$table = mysql_escape_string($table);
	$query = "SELECT * FROM $table";
	$result = mysql_query($query);
	return(mysql_errno() != 1146);
}


/**
 * Creates a table named $table with columns $colums
 * @params
 *		$table	:string, name of the table
 * 		$colums :string, columns of the table e.g. "name, age, id"
 * @returns true on success, dies otherwiese
 */

function create_table($table, $columns){
	$table = mysql_escape_string($table);
	$columns = mysql_escape_string($columns);
	$query = "CREATE TABLE $table ($columns)";
	$result = mysql_query($query);
	$error = mysql_errno();
	if($error != 0){
		if($table!=DB_TABLE_LOG){ //prevent infinite loop: must not try to log that logging doesn't work
			log_this("error: tried to create table $table", mysql_error());
		}else{
			echo "Unable to log.\n";
		}
		die("MySQL error: ".$error);
	}else{
		log_this("success: created table $table", "($columns)");
		return(true);
	}
}

/**
 * Appends a row to the log table
 * @params
 *		$key	:string, a short description of the data to be stored
 * 		$data	:the data to be stored
 * @returns true on success, dies otherwise
 */

function log_this($key, $data=''){
	$key = mysql_escape_string($key);
	$data = mysql_escape_string($data);
	if(!table_exists(DB_TABLE_LOG)){
		create_table(DB_TABLE_LOG, "id INT AUTO_INCREMENT, time INT, label TEXT, data TEXT, PRIMARY KEY (id)");
	}else{		// nothing is kept older than 3 days
		$time=time()-60*60*24*3;
		$query = "DELETE from ".DB_TABLE_LOG." WHERE time<$time";
		$result = mysql_query($query);
		if(mysql_errno != 0){	
			die("MySQL error: ".mysql_errno());
		}
	}
	$query = "INSERT INTO ".DB_TABLE_LOG."(time, label, data) VALUES ( ".time().", \"$key\", \"$data\")";
	$result = mysql_query($query);
	if(mysql_errno != 0){
		die("MySQL error: ".mysql_errno());
	}else{
		return(true);
	}
}

/**
 *	Logs the output buffer and flushes it afterwards
 *
 */
 
function log_and_flush(){
	global $log;
	$log .= ob_get_contents();
	ob_flush();
}

/**
 * Outputs a table
 * @params
 *		$table :string, the name of the table
 * @returns nothing
 */

function print_table($table){
	$query = "SELECT * FROM ".$table;
	$result = mysql_query($query);
	while($result and $row = mysql_fetch_row($result)){
		foreach($row as $key => $column){
			$column = substr($column, 0, 180);
			echo "$column\t\t\t\t\t";
		}
		echo "\n";
	}
}

/**
 * Decodes a JSON string
 * @params
 *		$json	:string, JSON to decode
 * @returns an array corresponding to the JSON string
 */

function my_json_decode($json){		// found on php.net (and modified)
    $comment = false;
    $out = '$x=';
   
    for ($i=0; $i<strlen($json); $i++)
    {
        if (!$comment){
            if (in_array($json[$i], array('{', '[')))  		$out .= ' array(';
            else if (in_array($json[$i], array('}', ']')))  $out .= ')';
            else if ($json[$i] == ':')    $out .= '=>';
            else                         $out .= $json[$i];           
        }else{
			 $out .= $json[$i];
		}
        if ($json[$i] == '"' and ($json[$i-1] != "\\" ))    $comment = !$comment;
    }
   	if(eval($out . ';')===false){
		log_this("error: my_json_decode", "$out");		
		return(array());
	}else{
		log_this("success: my_json_decode", $out);
		return $x;
	}
}  

?>
