# dsbmobile-php-api
> This library lets you access content from [DSBmobile](https://www.dsbmobile.de) in php.

[![Documentation](https://img.shields.io/badge/dsbmobile--php--api-docs-blue.svg)](https://irgendwr.github.io/dsbmobile-php-api/)
[![Travis](https://travis-ci.org/irgendwr/dsbmobile-php-api.svg)](https://travis-ci.org/irgendwr/dsbmobile-php-api)

### Requirements
- PHP 5.4.x or newer
- curl module for php (on debian/ubuntu: `apt install php7.0-curl`)

### Example
```php
<?php
include('path/to/dsb_api.php');

// log in
$dsb = new DsbAccount('username', 'password');

// get the url of the first element of the first topic
$timetableUrl = $dsb->getTopic()->getItem(0)->getUrl();

if ($timetableUrl) {
    echo '<a href="' . $timetableUrl . '">Timetable</a>';
} else {
    // an error occurred
    echo '<h2>DSB api error 🙁</h2>';
}
?>
```
