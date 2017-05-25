<?php
use Interop\Container\ContainerInterface;


return array(
	mysql::class => function (ContainerInterface $c) {
		$db_conf = include APP_ROOT . '/includes/config.php';
		return new mysql($db_conf['host'], $db_conf['username'], $db_conf['password'], $db_conf['database']);
	},
	db_mysql::class => function (ContainerInterface $c) {
		$db_conf = include APP_ROOT . '/includes/config.php';
		return new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password'], "");
	},
	template::class => function(ContainerInterface $c){
		$core = $c->get(core::class);
		return new template($core, $core->config('template'));
	},
	// model_base::class => function(ContainerInterface $c){
	// 	return new template($core, $core->config('template'));
	// },
);