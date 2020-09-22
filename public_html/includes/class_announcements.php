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
}
?>