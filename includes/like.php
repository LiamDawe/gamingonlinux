<?php
session_start();
 
include('config.php');
 
include('class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);
 
include('class_core.php');
$core = new core();
 
include('class_user.php');
$user = new user();
 
if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
        $pinsid=$_POST['sid'];
        $status=$_POST['sta'];
        $chkpinu = $db->sqlquery("SELECT * FROM likes WHERE comment_id = ? AND user_id = ?", array($pinsid, $_SESSION['user_id']));
        $chknum = $db->num_rows();
        if($status=="like")
        {
                if($chknum==0)
                {
                        $add = $db->sqlquery("INSERT INTO `likes` SET `comment_id` = ?, `user_id` = ?", array($pinsid, $_SESSION['user_id']));
                        echo 1;
			return true;
                }
                echo 2; //Bad Checknum
		return true;
        }
        else if($status=="unlike")
        {
                if($chknum!=0)
                {
                        $rem=$db->sqlquery("DELETE FROM `likes` WHERE comment_id = ? AND user_id = ?", array($pinsid, $_SESSION['user_id']));
                        echo 1;
			return true;
                }
                echo 2; //Bad Checknum
		return true;
        }
        echo 3; //Bad Status
	return true;
}
echo 4; //Bad Post or Session

return true;
?>
