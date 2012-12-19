<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?include "$confroot/$templates/php_cgi_admin.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
$jsondata['prefname'] = "";


//Make sure we have a valid email address
$jsondata['prefname'] = "userEmail";
if( isset($_REQUEST['userEmail']) ) {
  $email = $_REQUEST['userEmail'];
} else {
  //Log it
  loggit(2,"No email address found in create user request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No email given.";
  echo json_encode($jsondata);
  exit(1);
}

//Make sure we have a valid name
$jsondata['prefname'] = "userName";
if( isset($_REQUEST['userName']) ) {
  $name = $_REQUEST['userName'];
} else {
  //Log it
  loggit(2,"No name found in create user request.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "No name given.";
  echo json_encode($jsondata);
  exit(1);
}

$result = create_user($email, FALSE, TRUE, FALSE, $name);

//If the result is anything other than true then fail
if( $result !== TRUE ) {
  //Log it
  loggit(1,"The user: [$email] already exists.  Can't create.");
  $jsondata['status'] = "false";
  $jsondata['description'] = "That user already exists.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the user id from the email address
$newuid = get_user_id_from_email($email);
$password = random_gen(12);
set_password($newuid, $password);

//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['id'] = $newuid;
$jsondata['password'] = $password;
$jsondata['status'] = "true";
$jsondata['description'] = "User created with password: $password.";
echo json_encode($jsondata);

return(0);

?>

