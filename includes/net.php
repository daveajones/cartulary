<?

//_______________________________________________________________________________________
//Update a servers ip address/hostname using it's guid value
function update_server_address($guid = NULL, $addr = NULL)
{
  //Check parameters
  if(empty($guid)) {
    loggit(2,"The server guid is blank or corrupt: [$guid]");
    return(FALSE);
  }
  if(empty($addr)) {
    loggit(2,"The server address is blank or corrupt: [$addr]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

  //Database call
  $stmt = "INSERT INTO $table_servers (guid, address) VALUES (?,?) ON DUPLICATE KEY UPDATE address=?";
  $sql=$dbh->prepare($stmt) or loggit(2, "MySql error: ".$dbh->error);
  $sql->bind_param("sss", $guid, $addr, $addr) or loggit(2, "MySql error: ".$dbh->error);
  $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
  $sql->close() or loggit(2, "MySql error: ".$dbh->error);

  //Log and return
  loggit(3,"Server: [$guid] is located at: [$addr].");
  return(TRUE);
}


//_______________________________________________________________________________________
//Get all known servers
function get_all_servers()
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

  //Database call
  $sql=$dbh->prepare("SELECT guid,address FROM $table_servers") or loggit(2, "MySql error: ".$dbh->error);
  $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
  $sql->store_result() or loggit(2, "MySql error: ".$dbh->error);
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or loggit(2, "MySql error: ".$dbh->error);
    loggit(1,"No servers known.");
    return(FALSE);
  }
  $sql->bind_result($guid,$address) or loggit(2, "MySql error: ".$dbh->error);

  //Collect the results
  $servers = array();
  $count = 0;
  while($sql->fetch()){
    $servers[$count] = array( 'guid' => $guid, 'address' => $address );
    $count++;
  }

  $sql->close() or loggit(2, "MySql error: ".$dbh->error);


  loggit(3,"Returning: [$count] servers.");
  return($servers);
}


//_______________________________________________________________________________________
//Get an address for a given server guid
function get_server_address_by_guid($guid = NULL)
{
  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or loggit(2, "MySql error: ".$dbh->error);

  //Database call
  $sql=$dbh->prepare("SELECT address FROM $table_servers WHERE guid=?") or loggit(2, "MySql error: ".$dbh->error);
  $sql->bind_param("s", $guid) or loggit(2, "MySql error: ".$dbh->error);
  $sql->execute() or loggit(2, "MySql error: ".$dbh->error);
  $sql->store_result() or loggit(2, "MySql error: ".$dbh->error);
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or loggit(2, "MySql error: ".$dbh->error);
    loggit(1,"No servers known.");
    return(FALSE);
  }
  $sql->bind_result($address) or loggit(2, "MySql error: ".$dbh->error);
  $sql->fetch();
  $sql->close() or loggit(2, "MySql error: ".$dbh->error);


  loggit(3,"Returning address: [$address] for server guid: [$guid].");
  return($address);
}
?>
