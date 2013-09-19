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