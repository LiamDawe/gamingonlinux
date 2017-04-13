# Gamingonlinux.com code

The [Gamingonlinux](https://gamingonlinux.com) news site source code.

Please read first: The entire codebase is probably a mess, as it was started years ago and tons needs updating for standards, newer PHP versions etc. It was started as a learning experience for Liam and still is to this day. Remember this before making any smart-arsed comments ;) (helpful pointers appreciated!)

If you like what I do please consider [supporting me on Patreon](https://www.patreon.com/liamdawe).

## Security issues

If you discover a security issue, PLEASE notify us privately first!

I will consider rewarding people who properly report security issues with games from their service of choice (while I have money, otherwise I will keep you in a list for when I have money to do so).

## Requirements

This site requires atleast PHP 7, Mysql 5.6 or MariaDB 10.0 and apache 2.4.  
It is also recommended to have the following php extentions available: 

- Curl
- Mysql
- GD
- Json

## Setting up a dev enviroment

Setup apache, PHP and MySQL to serve up PHP pages as with any other. Adjust `includes/config.php` as needed for your Mysql installation.  
Import the development SQL database from the stripped SQL file `tools/SQL.sql`  

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

- /includes/E_PDOStatement.php (https://github.com/noahheck/E_PDOStatement APACHE 2) 

- /includes/jscripts/clipboard (https://clipboardjs.com/ MIT)

- /includes/jscripts/jquery.countdown.min.js (http://hilios.github.io/jQuery.countdown/ MIT)

- /includes/jscripts/moment/ (http://momentjs.com/timezone/ MIT)

- Gallery avatars (https://openclipart.org/ Public Domain)

- /includes/jscripts/Chart.min.js (http://www.chartjs.org/ MIT)

## Misc

If you want to know what you can put in a chart.js tooltip:  
`str = JSON.stringify(tooltipItem, null, 4); // (Optional) beautiful indented output.
console.log(str); // Logs output to dev tools console.
alert(str); // Displays output using window.alert()`
