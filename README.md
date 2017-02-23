# dsbmobile-php-api
Just a quick php api I made for [dsbmobile](https://www.dsbmobile.de) because they don't provide one, nothing fancy.

Requirements: curl

### Example
```
<?php
include("dsb_api.php");

// get the first element of the first topic
$timetableurl = DSB::getTopicChildUrl(0, 0, "DSB-username", "DSB-password");

if ($timetableurl == false) {
    // an error occurred
    echo "<center><h2>DSB Api Error</h2></center>";
} else {
    // get and output the html code
    echo file_get_contents($timetableurl);
}
?>
```

The two integers passed to `getTopicChildUrl()` determine the category/topic (should be zero in most cases) and which child should be returned.

![DSB API example](https://i.sandstorm-projects.de/dsb-api-example)
