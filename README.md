# dsbmobile-php-api
This library lets you access content from [DSBmobile](https://www.dsbmobile.de) in php.

Requirements:
- PHP 5.4.x or newer
- curl

### Example
```php
<?php
include('path/to/dsb_api.php');

// log in
$dsb = new DsbAccount('username', 'password');

// get the url of the first element of the first topic
$timetableUrl = $dsb->getTopic(0)->getChild(0)->getUrl();

if ($timetableUrl) {
    echo '<a href="' . $timetableUrl . '">Timetable</a>';
} else {
    // an error occurred
    echo '<h2>DSB api error 🙁</h2>';
}
?>
```