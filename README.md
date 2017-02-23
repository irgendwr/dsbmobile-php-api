# dsbmobile-php-api
Just a quick php api I made for [dsbmobile](https://www.dsbmobile.de) because they don't provide one, nothing fancy.

Requirements: curl

### Example
```
<?php
include("dsb_api.php");

$timetableurl = DSB::getTopicChildUrl(0, 0, "DSB-username", "DSB-password");

if ($timetableurl == false) {
    echo "<center><h2>DSB Api Error</h2></center>";
} else {
    echo file_get_contents($timetableurl);
}
?>
```

The two integers passed to getTopicChildUrl determine the category (should be zero in most cases) and which child should be returned.

![DSB APi Example](https://i.sandstorm-projects.de/dsb-api-example)
