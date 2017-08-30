<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?

//Check for command line switch whatif
$doit = TRUE;
if (in_array("whatif", $argv) || in_array("-whatif", $argv) || in_array("--whatif", $argv) || in_array("/whatif", $argv)) {
    $doit = FALSE;
    echo "Test mode.\n\n";
    loggit(3, "Cleaning non-fqdn feed urls - TEST MODE.");
} else {
    loggit(3, "Cleaning non-fqdn feed urls.");
}
echo "Cleaning non-fqdn feed urls from the database.\n";

$allfeeds = get_all_feeds(NULL, TRUE, TRUE);
$count = 0;
foreach($allfeeds as $feed) {
    $fi   = get_feed_info($feed['id']);
    $id   = $feed['id'];
    $url  = $fi['url'];
    $link = $fi['link'];

    //handle "view-source:" feeds
    if(stripos($url, 'view-source:') === 0) {
        echo "Feed: [$id] has a 'view-source' url: [$url]\n";
        echo "  Stripping... ";
        $new = substr($url, 12);
        if($doit) update_feed_url($id, $new);
        echo " to [$new].\n\n";
        loggit(3, "Changing feed: [$id] with 'view-source' url: [$url] to [$new]");
        $count++;
    } else

    //handle feeds with invalid url and link first by removing them
    if(stripos($url, 'http') !== 0 && stripos($link, 'http') !== 0) {
        echo "Feed: [$id] has invalid url: [$url] and link: [$link].\n";
        echo "  Removing...";
        if($doit) delete_feed($id);
        echo " Done.\n\n";
        loggit(3, "Deleted feed: [$id] because it had an invalid url: [$url] and invalid link: [$link].\n");
        $count++;
    } else

    //now handle feeds with just invalid urls by tacking the url onto
    //the end of the link
    if(stripos($url, 'http') !== 0 && stripos($link, 'http') === 0) {
        echo "Feed: [$id] has invalid url: [$url] but valid link: [$link].\n";

        $purl = parse_url($link);
        $newlink = $purl['scheme']."://".$purl['host'];

        $newurl  = ltrim($url, '/ ');
        echo "  Changing...";
        $new = $newlink."/".$newurl;
        if($doit) update_feed_url($id, $new);
        echo "  to [$new].\n";
        loggit(3, "Changing feed: [$id] url: [$url] to: [$new] because it was invalid.");

        //now check if it returns a valid feed. if not, remove it.
        echo "  Now checking feed: [$id] if we can get good content.\n";
        echo "  -----------------------------------------\n";
        $content = fetchFeedUrl($new);
        if(!is_feed($content)) {
            echo "  Deleting feed: [$id] becuase we could not reconstruct a valid url for it.\n";
            if($doit) delete_feed($id);
            loggit(3, "Deleted feed: [$id] because we could not reconstruct a valid url for it.");
        } else {
            echo "  Feed: [$id] is now fixed and validated with new url: [$new].";
            loggit(3, "Feed: [$id] is now fixed and validated with new url: [$new].");
        }
        echo "\n";
        $count++;
    } else {

        //If feed has more than 4000 errors and lastnewitem is over 1 month old delete the feed


    }
}


if($count > 0) {
    echo "Fixed: [$count] feeds.\n";
    loggit(3, "Fixed: [$count] feeds.");
} else {
    echo "All feed urls seem fully qualified.\n";
    loggit(3, "All feed urls seem fully qualified.");
}