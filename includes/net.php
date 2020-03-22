<?php
//########################################################################################
// API for managing network and communications
//########################################################################################


//Update a servers ip address/hostname using it's guid value
function update_server_address($guid = NULL, $addr = NULL)
{
    //Check parameters
    if (empty($guid)) {
        loggit(2, "The server guid is blank or corrupt: [$guid]");
        return (FALSE);
    }
    if (empty($addr)) {
        loggit(2, "The server address is blank or corrupt: [$addr]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "INSERT INTO $table_servers (guid, address) VALUES (?,?) ON DUPLICATE KEY UPDATE address=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sss", $guid, $addr, $addr) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Server: [$guid] is located at: [$addr].");
    return (TRUE);
}


//Remove a server from the known servers table
function remove_server($guid = NULL)
{
    //Check parameters
    if (empty($guid)) {
        loggit(2, "The server guid is blank or corrupt: [$guid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "DELETE FROM $table_servers WHERE guid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $guid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Server: [$guid] has been removed.");
    return (TRUE);
}


//Get all known servers
function get_all_servers()
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("SELECT guid,address FROM $table_servers") or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No servers known.");
        return (FALSE);
    }
    $sql->bind_result($guid, $address) or loggit(2, "MySql error: " . $dbh->error);

    //Collect the results
    $servers = array();
    $count = 0;
    while ($sql->fetch()) {
        $servers[$count] = array('guid' => $guid, 'address' => $address);
        $count++;
    }

    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Returning: [$count] servers.");
    return ($servers);
}


//Get an address for a given server guid
function get_server_address_by_guid($guid = NULL)
{
    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("SELECT address FROM $table_servers WHERE guid=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $guid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No servers known.");
        return (FALSE);
    }
    $sql->bind_result($address) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Returning address: [$address] for server guid: [$guid].");
    return ($address);
}


