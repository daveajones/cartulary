<?php
//########################################################################################
// API for managing outlines and lists
//########################################################################################


//Check if a feed links to an outline
function get_feed_outline($fid = NULL, $uid = NULL)
{
    //Check parameters
    if(empty($fid)) {
        loggit(2,"The feed id is blank or corrupt: [$fid]");
        return(FALSE);
    }
    if(empty($uid)) {
        loggit(2,"The user id is blank or corrupt: [$uid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Look for the url in the feed table
    $sql=$dbh->prepare("SELECT outlineid FROM $table_nfcatalog WHERE userid=? AND feedid=?") or loggit(2, "MySql error: ".$dbh->error);
    $sql->bind_param("ss", $uid, $fid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $sql->store_result() or loggit(2, "MySql error: ".$dbh->error);
    //See if any rows came back
    if($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: ".$dbh->error);
        loggit(1,"The feed: [$fid] does not contain an outline link for user: [$uid].");
        return(FALSE);
    }
    $sql->bind_result($oid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->fetch() or loggit(2, "MySql error: ".$dbh->error);
    $sql->close();

    //loggit(1,"The feed: [$fid] links to outline: [$oid] for user: [$uid].");
    return($oid);
}


//Get a list of feeds owned by an outline
function get_feeds_by_outline_id($id = NULL)
{
    //Check parameters
    if($id == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$id]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';
    require_once "$confroot/$includes/opml.php";

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //See if this is a list or an outline
    $oi = get_outline_info($id);

    //Look for the url in the feed table
    if( $oi['type'] == 'list' ) {
        $sql=$dbh->prepare("SELECT DISTINCT feedid FROM $table_listfeeds WHERE listid=?") or loggit(2, "MySql error: ".$dbh->error);
        $sql->bind_param("s", $id) or loggit(2, "MySql error: ".$dbh->error);
    } else {
        $sql=$dbh->prepare("SELECT DISTINCT feedid FROM $table_nfcatalog WHERE outlineid=?") or loggit(2, "MySql error: ".$dbh->error);
        $sql->bind_param("s", $id) or loggit(2, "MySql error: ".$dbh->error);
    }
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $sql->store_result() or loggit(2, "MySql error: ".$dbh->error);

    //See if any rows came back
    $subcount = $sql->num_rows();

    //Put the feed ids in an array to send back
    $sql->bind_result($feedid) or loggit(2, "MySql error: ".$dbh->error);
    $feeds = array();
    $count = 0;
    while($sql->fetch()){
        $feeds[$count] = $feedid;
        $count++;
    }

    $sql->close();

    loggit(1,"Returning: [$subcount] feeds linked to outline: [$id].");
    return($feeds);
}


//Mark an outline's feeds to purge
function mark_all_outline_feeds_to_purge($oid = NULL)
{
    //Check parameters
    if($oid == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET `purge`=1 WHERE outlineid=?";
    $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1,"Marked: [$updcount] feeds to purge for outline: [$oid].");
    return($updcount);
}


//Un-mark an outline's feeds to purge
function unmark_all_outline_feeds_to_purge($oid = NULL)
{
    //Check parameters
    if($oid == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET `purge`=0 WHERE outlineid=?";
    $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    loggit(1,"Un-marked: [$updcount] feeds to purge for outline: [$oid].");
    return($updcount);
}


//Mark an outline feed to purge
function mark_outline_feed_to_purge($oid = NULL, $fid = NULL)
{
    //Check parameters
    if($oid == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }
    if($fid == NULL) {
        loggit(2,"The feed id is blank or corrupt: [$fid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET `purge`=1 WHERE outlineid=? AND feedid=?";
    $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $oid, $fid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    if($updcount < 1) {
        loggit(2,"Failed to mark feed: [$fid] to purge for outline: [$oid].");
        return(FALSE);
    } else {
        loggit(1,"Marked feed: [$fid] to purge for outline: [$oid].");
        return(TRUE);
    }
}


//Un-mark an outline feed to purge
function unmark_outline_feed_to_purge($oid = NULL, $fid = NULL)
{
    //Check parameters
    if($oid == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }
    if($fid == NULL) {
        loggit(2,"The feed id is blank or corrupt: [$fid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Now that we have a good id, put the article into the database
    $stmt = "UPDATE $table_nfcatalog SET `purge`=0 WHERE outlineid=? AND feedid=?";
    $sql=$dbh->prepare($stmt) or loggit(3, $dbh->error);
    $sql->bind_param("ss", $oid, $fid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $updcount = $sql->affected_rows;
    $sql->close();

    //Log and return
    if($updcount < 1) {
        //loggit(2,"Failed to unmark feed: [$fid] to purge for outline: [$oid].");
        return(FALSE);
    } else {
        loggit(1,"Unmarked feed: [$fid] to purge for outline: [$oid].");
        return(TRUE);
    }
}


//Link a feed to an outline
function link_feed_to_outline($fid = NULL, $oid = NULL, $uid = NULL)
{
    //Check parameters
    if($fid == NULL) {
        loggit(2,"The feed id is blank or corrupt: [$fid]");
        return(FALSE);
    }
    if($oid == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }
    if($uid == NULL) {
        loggit(2,"The user id is blank or corrupt: [$uid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';
    $tstamp = time();

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Link the feed to the user
    $stmt = "INSERT INTO $table_nfcatalog (userid, feedid, outlineid, linkedon) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE outlineid=?, linkedon=?";
    $sql=$dbh->prepare($stmt) or loggit(2, "MySql error: ".$dbh->error);
    $sql->bind_param("ssssss", $uid,$fid,$oid,$tstamp,$oid,$tstamp) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $sql->close();

    //Log and return
    loggit(1,"Linked feed: [$fid] with outline: [$oid] for user: [$uid].");
    return(TRUE);
}


//Link feed to a list
function link_feed_to_list($fid = NULL, $oid = NULL)
{
    //Check parameters
    if($fid == NULL) {
        loggit(2,"The feed id is blank or corrupt: [$fid]");
        return(FALSE);
    }
    if($oid == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';
    $tstamp = time();

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Link the feed to the user
    $stmt = "INSERT INTO $table_listfeeds (listid, feedid, linkedon) VALUES (?,?,?) ON DUPLICATE KEY UPDATE linkedon=?";
    $sql=$dbh->prepare($stmt) or loggit(2, "MySql error: ".$dbh->error);
    $sql->bind_param("ssss", $oid,$fid,$tstamp,$tstamp) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $sql->close();

    //Log and return
    loggit(1,"Linked feed: [$fid] with list: [$oid].");
    return(TRUE);
}


//Unlink a feed from an outline
function unlink_feed_from_outline($fid = NULL, $oid = NULL)
{
    //Check params
    if($fid == NULL) {
        loggit(2,"The feed id is blank or corrupt: [$fid]");
        return(FALSE);
    }
    if($oid == NULL) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Look for the id in the table and kill it
    $stmt = "UPDATE $table_nfcatalog SET outlineid=NULL WHERE feedid=? AND outlineid=?";
    $sql=$dbh->prepare($stmt) or loggit(2, "MySql error: ".$dbh->error);
    $sql->bind_param("ss", $fid, $oid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    loggit(1,"Unlinked feed: [$fid] from outline: [$oid].");
    return(TRUE);
}


//Unlink a feed from a list
function unlink_feed_from_list($fid = NULL, $lid = NULL)
{
    //Check params
    if($fid == NULL) {
        loggit(2,"The feed id is blank or corrupt: [$fid]");
        return(FALSE);
    }
    if($lid == NULL) {
        loggit(2,"The list id is blank or corrupt: [$lid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Look for the id in the table and kill it
    $stmt = "DELETE FROM $table_listfeeds WHERE feedid=? AND listid=?";
    $sql=$dbh->prepare($stmt) or loggit(2, "MySql error: ".$dbh->error);
    $sql->bind_param("ss", $fid, $lid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    //loggit(3,"Unlinked feed: [$fid] from list: [$lid].");
    return(TRUE);
}


//Remove all feeds in the catalog table marked as purge for this outline
function purge_outline_feeds($oid = NULL)
{
    //Check params
    if(empty($oid)) {
        loggit(2,"The outline id is blank or corrupt: [$oid]");
        return(FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf").'/includes/env.php';

    //Connect to the database server
    $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

    //Look for the id in the transaction table
    $stmt = "DELETE FROM $table_nfcatalog WHERE `purge`=1 AND outlineid=?";
    $sql=$dbh->prepare($stmt) or loggit(2, "MySql error: ".$dbh->error);
    $sql->bind_param("s", $oid) or loggit(2, "MySql error: ".$dbh->error);
    $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close();

    //Log and leave
    loggit(1,"Deleted: [$delcount] catalog links for outline: [$oid].");
    return($delcount);
}