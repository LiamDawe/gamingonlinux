<?php
session_start();

include('../../includes/config.php');

include('../../includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../../includes/class_core.php');
$core = new core();

include('../class_template.php');

$templating = new template('default');

if($_POST)
{
    if (isset($_SESSION['user_id']))
    {
        if ($_SESSION['user_id'] != 0)
        {
            $db->sqlquery("SELECT `poll_id`, `author_id`, `poll_question`, `topic_id`, `poll_open` FROM `polls` WHERE `poll_id` = ?", array($_POST['poll_id']));
            if ($db->num_rows() == 1)
            {
                $grab_poll = $db->fetch();
                if ($grab_poll['poll_open'] == 1)
                {
                    // find if they have voted or not
                    $db->sqlquery("SELECT `user_id` FROM `poll_votes` WHERE `poll_id` = ?", array($grab_poll['poll_id']));

                    // if they haven't voted
                    if ($db->num_rows() == 0)
                    {
                        $options = '<ul style="list-style: none; padding:5px; margin: 0;">';
                        $grab_options = $db->sqlquery("SELECT `option_id`, `poll_id`, `option_title` FROM `poll_options` WHERE `poll_id` = ?", array($grab_poll['poll_id']));
                        foreach ($grab_options as $option)
                        {
                            $options .= '<li><button name="pollvote" class="poll_button" data-poll-id="'.$option['poll_id'].'" data-option-id="'.$option['option_id'].'">'.$option['option_title'].'</button></li>';
                        }
                        $options .= '<li><button name="pollresults" class="poll_button results_button" data-poll-id="'.$grab_poll['poll_id'].'">View Results</button></li>';

                        if ($_SESSION['user_id'] == $grab_poll['author_id'])
                        {
                          $options .= '<li><button name="closepoll" class="poll_button close_poll" data-poll-id="'.$grab_poll['poll_id'].'">Close Poll</button></li>';
                        }

                        $options .= '</ul>';

                        echo $options;
                    }
                }
                else
                {
                    echo 'Sorry, poll closed!';
                }
            }
        }
        else
        {
            echo 'Only registered users can see options to vote!';
        }
    }
    else
    {
        echo 'Only registered users can see options to vote!';
    }
}
else
{
    echo 'No poll requested!';
}
