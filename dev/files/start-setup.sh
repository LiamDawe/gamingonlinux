#!/bin/bash
if [ ! -f /usr/local/lib/php/extensions/no-debug-non-zts-20160303/pdo_mysql.so ]; then
	docker-php-ext-install pdo_mysql;
fi

exec php -f /var/www/html/dev/setup.php;
