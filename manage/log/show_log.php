<?php

require_once "../../config.php";
require_once "../../tools.php";

function plus_minus($id){
	$out="
		<div class=\"switch plus\" id=\"plus_$id\"
			onclick=\"
				document.getElementById('response_$id').style.display='inline';
				document.getElementById('plus_$id').style.display='none';
				document.getElementById('minus_$id').style.display='block';
				\"
		></div>
		<div class=\"switch minus\" id=\"minus_$id\"
			onclick=\"
				document.getElementById('response_$id').style.display='none';
				document.getElementById('plus_$id').style.display='block';
				document.getElementById('minus_$id').style.display='none';
			\"
		></div> 				
	";
	return($out);
}



function log_table(){
	$query="SELECT * FROM ".DB_TABLE_LOG." ORDER BY id DESC";
	$result = mysql_query($query);
	if(!$result){
		return(false);
	}else{
		$log_table = "<table class=\"log\">";
		while($row=mysql_fetch_row($result)){
			$time=$row[1];
			$id=$row[0];
			$log_table.="
						<tr>
							<td class=\"time\">".date("D d.M Y \t H:i",$time)."</td>
							<td class=\"label\">".$row[2]."</td>
							<td class=\"switch\">";
			$log_table.=plus_minus($id);
			$log_table.="		
							</td>
							<td class=\"data\"><textarea id=\"response_$id\" style=\"display:none;\">".$row[3]."</textarea></td>
						</tr>
			";
		}
		$log_table.="</table>";
		return($log_table);
	}	
}

$clear = $_GET["clear"];
if($clear==1){
	clear_log();
}

?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../styles.css">	
</head>
<body>
		<table class="box">
			<tr>
				<td class="topleft"></td>
				<td class="topcenter"></td>
				<td class="topright"></td>
			</tr>
			<tr>
				<td class="centerleft"></td>
				<td class="content">
					<div class="core">
						<a href="show_log.php?clear=1">Clear log</a>
						&nbsp;
						<a href="show_log.php">Refresh</a>
						<br/><br/>
						<?php echo log_table(); ?>
					</div>
				</td>
				<td class="centerright"></td>
			</tr>
			<tr>
				<td class="bottomleft"></td>
				<td class="bottomcenter"></td>
				<td class="bottomright"></td>
			</tr>
		</table>
</body>
<?php

mysql_close(DB);

?>


