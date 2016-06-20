<?php
$templating->set_previous('title', 'Linux Gamer User Profile', 1);

// check user exists
if (isset($_GET['user_id']))
{
	include('includes/profile_fields.php');

	$db_grab_fields = '';
	foreach ($profile_fields as $field)
	{
		$db_grab_fields .= "{$field['db_field']},";
	}

	$db->sqlquery("SELECT `user_id`, `pc_info_public`, `username`, `distro`, `register_date`, `email`, `avatar`, `avatar_gravatar`, `gravatar_email`, `avatar_uploaded`, `avatar_gallery`, `comment_count`, `forum_posts`, $db_grab_fields `article_bio`, `last_login`, `banned`, `user_group`, `secondary_user_group`, `ip` FROM `users` WHERE `user_id` = ?", array($_GET['user_id']));
	if ($db->num_rows() != 1)
	{
		$core->message('That person does not exist here!');
	}

	else
	{
		$profile = $db->fetch();

		if ($profile['banned'] == 1 && $user->check_group(1,2) == false)
		{
			$core->message("That user is banned so you may not view their profile!", NULL, 1);
		}

		else if (($profile['banned'] == 1 && $user->check_group(1,2) == true) || $profile['banned'] == 0)
		{
			if ($profile['banned'] == 1)
			{
				$core->message("You are viewing a banned users profile!", NULL, 2);
			}

			$templating->set_previous('meta_description', "Viewing {$profile['username']} profile on GamingOnLinux.com", 1);

			$templating->merge('profile');

			if ($_SESSION['user_id'] == $_GET['user_id'])
			{
				$templating->block('top');
			}

			$templating->block('main');

			$templating->set('username', $profile['username']);


			$donator_badge = '';
			if (($profile['secondary_user_group'] == 6 || $profile['secondary_user_group'] == 7) && $profile['user_group'] != 1 && $profile['user_group'] != 2)
			{
				$donator_badge = ' <span class="badge supporter">GOL Supporter</span> ';
			}

			$templating->set('supporter_badge', $donator_badge);

			$editor_badge = '';
			if ($profile['user_group'] == 1 || $profile['user_group'] == 2)
			{
				$editor_badge = " <span class=\"badge editor\">Editor</span> ";
			}

			// check if accepted submitter
			if ($profile['user_group'] == 5)
			{
				$editor_badge = " <span class=\"badge editor\">Contributing Editor</span> ";
			}

			$templating->set('editor_badge', $editor_badge);

			$registered_date = $core->format_date($profile['register_date']);
			$templating->set('registered_date', $registered_date);

			$avatar = $user->sort_avatar($profile);

			$templating->set('avatar', $avatar);
			$templating->set('article_comments', $profile['comment_count']);
			$templating->set('forum_posts', $profile['forum_posts']);

			$profile_fields_output = '';

			foreach ($profile_fields as $field)
			{
				if (!empty($profile[$field['db_field']]))
				{
					if ($field['db_field'] == 'website')
					{
						if (substr($profile[$field['db_field']], 0, 7) != 'http://')
						{
							$profile[$field['db_field']] = 'http://' . $profile[$field['db_field']];
						}
					}

					$url = '';
					if ($field['base_link_required'] == 1 && strpos($profile[$field['db_field']], $field['base_link']) === false ) //base_link_required and not already in the database
					{
						$url = $field['base_link'];
					}

					$image = '';
					if (isset($field['image']) && $field['image'] != NULL)
					{
						$image = "<img src=\"{$field['image']}\" alt=\"{$field['name']}\" />";
					}

					$span = '';
					if (isset($field['span']))
					{
						$span = $field['span'];
					}
					$into_output = '';
					if ($field['name'] != 'Distro')
					{
						$into_output .= "$image$span {$field['name']} <a href=\"$url{$profile[$field['db_field']]}\" target=\"_blank\">$url{$profile[$field['db_field']]}</a><br />";
					}

					$profile_fields_output .= $into_output;
				}
			}

			$templating->set('profile_fields', $profile_fields_output);

			$templating->set('last_login', $core->format_date($profile['last_login']));

			$message_link = '';
			if ($_SESSION['user_id'] != 0)
			{
				$message_link = "<a href=\"/private-messages/compose/user={$_GET['user_id']}\">Send Private Message</a><br />";
			}
			$templating->set('message_link', $message_link);

			$email = '';
			if ($user->check_group(1,2) == true)
			{
				$email = "Email: {$profile['email']}<br />";
			}
			$templating->set('email', $email);

			// additional profile info
			if ($profile['pc_info_public'] == 1)
			{
				$db->sqlquery("SELECT `what_bits`, `cpu_vendor`, `cpu_model`, `gpu_vendor`, `gpu_model`, `gpu_driver`, `ram_count`, `monitor_count`, `gaming_machine_type`, `resolution`, `dual_boot` FROM `user_profile_info` WHERE `user_id` = ?", array($profile['user_id']));

				$counter = 0;
				$templating->block('additional');

				$distro = '';
				if (!empty($profile['distro']) && $profile['distro'] != 'Not Listed')
				{
					$distro = "<li><strong>Distribution:</strong> <img class=\"distro\" height=\"20px\" width=\"20px\" src=\"/templates/default/images/distros/{$profile['distro']}.svg\" alt=\"{$profile['distro']}\" /> {$profile['distro']}</li>";
					$counter++;
				}
				$templating->set('distro', $distro);

				while ($additionaldb = $db->fetch())
				{
					$dist_arc = '';
					if ($additionaldb['what_bits'] != NULL && !empty($additionaldb['what_bits']))
					{
						$dist_arc = '<li><strong>Distribution Architecture:</strong> '.$additionaldb['what_bits'].'</li>';
						$counter++;
					}
					$templating->set('dist_arc', $dist_arc);

					$dual_boot = '';
					if ($additionaldb['dual_boot'] != NULL && !empty($additionaldb['dual_boot']))
					{
						$dual_boot = '<li><strong>Do you dual-boot with a different operating system?</strong> '.$additionaldb['dual_boot'].'</li>';
						$counter++;
					}
					$templating->set('dual_boot', $dual_boot);

					$cpu_vendor = '';
					if ($additionaldb['cpu_vendor'] != NULL && !empty($additionaldb['cpu_vendor']))
					{
						$cpu_vendor = '<li><strong>CPU Vendor:</strong> '.$additionaldb['cpu_vendor'].'</li>';
						$counter++;
					}
					$templating->set('cpu_vendor', $cpu_vendor);

					$cpu_model = '';
					if ($additionaldb['cpu_model'] != NULL && !empty($additionaldb['cpu_model']))
					{
						$cpu_model = '<li><strong>CPU Model:</strong> '.$additionaldb['cpu_model'].'</li>';
						$counter++;
					}
					$templating->set('cpu_model', $cpu_model);

					$gpu_vendor = '';
					if ($additionaldb['gpu_vendor'] != NULL && !empty($additionaldb['gpu_vendor']))
					{
						$gpu_vendor = '<li><strong>GPU Vendor:</strong> '.$additionaldb['gpu_vendor'].'</li>';
						$counter++;
					}
					$templating->set('gpu_vendor', $gpu_vendor);

					$gpu_model = '';
					if ($additionaldb['gpu_model'] != NULL && !empty($additionaldb['gpu_model']))
					{
						$gpu_model = '<li><strong>GPU Model:</strong> '.$additionaldb['gpu_model'].'</li>';
						$counter++;
					}
					$templating->set('gpu_model', $gpu_model);

					$gpu_driver = '';
					if ($additionaldb['gpu_driver'] != NULL && !empty($additionaldb['gpu_driver']))
					{
						$gpu_driver = '<li><strong>GPU Driver:</strong> '.$additionaldb['gpu_driver'].'</li>';
						$counter++;
					}
					$templating->set('gpu_driver', $gpu_driver);

					$ram_count = '';
					if ($additionaldb['ram_count'] != NULL && !empty($additionaldb['ram_count']))
					{
						$ram_count = '<li><strong>RAM:</strong> '.$additionaldb['ram_count'].'</li>';
						$counter++;
					}
					$templating->set('ram_count', $ram_count);

					$monitor_count = '';
					if ($additionaldb['monitor_count'] != NULL && !empty($additionaldb['monitor_count']))
					{
						$monitor_count = '<li><strong>Monitors:</strong> '.$additionaldb['monitor_count'].'</li>';
						$counter++;
					}
					$templating->set('monitor_count', $monitor_count);

					$resolution = '';
					if ($additionaldb['resolution'] != NULL && !empty($additionaldb['resolution']))
					{
						$resolution = '<li><strong>Resolution:</strong> '.$additionaldb['resolution'].'</li>';
						$counter++;
					}
					$templating->set('resolution', $resolution);

					$gaming_machine_type = '';
					if ($additionaldb['gaming_machine_type'] != NULL && !empty($additionaldb['gaming_machine_type']))
					{
						$gaming_machine_type = '<li><strong>Main gaming machine:</strong> '.$additionaldb['gaming_machine_type'].'</li>';
						$counter++;
					}
					$templating->set('gaming_machine_type', $gaming_machine_type);
				}
				$additional_empty = '';
				if ($counter == 0)
				{
					$additional_empty = '<li><em>This user has not filled out their PC info!</em></li>';
				}
				$templating->set('additional_empty', $additional_empty);
				$templating->set('username', $profile['username']);
			}

			// gather latest articles
			$db->sqlquery("SELECT `article_id`, `title` FROM `articles` WHERE `author_id` = ? AND `admin_review` = 0 AND `active` = 1 ORDER BY `date` DESC LIMIT 5", array($profile['user_id']));
			if ($db->num_rows() != 0)
			{
				$templating->block('articles_top');
				while ($article_link = $db->fetch())
				{
					$templating->block('articles');

					$safe_title = $core->nice_title($article_link['title']);

					$templating->set('latest_article_link', "<a href=\"/articles/{$safe_title}.{$article_link['article_id']}\">{$article_link['title']}</a>");
				}
				$templating->block('articles_bottom');
				$templating->set('user_id', $profile['user_id']);
				$templating->set('username', $profile['username']);
			}


			if (!empty($profile['article_bio']))
			{
				$templating->block('bio');
				$templating->set('bio_text', bbcode($profile['article_bio']));
			}

			// comments block
			$templating->block('article_comments_list');

			$comment_posts = '';
			$db->sqlquery("SELECT comment_id, c.`comment_text`, c.`article_id`, c.`time_posted`, a.`title`, a.comment_count, a.active FROM `articles_comments` c INNER JOIN `articles` a ON c.article_id = a.article_id WHERE a.active = 1 AND c.author_id = ? ORDER BY c.`comment_id` DESC limit 5", array($_GET['user_id']));
			$comments_execute = $db->fetch_all_rows();
			foreach ($comments_execute as $comments)
			{
				$date = $core->format_date($comments['time_posted']);
				$title = $comments['title'];

				$comment_posts .= "<li class=\"list-group-item\">
			<a href=\"/articles/{$core->nice_title($comments['title'])}.{$comments['article_id']}/comment_id={$comments['comment_id']}\">{$title}</a>
			<div>".substr(strip_tags(bbcode($comments['comment_text'])), 0, 63)."&hellip;</div>
			<small>{$date}</small>
		</li>";
			}

			$templating->set('comment_posts', $comment_posts);

			//Do not show end block if it's empty
			if ($user->check_group(1,2))
			{
				$templating->block('end');

				$admin_links = "<form method=\"post\" action=\"/admin.php?module=users&user_id={$profile['user_id']}\">
				<button type=\"submit\" formaction=\"/admin.php?module=users&view=edituser&user_id={$profile['user_id']}\" class=\"btn btn-primary\">Edit User</button>
				<button name=\"act\" value=\"ban\" class=\"btn btn-danger\">Ban User</button>
				<button name=\"act\" value=\"totalban\" class=\"btn btn-danger\">Ban User & Ban IP</button>
				<input type=\"hidden\" name=\"ip\" value=\"{$profile['ip']}\" />";

				if ($profile['banned'] == 1)
				{
					$admin_links .= "&nbsp;&nbsp;
					<button name=\"act\" value=\"delete_user_content\"  class=\"btn btn-danger\">Delete user content</button>
					";
				}

				$admin_links .= "</form>";
				$templating->set('admin_links', $admin_links);
			}
		}
	}
}

else
{
	$core->message('No user id asked for to view! <a href="index.php">Click here to return</a>.');
}
