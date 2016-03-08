<?php
$templating->set_previous('title', 'Submit Sale', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com submit game sale form', 1);

$templating->merge('submit_sale');
$templating->block('submit');
$captcha = '';
if ($_SESSION['user_group'] == 4)
{
	$captcha = '<div class="g-recaptcha" data-sitekey="6LcT0gATAAAAAOAGes2jwsVjkan3TZe5qZooyA-z"></div>';
}
$templating->set('captcha', $captcha);

if($config['pretty_urls'] == 1) {
	$form_action = '/sales/';
}
else {
	$form_action = $config['path'] . 'sales.php';
}

$templating->set('form_action', $form_action);

$provider_jump_list = '';
$db->sqlquery("SELECT `provider_id`, `name` FROM `game_sales_provider` ORDER BY `name` ASC");
while ($provider_query = $db->fetch())
{
	if (isset($_GET['search']) && in_array($provider_query['provider_id'], $_GET['stores']))
	{
		$provider_jump_list .= "<option value=\"{$provider_query['provider_id']}\" selected>{$provider_query['name']}</option>";
	}
	else
	{
		$provider_jump_list .= "<option value=\"{$provider_query['provider_id']}\">{$provider_query['name']}</option>\r\n";
	}
}
$templating->set('providers_list', $provider_jump_list);
