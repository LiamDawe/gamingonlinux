<?php
/* TODO

FORUM TOPIC NEEDS ADJUSTING - GET TEXT FROM FORUM_REPLIES WITH IS_TOPIC = 1

Email people the link once finished
Mention on the user deletion page they can download their data - mention this will not work if they delete their account before doing so
Forum replies
User profile info - should link to index files for other sections?
*/
ini_set("memory_limit", "-1");

define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/cron_bootstrap.php";

// make sure user exists first
$get_users = $dbl->run("SELECT dr.`user_id`,u.`username`,u.`avatar_uploaded`,u.`avatar` FROM `user_data_request` dr INNER JOIN `users` u ON u.user_id = dr.user_id ORDER BY `date_requested` ASC")->fetch_all();

foreach ($get_users as $user)
{
	$data_folder = APP_ROOT . '/uploads/user_data_request/' . $user['user_id'] . '/';

	if (!file_exists($data_folder)) 
	{
		mkdir($data_folder, 0777, true);
		@chmod($data_folder, 0777);
	}

	$zip = new ZipArchive();

	$zip_name = $user['user_id'].time().core::random_id().'.zip';

	/* article comments */

	$comment_text = '<p><a href="index.html">Back to index file</a></p>';
	$comment_index_text = 'Here is a list of files showing your comments on <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a>. Any problems with this archive please email contact@gamingonlinux.com.';

	$comments = $dbl->run("SELECT c.comment_text, c.time_posted, a.title FROM `articles_comments` c LEFT JOIN `articles` a ON c.article_id = a.article_id WHERE c.`author_id` = ?", array($user['user_id']))->fetch_all();

	$total_comments = count($comments);

	$comments_files = [];

	$counter = 0;
	$file_number = 0;
	if ($total_comments > 0)
	{
		$comment_index_text .= '<ul>';
		while($comment_loop = array_splice($comments, 0, 50)) 
		{
			$file_number++;
			foreach($comment_loop as $comment)
			{
				$counter++;
				$comment_date = date('Y-m-d H:i:s', $comment['time_posted']);

				$title = '';
				if (isset($comment['title']))
				{
					$title = $comment['title'];
				}
				else
				{
					$title = 'Couldn\'t get article title, possibly a deleted article';
				}

				$comment_text .= '<div><strong>Article title: '.$title.'</strong></div>
				<div><em>Comment posted on:  '.$comment_date.'</em></div>
				<div>'.$comment['comment_text'].'</div>';

				if ($counter < $total_comments)
				{
					$comment_text .= '<hr />';
				}
			}

			$comments_file = $data_folder . $user['user_id'] . 'comments_'.$file_number.'.html';
			file_put_contents($comments_file, $comment_text);
			$comments_files[$comments_file] = $user['user_id'] . 'comments_'.$file_number.'.html';
			$comment_text = '';

			$comment_index_text .= '<li><a href="' . $user['user_id'] . 'comments_'.$file_number.'.html">File: '.$file_number.'</a></li>';
		}
		$comment_index_text .= '</ul>';

		// make an index file with links to the other files, zip it, remove the basic file
		$comment_index_filename = $data_folder . $user['user_id'] . 'comments_index.html';

		file_put_contents($comment_index_filename, $comment_index_text);

		if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
		{
			$zip->addFile($comment_index_filename, 'article_comments/index.html');
			$zip->close();
			echo 'ok';
		} 
		else 
		{
			error_log ( 'Failed making zip file for user data request' );
		}

		unlink($comment_index_filename);

		// now add each comments file to the zip, then remove the basic file
		foreach ($comments_files as $full_file_path => $file_name)
		{
			if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
			{
				$zip->addFile($full_file_path, 'article_comments/'.$file_name);
				$zip->close();
				echo 'ok';
			} 
			else 
			{
				error_log ( 'Failed making zip file for user data request' );
			}
			unlink($full_file_path);
		}
	}

	/* user avatar if uploaded */

	if ($user['avatar_uploaded'] == 1)
	{
		$avatar_path = APP_ROOT . '/uploads/avatars/' . $user['avatar'];
		if (file_exists($avatar_path))
		{
			if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
			{
				$zip->addFile($avatar_path, '/avatar/'.$user['avatar']);
				$zip->close();
				echo 'ok';
			} 
			else 
			{
				error_log ( 'Failed making adding user avatar to zip file for user data request' );
			}
		}
	}

	/* forum topics */

	$forum_topics = $dbl->run("SELECT `topic_title`, `creation_date`, `topic_text` FROM `forum_topics` WHERE `author_id` = ?", array($user['user_id']))->fetch_all();

	$total_topics = count($forum_topics);

	$topics_text = '<p><a href="index.html">Back to index file</a></p>';
	$topics_index_text = 'Here is a list of files showing your forum topics on <a href="https://www.gamingonlinux.com">GamingOnLinux.com</a>. Any problems with this archive please email contact@gamingonlinux.com.';

	$topics_files = [];

	$counter = 0;
	$file_number = 0;
	if ($total_topics > 0)
	{
		$topics_index_text .= '<ul>';
		while($topics_loop = array_splice($forum_topics, 0, 50)) 
		{
			$file_number++;
			foreach($topics_loop as $topic)
			{
				$counter++;
				$topic_date = date('Y-m-d H:i:s', $topic['creation_date']);

				$title = $topic['topic_title'];

				$topics_text .= '<div><strong>Topic title: '.$title.'</strong></div>
				<div><em>Topic posted on:  '.$topic_date.'</em></div>
				<div>'.$topic['topic_text'].'</div>';

				if ($counter < $total_topics)
				{
					$topics_text .= '<hr />';
				}
			}

			$topics_file = $data_folder . $user['user_id'] . 'topics_'.$file_number.'.html';
			file_put_contents($topics_file, $topics_text);
			$topics_files[$topics_file] = $user['user_id'] . 'topics_'.$file_number.'.html';
			$topics_text = '';

			$topics_index_text .= '<li><a href="' . $user['user_id'] . 'topics_'.$file_number.'.html">File: '.$file_number.'</a></li>';
		}
		$topics_index_text .= '</ul>';

		// make an index file with links to the other files, zip it, remove the basic file
		$topics_index_filename = $data_folder . $user['user_id'] . 'forum_topics_index.html';

		file_put_contents($topics_index_filename, $topics_index_text);

		if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
		{
			$zip->addFile($topics_index_filename, 'forum_topics/index.html');
			$zip->close();
			echo 'ok';
		} 
		else 
		{
			error_log ( 'Failed making zip file for user data request' );
		}

		unlink($topics_index_filename);

		// now add each comments file to the zip, then remove the basic file
		foreach ($topics_files as $full_file_path => $file_name)
		{
			if ($zip->open($data_folder.$zip_name, ZipArchive::CREATE) === TRUE) 
			{
				$zip->addFile($full_file_path, 'forum_topics/'.$file_name);
				$zip->close();
				echo 'ok';
			} 
			else 
			{
				error_log ( 'Failed making forum topics zip file for user data request' );
			}
			unlink($full_file_path);
		}
	}

	/* forum replies */

	/* user profile */

	echo 'Download: <a href="https://www.gamingonlinux.com/uploads/user_data_request/'.$user['user_id'].'/'.$zip_name.'">https://www.gamingonlinux.com/uploads/user_data_request/'.$user['user_id'].'/'.$zip_name.'</a>';

	// check if they have admin notes?

	// check for any admin notifications?
}