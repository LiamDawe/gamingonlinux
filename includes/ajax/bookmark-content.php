<?php
session_start();
header('Content-Type: application/json');

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
  if ($_POST['method'] == 'add')
  {
    // find if it exists already
    $db->sqlquery("SELECT `data_id` FROM `user_bookmarks` WHERE `data_id` = ? AND `user_id` = ? AND `type` = ?", array($_POST['id'], $_SESSION['user_id'], $_POST['type']));
    if ($db->num_rows() == 0)
    {
      $parent_id = NULL;
      if (isset($_POST['parent_id']) && $_POST['parent_id'] != 0)
      {
        $parent_id = $_POST['parent_id'];
      }
      $db->sqlquery("INSERT INTO `user_bookmarks` SET `user_id` = ?, `data_id` = ?, `type` = ?, `parent_id` = ?", array($_SESSION['user_id'], $_POST['id'], $_POST['type'], $parent_id));

      echo json_encode(array("result" => 'added'));
      return;
    }
  }
  if ($_POST['method'] == 'remove')
  {
    // find if it exists already
    $db->sqlquery("DELETE FROM `user_bookmarks` WHERE `data_id` = ? AND `user_id` = ? AND `type` = ?", array($_POST['id'], $_SESSION['user_id'], $_POST['type']));
    echo json_encode(array("result" => 'removed'));
    return;
  }
}
?>
