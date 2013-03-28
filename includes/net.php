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
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Database call
  $stmt = "INSERT INTO $table_servers (guid, address) VALUES (?,?) ON DUPLICATE KEY UPDATE address=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("sss", $guid, $addr, $addr) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

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
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Database call
  $sql=$dbh->prepare("SELECT guid,address FROM $table_servers") or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"No servers known.");
    return(FALSE);
  }
  $sql->bind_result($guid,$address) or print(mysql_error());

  //Collect the results
  $servers = array();
  $count = 0;
  while($sql->fetch()){
    $servers[$count] = array( 'guid' => $guid, 'address' => $address );
    $count++;
  }

  $sql->close() or print(mysql_error());


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
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Database call
  $sql=$dbh->prepare("SELECT address FROM $table_servers WHERE guid=?") or print(mysql_error());
  $sql->bind_param("s", $guid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->store_result() or print(mysql_error());
  //See if any rows came back
  if($sql->num_rows() < 1) {
    $sql->close()
      or print(mysql_error());
    loggit(1,"No servers known.");
    return(FALSE);
  }
  $sql->bind_result($address) or print(mysql_error());
  $sql->fetch();
  $sql->close() or print(mysql_error());


  loggit(3,"Returning address: [$address] for server guid: [$guid].");
  return($address);
}
?>
