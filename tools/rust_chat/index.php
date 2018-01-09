<!doctype html>

<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="description" content="The HTML5 Herald">
  <meta name="author" content="SitePoint">
  <link rel="stylesheet" href="css/styles.css?v=1.0">
  <title>GamingOnLinux.com Rust Server Chat</title>
<style media="screen" type="text/css">
@import url(https://fonts.googleapis.com/css?family=Roboto:400,500,700,300,100);

body {
  background-color: #3e94ec;
  font-family: "Roboto", helvetica, arial, sans-serif;
  font-size: 16px;
  font-weight: 400;
  text-rendering: optimizeLegibility;
}

div.table-title {
   display: block;
  margin: auto;
  max-width: 600px;
  padding:5px;
  width: 100%;
}

.table-title h3 {
   color: #fafafa;
   font-size: 30px;
   font-weight: 400;
   font-style:normal;
   font-family: "Roboto", helvetica, arial, sans-serif;
   text-shadow: -1px -1px 1px rgba(0, 0, 0, 0.1);
   text-transform:uppercase;
}


/*** Table Styles **/

.table-fill {
  background: white;
  border-radius:3px;
  border-collapse: collapse;
  height: 320px;
  margin: auto;
  max-width: 600px;
  padding:5px;
  width: 100%;
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
  animation: float 5s infinite;
}
 
th {
  color:#D5DDE5;;
  background:#1b1e24;
  border-bottom:4px solid #9ea7af;
  border-right: 1px solid #343a45;
  font-size:23px;
  font-weight: 100;
  padding:24px;
  text-align:left;
  text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
  vertical-align:middle;
}

th:first-child {
  border-top-left-radius:3px;
}
 
th:last-child {
  border-top-right-radius:3px;
  border-right:none;
}
  
tr {
  border-top: 1px solid #C1C3D1;
  border-bottom-: 1px solid #C1C3D1;
  color:#666B85;
  font-size:16px;
  font-weight:normal;
  text-shadow: 0 1px 1px rgba(256, 256, 256, 0.1);
}
 
tr:hover td {
  background:#4E5066;
  color:#FFFFFF;
  border-top: 1px solid #22262e;
}
 
tr:first-child {
  border-top:none;
}

tr:last-child {
  border-bottom:none;
}
 
tr:nth-child(odd) td {
  background:#EBEBEB;
}
 
tr:nth-child(odd):hover td {
  background:#4E5066;
}

tr:last-child td:first-child {
  border-bottom-left-radius:3px;
}
 
tr:last-child td:last-child {
  border-bottom-right-radius:3px;
}
 
td {
  background:#FFFFFF;
  padding:20px;
  text-align:left;
  vertical-align:middle;
  font-weight:300;
  font-size:18px;
  text-shadow: -1px -1px 1px rgba(0, 0, 0, 0.1);
  border-right: 1px solid #C1C3D1;
}

td:last-child {
  border-right: 0px;
}

th.text-left {
  text-align: left;
}

th.text-center {
  text-align: center;
}

th.text-right {
  text-align: right;
}

td.text-left {
  text-align: left;
}

td.text-center {
  text-align: center;
}

td.text-right {
  text-align: right;
}

</style>
<script src="https://www.gamingonlinux.com/includes/jscripts/jquery-3.2.1.min.js"></script>
<script src="https://www.gamingonlinux.com/includes/jscripts/jquery.timeago.js"></script>
</head>

<body>
<strong>GamingOnLinux.com Rust Server Chat</strong><br />
The chat will be updated every 5 minutes, refresh for the latest. You last refreshed: <?php echo date('d/m/y H:i:s'); ?>
<br />
<table class="rust_chat">
  <tr>
    <th class="date_row">Date</th>
    <th>Username</th>
    <th>Text</th>
  </tr>
<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ));
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_db_mysql.php');

$db_conf = include $file_dir . '/includes/config.php';

$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password']);

// display the chat
$chat = $dbl->run("SELECT * FROM `rust_chat` ORDER BY `id` DESC")->fetch_all();

foreach($chat as $line)
{
	$machine_time = date("Y-m-d\TH:i:s", strtotime($line['date'])).'-08:00';
	echo '<tr>';
	echo '<td class="text-left"><time class="timeago" datetime="'.$machine_time.'">'.$line['date'].'</time></td><td class="text-left">'.$line['username'].'</td><td class="text-left">'.$line['text'].'</td>';
	echo '</tr>';
}
?>
</table>
<script>jQuery("time.timeago").timeago();</script>
</body>
</html>
