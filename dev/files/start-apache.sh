#!/bin/bash

if [ ! -f /var/lib/apache2/module/enabled_by_admin/rewrite ]; then
	a2enmod rewrite;
fi
if [ ! -f /usr/local/lib/php/extensions/no-debug-non-zts-20160303/pdo_mysql.so ]; then
	docker-php-ext-install pdo_mysql;
fi

exec "apache2-foreground";