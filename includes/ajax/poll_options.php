<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$templating = new template($core, $core->config('template'));

if($_POST)
{
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
    {
		$grab_poll = $dbl->run("SELECT `poll_id`, `author_id`, `poll_question`, `topic_id`, `poll_open` FROM `polls` WHERE `poll_id` = ?", array($_POST['poll_id']))->fetch();
		if ($grab_poll)
		{
			if ($grab_poll['poll_open'] == 1)
			{
				// find if they have voted or not
				$check = $dbl->run("SELECT `user_id` FROM `poll_votes` WHERE `poll_id` = ?", array($grab_poll['poll_id']))->fetchOne();

				// if they haven't voted
				if (!$check)
				{
					$options = '<ul style="list-style: none; padding:5px; margin: 0;">';
					$grab_options = $dbl->run("SELECT `option_id`, `poll_id`, `option_title` FROM `poll_options` WHERE `poll_id` = ?", array($grab_poll['poll_id']))->fetch_all();
					foreach ($grab_options as $option)
					{
						$options .= '<li><button name="pollvote" class="poll_button_vote poll_button" data-poll-id="'.$option['poll_id'].'" data-option-id="'.$option['option_id'].'">'.$option['option_title'].'</button></li>';
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
		else
		{
			echo 'Sorry, can\'t find that poll!';
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
