<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?

//Let's not run twice
if (($pid = cronHelper::lock()) !== FALSE) {

    //If this is being run as an upgrade
    $action = "";
    if (in_array("resume", $argv)) {
        $action = "resume";
    }
    $startid = 0;
    foreach( $argv as $arg) {
        if(is_numeric($arg)) {
            $startid = $arg;
            break;
        }
    }


    loggit(3, "Building search maps...");
    echo "Building search maps...\n";
    $tstart = time();

    echo "Ignore: " . print_r($cg_search_map_ignore, TRUE) . "\n";

    //Get all of the feed items in the system
    $count = 0;
    $items = get_all_feed_item_ids();
    $icount = count($items);
    echo "Got: [$icount] items...\n";

    //Loop and map each item into the search tables
    foreach ($items as $itm) {
        if ($action == "resume" && $startid != 0) {
            if($itm < $startid) continue;
            echo "Skipping item [$itm] cause resume.";
        }

        echo "Mapping item($itm): [$count / $icount].\n";

        $item = get_feed_item($itm);

        $host = parse_url($item['url'], PHP_URL_HOST);
        map_feed_item($item['id'], $item['title'] . " " . $item['description'] . " " . $host);

        if ($count % 100 == 0) {
            $sofar = time() - $tstart;
            echo " [$sofar] seconds elapsed so far.\n";
        }

        unset($item);

        $count++;
    }

    //Build search word counts
    echo "Calculating word maps.\n";
    calculate_map_word_counts();
    calculate_map_word_today_counts();

    //Calculate how long it took
    $took = time() - $tstart;
    echo "It took: [$took] seconds to build search maps.\n";
    loggit(1, "It took: [$took] seconds to build search maps.");

    //Release the lock
    cronHelper::unlock();
}
exit(0);