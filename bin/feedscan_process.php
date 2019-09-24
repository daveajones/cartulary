<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?

//Track the time spent
$tstart = time();

//Get arguments from the command line
$force = FALSE;
if (in_array("force", $argv)) {
    $force = TRUE;
}
$feedurl = "";
foreach( $argv as $arg) {
    if(stripos($arg, 'http') === 0) {
        $feedurl = $arg;
    }
}
if( empty($feedurl) ) {
    loggit(2, "No feed specified on the command line: [".print_r($argv, TRUE)."].");
    echo "You must pass this script a feed url that starts with http(s)://...\n";
    exit(1);
}

//Sleep for a random amount of time to back off on the database connections
$sleeptime = mt_rand(1, 4);
//loggit(3, "DEBUG: Sleeping for: [$sleeptime] seconds.");
sleep( $sleeptime );

//Get the feed list
//Checking a single feed?
if (!empty($feedurl)) {
    $feed = get_feed_info(feed_exists($feedurl));
} else {
    loggit(2, "Feed not found in database: [$feedurl].");
    echo "Feed not found in database: [$feedurl].\n";
    exit(1);
}

// Walk the feed list and get new items in each
$ncount = 0;
$newitems = 0;

echo "Checking feed: [" . $feed['title'] . " | " . $feed['url'] . "]...\n";

//sleep(1);

//Make a timestamp
$fstart = time();

//Parse the feed and add new items to the database
//loggit(3, "Checking feed: [ $feedcount | " . $feed['title'] . " | " . $feed['url'] . "].");
$result = get_feed_items($feed['id'], NULL, $force);

if ($result == -1) {
    loggit(2, "Error getting items for feed: [" . $feed['title'] . " | " . $feed['url'] . "]");
    echo "    Error getting items for feed: [" . $feed['title'] . " | " . $feed['url'] . "]\n";
    $errorfeeds[] = $feed['url'];
} else if ($result == -2) {
    loggit(2, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] has no items.");
    echo "    Feed is empty.\n";
} else if ($result == -3) {
    loggit(1, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] is current.");
    //echo "    Feed is current.\n";
} else {
    loggit(1, "Feed: [" . $feed['title'] . " | " . $feed['url'] . "] updated.");
    echo "    Feed updated.\n";
    $ncount++;
    $newitems += $result;
}


//Calculate time took to scan
$ttime = time() - $tstart;
if($ttime > 1) {
    loggit(3, "It took: [$ttime] seconds to scan feed: [$feedurl]");
}
if($newitems > 0) {
    loggit(3, "Updated: [$ncount]. New items: [$newitems].");
}
echo "      It took " . (time() - $tstart) . " seconds to scan feed: [$feedurl].\n";
echo "Updated: [$ncount]. New items: [$newitems].\n";

exit(0);