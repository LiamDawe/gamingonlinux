<?php
session_start();

include('../../includes/config.php');

include('../../includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../../includes/class_core.php');
$core = new core();

if($_POST)
{
    $db->sqlquery("SELECT `poll_id`, `author_id`, `poll_question`, `topic_id`, `poll_open` FROM `polls` WHERE `poll_id` = ?", array($_POST['poll_id']));
    $grab_poll = $db->fetch();

    $db->sqlquery("SELECT `option_id`, `option_title`, `votes` FROM `poll_options` WHERE `poll_id` = ? ORDER BY `votes` DESC", array($grab_poll['poll_id']));
    $options = $db->fetch_all_rows();

    // see if they voted to make their option have a star * by the name
    if (isset($_SESSION['user_id']))
    {
        if ($_SESSION['user_id'] != 0)
        {
            $db->sqlquery("SELECT `user_id`, `option_id` FROM `poll_votes` WHERE `user_id` = ? AND `poll_id` = ?", array($_SESSION['user_id'], $_POST['poll_id']));
            $check_voted = $db->num_rows();
            $get_user = $db->fetch();
        }
    }

    $total_votes = 0;
    foreach ($options as $votes)
    {
        $total_votes = $total_votes + $votes['votes'];
    }

    $results = '';
    $star = '';
    foreach ($options as $option)
    {
        if (isset($_SESSION['user_id']))
        {
            if ($_SESSION['user_id'] != 0)
            {
                if ($option['option_id'] == $get_user['option_id'])
                {
                    $star = '*';
                }
            }
        }
        $total_perc = '0';
        if ($option['votes'] > 0)
        {
            $total_perc = round($option['votes'] / $total_votes * 100);
        }

        $results .= '<div class="group"><div class="col-4">' . $star . $option['option_title'] . $star . '</div> <div class="col-4"><div style="background:#CCCCCC; border:1px solid #666666;"><div style="background: #28B8C0; width:'.$total_perc.'%;">&nbsp;</div></div></div> <div class="col-2">'.$option['votes'].' vote(s)</div> <div class="col-2">'.$total_perc.'%</div></div>';
        $star = '';
    }

    if ($check_voted == 0 && $grab_poll['poll_open'] == 1)
    {
        $results .= '<ul style="list-style: none; padding:5px; margin: 0;"><li><button name="pollresults" class="back_vote_button poll_button" data-poll-id="'.$grab_poll['poll_id'].'">Back to voting</button></li></ul>';
    }

    if ($grab_poll['poll_open'] == 1)
    {
      if ($_SESSION['user_id'] == $grab_poll['author_id'])
      {
        $results .= '<ul style="list-style: none; padding:5px; margin: 0;"><li><button name="closepoll" class="close_poll poll_button" data-poll-id="'.$grab_poll['poll_id'].'">Close Poll</button></li></ul>';
      }
    }

    if ($grab_poll['poll_open'] == 0)
    {
      if ($_SESSION['user_id'] == $grab_poll['author_id'])
      {
        $results .= '<ul style="list-style: none; padding:5px; margin: 0;"><li><button name="openpoll" class="open_poll poll_button" data-poll-id="'.$grab_poll['poll_id'].'">Open Poll</button></li></ul>';
      }
    }

    echo $results;
}
else
{
    echo 'No poll requested!';
}
