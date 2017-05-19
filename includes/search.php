<?php
//########################################################################################
// API for general utility type functions
//########################################################################################


//Search for river items that match the query for this user
function search2_feed_items($uid = NULL, $query = NULL, $max = NULL, $withopml = FALSE)
{
    //Check parameters
    if ($uid == NULL) {
        loggit(2, "The user id given is corrupt or blank: [$uid]");
        return (FALSE);
    }
    if ($query == NULL) {
        loggit(2, "The query given is corrupt or blank: [$query]");
        return (FALSE);
    }


    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Convert the query words to ids
    $having = 0;
    $include = array();
    $exclude = array();
    foreach ( $query['like'] as $like ) {
        $wordid = map_get_word_id($like);
        if( $wordid > -1 ) {
            $include[] = $wordid;
        }
    }
    foreach ( $query['not'] as $not ) {
        $wordid = map_get_word_id($not);
        if( $wordid > -1 ) {
            $exclude[] = $wordid;
        }
    }

    //Check for queries that ended with no valid (i.e. non-stop) word words
    if( empty($include) ) {
        loggit(2, "All search terms were excluded by stop filter.");
        return(FALSE);
    }

    //We search by doing the equivelant of a river build, but we limit based on the query text
    $sqltxt = "SELECT mapcat.nfitemid,items.title,items.description,items.url,items.timeadded
               FROM $table_nfitem_map_catalog mapcat
               JOIN $table_nfitem items ON mapcat.nfitemid=items.id
               WHERE";

    foreach( $include as $inc ) {
        if($having == 0) {
            $sqltxt .= " ( ";
        }
        if($having > 0) {
            $sqltxt .= " OR ";
        }
        $sqltxt .= " wordid=$inc";
        $having++;
    }
    if($having > 0) {
        $sqltxt .= " ) ";
    }

    foreach( $exclude as $exc ) {
        $sqltxt .= " AND wordid!=$exc ";
    }

    //Group the results
    $sqltxt .= " GROUP BY mapcat.nfitemid";

    //Restrict to only the matched item count
    if( $having != 0 ) {
        $sqltxt .= " HAVING COUNT(*) = $having";
    }

    //Sort by date
    $sqltxt .= " ORDER BY items.timeadded DESC";

    //Limits
    if ( !empty($max) && is_numeric($max) ) {
        $sqltxt .= " LIMIT $max";
    } else {
        $sqltxt .= " LIMIT 30";
    }

    loggit(3, "SEARCH_V2: [$sqltxt]");
    $sql = $dbh->prepare($sqltxt) or loggit(2, "MySql error: " . $dbh->error);

    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);

    //See if there were any results
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No feed items returned for user: [$uid] with given criteria.");
        return (FALSE);
    }

    $sql->bind_result($id, $title, $description, $url, $timeadded) or loggit(2, "MySql error: " . $dbh->error);

    $nfitems = array();
    $count = 0;
    while ($sql->fetch()) {
        if (empty($title)) {
            $title = $description;
        }
        $nfitems[$count] = array('id' => $id, 'title' => $title, 'url' => $url);
        if($withopml) {
            $nfitems[$count]['description'] = $description;
            $nfitems[$count]['timeadded'] = $timeadded;
        }
        $count++;
    }

    $sql->close();

    if($withopml) {
        $s3url = build_opml_nfitem_feed($uid, $max, FALSE, $nfitems, FALSE, "search/riversearch", TRUE, "River search results: [".$query['flat']."]");
        loggit(3, "OPMLURL: $s3url");
        if(is_string($s3url)) {
            $nfitems['opmlurl'] = $s3url;
            loggit(3, "OPMLURL: ".$nfitems['opmlurl']);
        }
    }

    loggit(1, "Returning: [$count] newsfeed items for user: [$uid]");
    return ($nfitems);
}


//Find a word in the map table and get it's id
function map_get_word_id($word = NULL)
{
    //Check parameters
    if (empty($word)) {
        loggit(2, "The map word is blank or corrupt: [$word]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Find the word
    $stmt = "SELECT id FROM $table_nfitem_map WHERE word=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $word) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "The word: [$word] does not exist.");
        return (-1);
    }
    $sql->bind_result($wid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close();

    loggit(1,"Word: [$word] exists with id: [$wid].");
    return ($wid);
}