//Add an ip address to the ban table or update the time stamp of one
function update_banned_ip($addr = NULL, $reason = 0)
{
    //Check parameters
    if (empty($addr)) {
        loggit(2, "The ip address is blank or corrupt: [$addr]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    $added = time();
    $expires = $added + 86400;

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "INSERT INTO $table_ban (ip,added,reason,expires) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE added=?,expires=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sddddd", $addr, $added, $reason, $expires, $added, $expires) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Added: [$addr] to the ban table.  Expires at: [$expires].");
    return (TRUE);
}


//Add an ip address to the registration attempt table
function update_registration_attempt($addr = NULL)
{
    //Check parameters
    if (empty($addr)) {
        loggit(2, "The ip address is blank or corrupt: [$addr]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    $la = time();

    //Database call
    $stmt = "INSERT INTO $table_registration (ip,lastattempt) VALUES (?,?) ON DUPLICATE KEY UPDATE attempts=attempts+1,lastattempt=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sdd", $addr, $la, $la) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Updated the registration attempt table for ip: [$addr].");
    return (TRUE);
}


//Set the reg attempt count for an ip to a certain value
function set_registration_attempts($addr = NULL, $count = NULL)
{
    //Check parameters
    if (empty($addr)) {
        loggit(2, "The ip address is blank or corrupt: [$addr]");
        return (FALSE);
    }
    if (empty($count)) {
        loggit(2, "The count is blank or corrupt: [$count]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    $la = time();

    //Database call
    $stmt = "INSERT INTO $table_registration (ip,attempts,lastattempt) VALUES (?,?,?) ON DUPLICATE KEY UPDATE attempts=?,lastattempt=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("sdddd", $addr, $count, $la, $count, $la) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Updated the registration attempt table for ip: [$addr] to: [$count].");
    return (TRUE);
}


//See how many times this ip has tried to register
function get_registration_attempts($addr = NULL)
{
    //Check parameters
    if (empty($addr)) {
        loggit(2, "The ip address is blank or corrupt: [$addr]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("SELECT attempts FROM $table_registration WHERE ip=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $addr) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No servers known.");
        return (0);
    }
    $sql->bind_result($ac) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Returning attempt count: [$ac] for ip: [$addr].");
    return ($ac);
}


//Get timestamp of the last registration attempt
function reset_registration_attempt_counters($time = NULL)
{
    //Check parameters
    if (empty($time)) {
        $time = time() - (86400 * 2);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("DELETE FROM $table_registration WHERE lastattempt < ?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("d", $time) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $delcount = $sql->affected_rows;
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Deleted: [$delcount] expired registration bans.");
    return ($delcount);
}


//Get a redirection address for a given host name
function get_redirection_url_by_host_name($host = NULL)
{
    //Check parameters
    if (empty($host)) {
        loggit(2, "The host address is blank or corrupt: [$host]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("SELECT url FROM $table_redirect WHERE host=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $host) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(1, "No redirect known.");
        return ("");
    }
    $sql->bind_result($url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Returning url: [$url] for host: [$host].");
    return ($url);
}


//Get a redirection host name for a given destination url
function get_redirection_host_name_by_url($url = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The destination url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("SELECT host FROM $table_redirect WHERE url=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(3, "No redirect found for: [$url].");
        return ("");
    }
    $sql->bind_result($host) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Returning host: [$host] for url: [$url].");
    return ($host);
}


//Add a redirection url to the redirector table
function update_redirection_host_name_by_url($url = NULL, $host = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }
    if (empty($host)) {
        loggit(2, "The host is blank or corrupt: [$host]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "INSERT INTO $table_redirect (host, url, userid) VALUES (?,?,?) ON DUPLICATE KEY UPDATE url=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ssss", $host, $url, $uid, $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Updated: [$host -> $url] redirection.");
    return (TRUE);
}


//Remove a redirection host from the redirector table
function remove_redirection_by_host_name($host = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($host)) {
        loggit(2, "The host is blank or corrupt: [$host]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "DELETE FROM $table_redirect WHERE host=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $host, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Removed: [$host] redirection for user: [$uid].");
    return (TRUE);
}


//Remove a redirection url from the redirector table
function remove_redirection_by_url($url = NULL, $uid = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }
    if (empty($uid)) {
        loggit(2, "The user id is blank or corrupt: [$uid]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "DELETE FROM $table_redirect WHERE url=? AND userid=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("ss", $url, $uid) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Removed: [$url] redirection for user: [$uid].");
    return (TRUE);
}


//Remove a redirection url from the redirector table
function add_redirection_hit_by_url($url = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The url is blank or corrupt: [$url]");
        return (FALSE);
    }


    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $stmt = "UPDATE $table_redirect SET hits = hits + 1 WHERE url=?";
    $sql = $dbh->prepare($stmt) or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);

    //Log and return
    loggit(3, "Added 1 hit for url: [$url].");
    return (TRUE);
}


//Get a hit count for a given destination url
function get_redirection_hit_count_by_url($url = NULL)
{
    //Check parameters
    if (empty($url)) {
        loggit(2, "The destination url is blank or corrupt: [$url]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("SELECT hits FROM $table_redirect WHERE url=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $url) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(3, "No redirect found for: [$url].");
        return (0);
    }
    $sql->bind_result($hits) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Returning hit count: [$hits] for url: [$url].");
    return ((integer)$hits);
}


//Get a hit count for a given destination url
function get_redirection_hit_count_by_host($host = NULL)
{
    //Check parameters
    if (empty($host)) {
        loggit(2, "The redirection host is blank or corrupt: [$host]");
        return (FALSE);
    }

    //Includes
    include get_cfg_var("cartulary_conf") . '/includes/env.php';

    //Connect to the database server
    $dbh = new mysqli($dbhost, $dbuser, $dbpass, $dbname) or loggit(2, "MySql error: " . $dbh->error);

    //Database call
    $sql = $dbh->prepare("SELECT hits FROM $table_redirect WHERE host=?") or loggit(2, "MySql error: " . $dbh->error);
    $sql->bind_param("s", $host) or loggit(2, "MySql error: " . $dbh->error);
    $sql->execute() or loggit(2, "MySql error: " . $dbh->error);
    $sql->store_result() or loggit(2, "MySql error: " . $dbh->error);
    //See if any rows came back
    if ($sql->num_rows() < 1) {
        $sql->close()
        or loggit(2, "MySql error: " . $dbh->error);
        loggit(3, "No redirect found for: [$host].");
        return (0);
    }
    $sql->bind_result($hits) or loggit(2, "MySql error: " . $dbh->error);
    $sql->fetch();
    $sql->close() or loggit(2, "MySql error: " . $dbh->error);


    loggit(3, "Returning hit count: [$hits] for host: [$host].");
    return ((integer)$hits);
}