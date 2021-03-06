<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: admin blocks config.');
}

if (!$user->check_group(1))
{
	$core->message("You do not have permission to access this page!");
}

else
{
	$templating->load('admin_modules/admin_module_blocks');

	if (!isset($_POST['act']) && isset($_GET['view']))
	{
		if ($_GET['view'] == 'add')
		{
            $templating->block('add', 'admin_modules/admin_module_blocks');

            $editor = new editor($core, $templating, $bbcode);
            $editor->article_editor(['content' => '']);

			$templating->block('add_bottom', 'admin_modules/admin_module_blocks');

			if (!isset($_GET['usercp']))
			{
				$templating->set('type', '');
			}

			else
			{
				$templating->set('type', 'usercp');
			}
		}

		if ($_GET['view'] == 'manage')
		{
			$templating->block('manage_top');
			if (!isset($_GET['usercp']))
			{
				$templating->set('type', '');
			}

			else
			{
				$templating->set('type', 'usercp');
			}

			if (!isset($_GET['usercp']))
			{
				$get_blocks = $dbl->run("SELECT * FROM `blocks` ORDER BY `order` ASC")->fetch_all();
			}

			else
			{
				$get_blocks = $dbl->run("SELECT * FROM `usercp_blocks` ORDER BY `order` ASC")->fetch_all();
			}

			foreach ($get_blocks as $blocks)
			{
				if ($blocks['block_link'] != NULL)
				{
					$templating->block('normal_row');

					if (!isset($_GET['usercp']))
					{
						$templating->set('type', '');
					}

					else
					{
						$templating->set('type', '&amp;usercp');
					}

					$templating->set('block_name', $blocks['block_name']);
					$templating->set('block_title', $blocks['block_title']);
					$templating->set('link', $blocks['block_title_link']);
					$templating->set('block_link', $blocks['block_link']);

					$checked = '';
					if ($blocks['activated'] == 1)
					{
						$checked = 'checked';
					}

					$templating->set('checked', $checked);

					$templating->set('block_id', $blocks['block_id']);
				}

				else
				{
					$templating->block('custom_row', 'admin_modules/admin_module_blocks');

					if (!isset($_GET['usercp']))
					{
						$templating->set('type', '');
					}

					else
					{
						$templating->set('type', '&amp;usercp');
					}

					$templating->set('block_name', $blocks['block_name']);
					$templating->set('block_title', $blocks['block_title']);
                    $templating->set('link', $blocks['block_title_link']);
                    
                    $editor = new editor($core, $templating, $bbcode);
                    $editor->article_editor(['content' => $blocks['block_custom_content']]);

					$templating->block('custom_row_bottom', 'admin_modules/admin_module_blocks');

					$block_selected = '';
					if ($blocks['style'] == 'block')
					{
						$block_selected = 'selected';
					}

					$block_plain = '';
					if ($blocks['style'] == 'block_plain')
					{
						$block_plain = 'selected';
					}

					$options = "<option value=\"block\" $block_selected>Standard style</option><option value=\"block_plain\" $block_plain>No style</option>";
					$templating->set('options', $options);

					$checked = '';
					if ($blocks['activated'] == 1)
					{
						$checked = 'checked';
					}

					$templating->set('checked', $checked);

					$nonpremium = '';
					if ($blocks['nonpremium_only'] == 1)
					{
						$nonpremium = 'checked';
					}
					$templating->set('nonpremium_check', $nonpremium);

					$homepage = '';
					if ($blocks['homepage_only'] == 1)
					{
						$homepage = 'checked';
					}

					$templating->set('homepage_check', $homepage);

					$templating->set('block_id', $blocks['block_id']);
				}
			}
		}
	}

	else if (isset($_POST['act']) && !isset($_GET['view']))
	{
		if ($_POST['act'] == 'Add')
		{
			$title = trim($_POST['title']);
			$text = trim($_POST['text']);

			// check empty
			if (empty($_POST['name']) || empty($text))
			{
				$core->message("You must fill out all text fields!");
			}

			else
			{
				// check if activated
				$activated = 0;
				if (isset($_POST['activated']))
				{
					$activated = 1;
				}

				// check if activated
				$nonpremium = 0;
				if (isset($_POST['nonpremium']))
				{
					$nonpremium = 1;
				}

				// check if activated
				$homepage = 0;
				if (isset($_POST['homepage']))
				{
					$homepage = 1;
				}

				// check if its a main or usercp block
				$type = '';
				if ($_POST['type'] == 'usercp')
				{
					$type = 'usercp_';
				}

				// get last order
				$get_order = $dbl->run("SELECT `order` FROM `blocks` ORDER BY `order` DESC LIMIT 1")->fetch();

				$new_order = $get_order['order'] + 1;

				// create block
				$dbl->run("INSERT INTO `{$type}blocks` SET `block_name` = ?, `block_title` = ?, `block_title_link` = ?, `activated` = ?, `block_custom_content` = ?, `style` = ?, `nonpremium_only` = ?, `homepage_only` = ?, `order` = ?", array($_POST['name'], $title, $_POST['link'], $activated, $text, $_POST['style'], $nonpremium, $homepage, $new_order));

				// note who did it
				$core->new_admin_note(array('completed' => 1, 'content' => ' added a new website sidebar block named: '.$_POST['name'].'.'));

                $blocks = $dbl->run('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`')->fetch_all();
                $core->set_dbcache('index_blocks', serialize($blocks)); // no expiry as shown blocks hardly ever changes

                $_SESSION['message'] = 'saved';
                $_SESSION['message_extra'] = 'block';
                header("Location: /admin.php?module=blocks&view=manage&{$_POST['type']}");
                die();
            }
		}

		if ($_POST['act'] == 'addmain')
		{
			if (empty($_POST['name']) || empty($_POST['file']))
			{
				$core->message("You have to fill in a title and filename!", 1);
			}

			else
			{
				// check if activated
				$activated = 0;
				if (isset($_POST['activated']))
				{
					$activated = 1;
				}

				// check if its a main or usercp block
				$type = '';
				if ($_POST['type'] == 'usercp')
				{
					$type = 'usercp_';
				}

				// get last in order to add 1
				$order = $dbl->run("SELECT `order` FROM `{$type}blocks` ORDER BY `order` DESC LIMIT 1")->fetch();

				$new_order = $order['order'] + 1;

				// create block
				$dbl->run("INSERT INTO `{$type}blocks` SET `block_name` = ?, `block_title` = ?, `block_link` = ?, `activated` = ?, `order` = ?", array($_POST['name'], $_POST['name'], $type . "block_" . $_POST['file'], $activated, $new_order));

				$blocks = $dbl->run('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`')->fetch_all();
				core::$redis->set('index_blocks', serialize($blocks)); // no expiry as shown blocks hardly ever changes

				// note who did it
                $core->new_admin_note(array('completed' => 1, 'content' => ' added a new website sidebar block named: '.$_POST['name'].'.'));
                
                $blocks = $dbl->run('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`')->fetch_all();
                $core->set_dbcache('index_blocks', serialize($blocks)); // no expiry as shown blocks hardly ever changes

                $_SESSION['message'] = 'saved';
                $_SESSION['message_extra'] = 'block';
                header("Location: /admin.php?module=blocks&view=manage&{$_POST['type']}");
                die();
			}
		}

		if ($_POST['act'] == 'Update')
		{
			if ($_POST['type'] == 'normal')
			{
				// make safe
				$name = trim($_POST['name']);
				$title = trim($_POST['title']);
				$id = $_POST['block_id'];

				if (!is_numeric($id))
				{
					$core->message("Block ID was not a number!");
				}

				// check empty
				else if (empty($name) || empty($title) || empty($_POST['filename']))
				{
					$core->message("You must fill out all text fields!");
				}

				else
				{
					$activated = 0;
					if (isset($_POST['activated']))
					{
						$activated = 1;
					}

					// Check if it's a user control panel block or not
					$usercp = '';
					$usercp_link = '';
					if (isset($_GET['usercp']))
					{
						$usercp = 'usercp_';
						$usercp_link = '&amp;usercp';
					}

					// update
					$dbl->run("UPDATE `{$usercp}blocks` SET `block_name` = ?, `block_title` = ?, `block_title_link` = ?, `block_link` = ?, `activated` = ? WHERE `block_id` = ?", array($name, $title, $_POST['link'], $_POST['filename'], $activated, $id));

					// note who did it
                    $core->new_admin_note(array('completed' => 1, 'content' => ' updated the sidebar block named: '.$name.'.'));
                    
                    $blocks = $dbl->run('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`')->fetch_all();
                    $core->set_dbcache('index_blocks', serialize($blocks)); // no expiry as shown blocks hardly ever changes

                    $_SESSION['message'] = 'saved';
                    $_SESSION['message_extra'] = 'block';
                    header("Location: /admin.php?module=blocks&view=manage&{$_POST['type']}");
                    die();
				}
			}

			if ($_POST['type'] == 'custom')
			{
				$title = trim($_POST['title']);
				$text = trim($_POST['text']);
				$id = $_POST['block_id'];

				if (!is_numeric($id))
				{
					$core->message("Block ID was not a number! This is likely an error, let Liam know!");
				}

				// check empty
				else if (empty($_POST['name']) || empty($text))
				{
					$core->message("You must fill out all text fields!");
				}

				else
				{
					$activated = 0;
					if (isset($_POST['activated']))
					{
						$activated = 1;
					}

					// check if activated
					$nonpremium = 0;
					if (isset($_POST['nonpremium']))
					{
						$nonpremium = 1;
					}

					// check if activated
					$homepage = 0;
					if (isset($_POST['homepage']))
					{
						$homepage = 1;
					}

					// Check if it's a user control panel block or not
					$usercp = '';
					$usercp_link = '';
					if (isset($_GET['usercp']))
					{
						$usercp = 'usercp_';
						$usercp_link = '&amp;usercp';
					}

					// update
					$dbl->run("UPDATE `{$usercp}blocks` SET `block_name` = ?, `block_title` = ?, `block_title_link` = ?, `block_custom_content` = ?, `activated` = ?, `style` = ?, `nonpremium_only` = ?, `homepage_only` = ? WHERE `block_id` = ?", array($_POST['name'], $title, $_POST['link'], $text, $activated, $_POST['style'], $nonpremium, $homepage, $id));

					// note who did it
					$core->new_admin_note(array('completed' => 1, 'content' => ' added a new website sidebar block named: '.$_POST['name'].'.'));

                    $blocks = $dbl->run('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`')->fetch_all();
                    $core->set_dbcache('index_blocks', serialize($blocks)); // no expiry as shown blocks hardly ever changes

                    $_SESSION['message'] = 'saved';
                    $_SESSION['message_extra'] = 'block';
                    header("Location: /admin.php?module=blocks&view=manage&{$_POST['type']}");
                    die();				
                }
			}
		}

		if ($_POST['act'] == 'Delete')
		{
			if (!isset($_POST['yes']) && !isset($_POST['no']))
			{
				// Check if it's a user control panel block or not
				$usercp = '';
				if (isset($_GET['usercp']))
				{
					$usercp = "&amp;usercp";
				}

				$core->confirmation(['title' => 'Are you sure you want to delete that block?', 'text' => 'This cannot be undone!', 'action_url' => "admin.php?module=blocks&amp;block_id={$_POST['block_id']}{$usercp}", 'act' => "Delete"]);
			}

			else if (isset($_POST['no']))
			{
				header("Location: admin.php?module=blocks&view=manage");
				die();
			}

			else if (isset($_POST['yes']))
			{
				// Check if it's a user control panel block or not
				$usercp = '';
				if (isset($_GET['usercp']))
				{
					$usercp = "usercp_";
				}

				// check id is set
				$id = $_GET['block_id'];
				if (!is_numeric($id))
				{
					$core->message('That is not a correct id!');
				}

				else
				{
					// check block exists
					$check_res = $dbl->run("SELECT `block_id`, `block_name` FROM `{$usercp}blocks` WHERE `block_id` = ?", array($id))->fetch();
					if (!$check_res)
					{
						$core->message('That is not a correct id!');
					}

					// Delete now
					else
					{
						$dbl->run("DELETE FROM `{$usercp}blocks` WHERE `block_id` = ?", array($id));

						if (!isset($_GET['usercp']))
						{
							$blocks = $dbl->run('SELECT `block_link`, `block_id`, `block_title_link`, `block_title`, `block_custom_content`, `style`, `nonpremium_only`, `homepage_only` FROM `blocks` WHERE `activated` = 1 ORDER BY `order`')->fetch_all();
							$core->set_dbcache('index_blocks', serialize($blocks)); // no expiry as shown blocks hardly ever changes
						}

						// note who did it
						$core->new_admin_note(array('completed' => 1, 'content' => ' deleted a website sidebar block named: '.$check_res['block_name'].'.'));

						$_SESSION['message'] = 'deleted';
						$_SESSION['message_extra'] = 'block';
						header("Location: admin.php?module=blocks&view=manage");
						die();
					}
				}
			}
		}
	}
}
