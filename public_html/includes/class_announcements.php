<?php
class announcements
{	
	function __construct($core, $dbl, $user)
	{
		$this->core = $core;
		$this->dbl = $dbl;
		$this->user = $user;
	}

	function get_announcements()
	{
		$announcement_return = [];
		
		$get_announcements = unserialize($this->core->get_dbcache('index_announcements'));
		if ($get_announcements === false) // there's no cache
		{
			$get_announcements = $this->dbl->run("SELECT `id`, `text`, `user_groups`, `type`, `modules`, `can_dismiss` FROM `announcements` ORDER BY `id` DESC")->fetch_all();
			$this->core->set_dbcache('index_announcements', serialize($get_announcements));  // no need for a cache, don't update often
		}

		if (isset($_SESSION) && isset($_SESSION['user_id']))
		{
			$pcinfo_announce = $this->check_old_pc_info($_SESSION['user_id']);
			if (!empty($pcinfo_announce))
			{
				$get_announcements = array_merge($pcinfo_announce, $get_announcements);
			}
		}

		if ($get_announcements)
		{
			$random_item = array_rand($get_announcements, 1);
		
			if (!isset($_COOKIE['gol_announce_'.$get_announcements[$random_item]['id']]))
			{
				$show = 0;
					
				// one to show to everyone (generic announcement)
				if ((empty($get_announcements[$random_item]['user_groups']) || $get_announcements[$random_item]['user_groups'] == NULL) && (empty($get_announcements[$random_item]['modules']) || $get_announcements[$random_item]['modules'] == NULL))
				{
					$show = 1;
				}
				// otherwise, we need to do some checks
				else
				{
					$module_show = 0;
					$group_show = 0;
						
					// check if the currently loaded module is allow to show it
					if (!empty($get_announcements[$random_item]['modules'] && $get_announcements[$random_item]['modules'] != NULL))
					{
						$modules_array = unserialize($get_announcements[$random_item]['modules']);
							
						if (in_array(core::$current_module['module_id'], $modules_array))
						{
							$module_show = 1;
						}
					}
					else
					{
						$module_show = 1;
					}
						
					// check their user group against the setting
					if (!empty($get_announcements[$random_item]['user_groups'] && $get_announcements[$random_item]['user_groups'] != NULL))
					{
						$group_ids_array = unserialize($get_announcements[$random_item]['user_groups']);
							
						// if this is to only be shown to specific groups, is the user in that group?
						if ($get_announcements[$random_item]['type'] == 'in_groups' && $this->user->check_group($group_ids_array) == true)
						{
							$group_show = 1;				
						}
							
						// if it's to only be shown if they aren't in those groups
						if ($get_announcements[$random_item]['type'] == 'not_in_groups' && $this->user->check_group($group_ids_array) == false)
						{
							$group_show = 1;			
						}
					}
					else
					{
						$group_show = 1;	
					}
				}
					
				if ($show == 1 || ($module_show == 1 && $group_show == 1))
				{
					$announcement_return['text'] = $get_announcements[$random_item]['text'];
					
					$dismiss = '';
					if ($get_announcements[$random_item]['can_dismiss'] == 1)
					{
						$dismiss = '<span class="fright"><a href="#" class="remove_announce" title="Hide Announcement" data-announce-id="'.$get_announcements[$random_item]['id'].'">&#10799;</a></span>';
					}
					$announcement_return['dismiss'] = $dismiss;
				}
			}
		}

		return $announcement_return;
	}

	function check_old_pc_info($user_id)
	{
		$announcements = [];
		if (isset($user_id) && $user_id != 0)
		{
			$checker = $this->dbl->run("SELECT `date_updated` FROM `user_profile_info` WHERE `user_id` = ?", array($user_id))->fetch();

			if ($checker && $checker['date_updated'] != NULL)
			{
				$minus_4months = strtotime('-4 months');

				if (strtotime($checker['date_updated']) < $minus_4months)
				{
					if (!isset($_COOKIE['gol_announce_pc_info']))
					{
						$announcements['pc_info']['text'] = 'You haven\'t updated your PC information in over 4 months! <a href="/usercp.php?module=pcinfo">Click here to go and check</a>. You can simply update if nothing has changed to be included in our statistics!';
						$announcements['pc_info']['can_dismiss'] = 1;
						$announcements['pc_info']['id'] = 'pc_info';
					}
				}
			}
		}
		return $announcements;
	}
}
?>