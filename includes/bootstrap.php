<?php
//Boostrap file

// Auto class loaders
require realpath(dirname(__FILE__) . "/../vendor/autoload.php");

require dirname(__FILE__) . "/loader.php";


// DI stuff
$builder = new \DI\ContainerBuilder();
$builder->useAnnotations(false);
$builder->addDefinitions(APP_ROOT . "/includes/di_config.php");
// $builder->setDefinitionCache(new Doctrine\Common\Cache\FilesystemCache(APP_ROOT . "/tmp/"));
// $builder->writeProxiesToFile(true, 'proxies');
$container = $builder->build();

DI::setup($container);

// Copy stuff from index.php and header.php here


$db  = $container->get(mysql::class);
$dbl = $container->get(db_mysql::class);

$core = $container->get(core::class);
define('url', $core->config('website_url'));

$message_map = new message_map();

$plugins = new plugins($dbl, $core, $file_dir);

$article_class = new article($dbl, $core, $plugins);

$bbcode = new bbcode($dbl, $core, $plugins);

// FIXME: go away
$mail_class = new PHPMailer();


