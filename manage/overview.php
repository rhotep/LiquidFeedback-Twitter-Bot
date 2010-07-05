<?php

require_once "../config.php";
require_once "../tools.php";

?>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="styles.css">	
</head>
<body>
		<table class="box extended">
			<tr>
				<td class="topleft"></td>
				<td class="topcenter"></td>
				<td class="topright"></td>
			</tr>
			<tr>
				<td class="centerleft"></td>
				<td class="content overview">

					<iframe src="../update_twitter_bot.php" height="400" width="50%"></iframe>
					<h1>Running bots: <a href="overview.php">Update bots</a></h1>
					<div id="bots">



<?php
	foreach($bots as $bot){
		$twitter = $bot['twitter'];
?>
						<table class="box twitter">
							<tr>
								<td class="topleft"></td>
								<td class="topcenter"></td>
								<td class="topright"></td>
							</tr>
							<tr>
							<td class="centerleft"></td>
								<td class="content">



<script src="http://widgets.twimg.com/j/2/widget.js"></script>
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: 4,
  interval: 6000,
  width: 'auto',
  height: 200,
  theme: {
    shell: {
      background: '#333333',
      color: '#ffffff'
    },
    tweets: {
      background: '#000000',
      color: '#ffffff',
      links: '#4aed05'
    }
  },
  features: {
    scrollbar: true,
    loop: false,
    live: true,
    hashtags: true,
    timestamp: true,
    avatars: false,
    behavior: 'all'
  }
}).render().setUser('<?php echo $twitter['name'];?>').start();
</script>

		
								</td>
								<td class="centerright"></td>
							</tr>
							<tr>
								<td class="bottomleft"></td>
								<td class="bottomcenter"></td>
								<td class="bottomright"></td>
							</tr>
						</table>
<?php
	}
?>
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


