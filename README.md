# Gamingonlinux.com code

The [Gamingonlinux](https://gamingonlinux.com) news site source code. This code is meant for GOL and isn't really suitable for use on other sites, nor was it designed with other sites in mind.

Please read first: The entire codebase is probably a mess, as it was started years ago and tons needs updating for standards, newer PHP versions etc. It was started as a learning experience for Liam and still is to this day. Remember this before making any smart-arsed comments ;) (helpful pointers, patches and so on highly appreciated!)

If you like what I do please consider [Patreon](https://www.patreon.com/liamdawe) or [Liberapay](https://liberapay.com/gamingonlinux/).

## Security issues

If you discover a security issue, PLEASE notify us privately first!

I will consider rewarding people who properly report security issues with games from their service of choice (while I have money, otherwise I will keep you in a list for when I have money to do so).

## Requirements

This site requires atleast PHP 7, Mysql 8 or MariaDB 10.2.2 and apache 2.4.  
It is also required to have the following php extentions available: 

- BC Math
- Curl
- GD
- Iconv
- Json
- Mysql

## PHP Settings

You should really have these set, to make cookies more secure:

- session.cookie_httponly = 1
- session.use_only_cookies = 1
- session.cookie_secure = 1 (If you have an SSL cert)

## Installing / Setting up a dev enviroment

Setup apache, PHP and MySQL to serve up PHP pages as with any other. 

Create `includes/config.php` with this (fill your details):

```
<?php
define("DB", 
[
    "DB_HOST_NAME" => "",
    "DB_USER_NAME" => "",
    "DB_PASSWORD" => "",
    "DB_DATABASE" => ""
]);
```
 
Import the development SQL database from the stripped SQL file `tools/SQL.sql`  

Eventually I will do an actual installer...

## Apache rewrite

You also need apache rewrite turned on like so:  

```
<Directory /your/local/site>
    Options Indexes FollowSymLinks MultiViews
    AllowOverride All
    Require all granted
</Directory>
```

And also adjust your htaccess "AccessFileName" to ".htaccess.testing" (as this doesn't include www. and secure site stuff you won't have locally), you can do so like this:  

```
<virtualhost>
    ServerName www.example.local
    DirectoryRoot /your/local/site
    AccessFileName .htaccess.testing
</virtualhost>
```

## User Groups

Leave the existing user group IDs 1-6 in place, removing them will cause issues. Consider those reserved for normal function.

## Code styling

I do not like shorthand PHP, I like it to be as descriptive and easy to understand as possible!

Please put braces on a new line, I think it looks cleaner and helps define different sections.

Indents: Use tabs (4 characters long) not spaces!

## Crons ##

Various cron jobs are needed to keep it all running smoothly, below are the files and suggested times:
- includes/crons/remove_temp_uploads.php (removes left-over temp tagline uploads, once a day)

## Licensing

The GOL site source is MIT licensed, but we also use other scripts which use different licenses:

- /includes/jscripts/select2/ (APACHE 2/GPL)

- /includes/jscripts/qTip2/ (MIT)

- /includes/jscripts/Pikaday/ (MIT/BSD)

- /includes/jscripts/fancybox/ (Creative Commons Attribution-NonCommercial 3.0)

- /includes/jscripts/jquery.form.min.js (MIT/GPL)

- /includes/jscripts/clipboard (https://clipboardjs.com/ MIT)

- /includes/jscripts/jquery.countdown.min.js (http://hilios.github.io/jQuery.countdown/ MIT)

- /includes/jscripts/moment/ (http://momentjs.com/timezone/ MIT)

- Gallery avatars (https://openclipart.org/ Public Domain)

- /includes/jscripts/Chart.min.js (http://www.chartjs.org/ MIT)

- /includes/image_class/ (https://github.com/claviska/SimpleImage MIT)

- /includes/datatables/ (https://datatables.net/ MIT)

- /includes/jscripts/cocoen/ (https://github.com/koenoe/cocoen MIT)

- /includes/jscripts/sorttable.min.js (https://github.com/stuartlangridge/sorttable MIT)

## Misc

If you want to know what you can put in a chart.js tooltip:  
`str = JSON.stringify(tooltipItem, null, 4); // (Optional) beautiful indented output.
console.log(str); // Logs output to dev tools console.
alert(str); // Displays output using window.alert()`
