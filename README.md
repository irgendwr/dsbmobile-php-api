# dsbmobile-php-api
Just a quick php api I made for [dsbmobile](https://www.dsbmobile.de) because they don't provide one, nothing fancy.

Requirements: curl

### Example
```
<?php
include("dsb_api.php");

$timetableurl = DSB::getTopicChildUrl(0, 0, "DSB-Username", "DSB-Password");

if ($timetableurl == false) {
    echo "<center><h2>DSB Api Error</h2></center>";
} else {
    echo file_get_contents($timetableurl);
}
?>
```

With the two Numeric Variables you can select the "Table" and the Child.
