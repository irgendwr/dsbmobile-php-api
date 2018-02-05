# dsbmobile-php-api
> This library lets you access content from [DSBmobile](https://www.dsbmobile.de) in php.

[![Documentation](https://img.shields.io/badge/dsbmobile--php--api-docs-blue.svg)](https://irgendwr.github.io/dsbmobile-php-api/)
[![Travis](https://travis-ci.org/irgendwr/dsbmobile-php-api.svg)](https://travis-ci.org/irgendwr/dsbmobile-php-api)

### Requirements
- PHP 5.4.x or newer
- curl module for php (on debian/ubuntu: `apt install php7.0-curl`)

### Example
#### Basic example
```php
<?php
include('path/to/dsb_api.php');

// log in
$dsb = new DsbAccount('username', 'password');

// get the url of the first timetable
$timetableUrl = $dsb->getTimetables()->getItem(0)->getUrl();

if ($timetableUrl) {
    echo '<a href="' . $timetableUrl . '">Timetable</a>';
} else {
    // an error occurred
    echo '<h2>Something went wrong 🙁</h2>';
}
```

#### Displaying every timetable
```php
// get all timetables
$timetables = $dsb->getTimetables()->getItems();

// loop through every one of them
foreach ($timetables as $timetable) {
    // output their html code
    echo $timetable->getHTML();
}

// an error occurred 
if (count($timetables) == 0) {
    echo '<h2>Something went wrong 🙁</h2>';
}
```

A complete list of methods can be found in the [documentation](https://irgendwr.github.io/dsbmobile-php-api/).