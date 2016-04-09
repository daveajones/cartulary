<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_bin_init.php" ?>
<?

//Check for command line switch whatif
$doit = TRUE;
if (in_array("whatif", $argv) || in_array("-whatif", $argv) || in_array("--whatif", $argv) || in_array("/whatif", $argv)) {
    $doit = FALSE;
    echo "Test mode.\n\n";
    loggit(3, "Cleaning duplicate feed urls - TEST MODE.");
} else {
    loggit(3, "Cleaning duplicate feed urls.");
}
echo "Cleaning duplicate feed urls from the database.\n";


//Connect to the database server
$dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

//Get all feeds with a low error count
$sqltxt = "SELECT nf.id,nf.url,nfc.userid FROM newsfeeds AS nf
           INNER JOIN (SELECT nf.id,nf.url FROM newsfeeds AS nf
           GROUP BY url HAVING count(url) > 1) AS dup ON nf.url = dup.url
           INNER JOIN nfcatalog AS nfc ON nfc.feedid = nf.id ORDER BY nf.url DESC";

$sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
$sql->execute() or loggit(2, "MySql error: " . $dbh->error);
$sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

//See if there were any feeds returned
if ($sql->num_rows() < 1) {
    $sql->close()
    or loggit(2, "MySql error: " . $dbh->error);
    loggit(2, "There are no duplicate feeds.");
    echo "  There are no duplicate feeds.\n";
} else {
    $sql->bind_result($id, $url, $uid) or loggit(2, "MySql error: " . $dbh->error);

    $feeds = array();
    $count = 0;
    $fcount = 0;
    $lasturl = '';
    while ($sql->fetch()) {
        //skip handling feeds that are not canonical
        if(stripos($url, 'http') !== 0) continue;

        //reset counters when feed url changes
        if($url !== $lasturl && $count > 0) {
            $count = 0;
            $fcount++;
        }
        $lasturl = $url;
        $feeds[$fcount][$count] = array('id' => $id, 'url' => $url, 'uid' => $uid);
        if(feed_is_sticky($id, $uid)) { $feeds[$fcount][$count]['sticky'] = TRUE; } else { $feeds[$fcount][$count]['sticky'] = FALSE; }
        if(feed_is_fulltext($id, $uid)) { $feeds[$fcount][$count]['fulltext'] = TRUE; } else { $feeds[$fcount][$count]['fulltext'] = FALSE; }
        if(feed_is_hidden($id, $uid)) { $feeds[$fcount][$count]['hidden'] = TRUE; } else { $feeds[$fcount][$count]['hidden'] = FALSE; }
        $count++;
    }
    $sql->close();

    $count = 0;
    foreach($feeds as $feed) {
        $addedto = array();
        $url = $feed[0]['url'];
        //skip handling feeds that are not canonical
        if(stripos($url, 'http') !== 0) continue;

        foreach($feed as $userfeed) {
            echo "DELETE FEED ID: [".$userfeed['id']."] URL: [".$userfeed['url']."]\n";
            if($doit) delete_feed($userfeed['id']);
            loggit(3, "DELETED FEED ID: [".$userfeed['id']."] URL: [".$userfeed['url']."] because it was a duplicate.");
        }
        foreach($feed as $userfeed) {
            if( !isset($addedto[$userfeed['uid']]) ) {
                $fid = feed_exists($url);
                if(!$fid) {
                    echo "  ADDING FEED: [".$url."] and linking to user: [".$userfeed['uid']."]\n";
                    $addedto[$userfeed['uid']] = TRUE;
                    if($doit) add_feed($url, $userfeed['uid']);
                    loggit(3, "ADDED FEED: [".$url."] linked to user: [".$userfeed['uid']."]");
                } else {
                    echo "  LINKING FEED: [".$url."] to user: [".$userfeed['uid']."]\n";
                    $addedto[$userfeed['uid']] = TRUE;
                    if($doit) link_feed_to_user($fid, $userfeed['uid']);
                    loggit(3, "LINKED FEED: [".$url."] to user: [".$userfeed['uid']."]");
                }
            }
            if($userfeed['sticky']) {
                echo "    MARKING FEED: [$url] as STICKY for user: [".$userfeed['uid']."]\n";
                if($doit) mark_feed_as_sticky(feed_exists($url), $userfeed['uid']);
                loggit(3, "MARKING FEED: [$url] as STICKY for user: [".$userfeed['uid']."]");
            }
            if($userfeed['hidden']) {
                echo "    MARKING FEED: [$url] as HIDDEN for user: [".$userfeed['uid']."]\n";
                if($doit) mark_feed_as_hidden(feed_exists($url), $userfeed['uid']);
                loggit(3, "MARKING FEED: [$url] as HIDDEN for user: [".$userfeed['uid']."]");
            }
            if($userfeed['fulltext']) {
                echo "    MARKING FEED: [$url] as FULLTEXT for user: [".$userfeed['uid']."]\n";
                if($doit) mark_feed_as_fulltext(feed_exists($url), $userfeed['uid']);
                loggit(3, "MARKING FEED: [$url] as FULLTEXT for user: [".$userfeed['uid']."]");
            }
        }

        echo print_r($addedto, TRUE)."\n";
    }
}

//Now delete any feeds that don't link to any user
//------------------------------------------------------------------------------------------------
//Connect to the database server
$dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

//Get all feeds with a low error count
$sqltxt = "SELECT nf.id,nf.url FROM newsfeeds AS nf
           INNER JOIN (SELECT nf.id,nf.url FROM newsfeeds AS nf
           GROUP BY url HAVING count(url) > 1) AS dup ON nf.url = dup.url
           ORDER BY nf.url DESC";

$sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);
$sql->execute() or loggit(2, "MySql error: " . $dbh->error);
$sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

//See if there were any feeds returned
if ($sql->num_rows() < 1) {
    $sql->close()
    or loggit(2, "MySql error: " . $dbh->error);
    loggit(2, "There are no duplicate, user-less feeds.");
    echo "  There are no duplicate, user-less feeds.\n";
    exit(0);
} else {
    $sql->bind_result($did, $durl) or loggit(2, "MySql error: " . $dbh->error);

    while ($sql->fetch()) {
        //skip handling feeds that are not canonical
        if(stripos($durl, 'http') !== 0) continue;

        echo "DELETING: $durl\n";
        if($doit) delete_feed($did);
        loggit(3, "DELETED: [$durl] because it wasn't linked to any user.");
    }
    $sql->close();
}