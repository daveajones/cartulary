<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
$jsondata['prefname'] = "";

// Get the input
$password0 = $_POST['password0'];
$password1 = $_POST['password1'];
$password2 = $_POST['password2'];

// Is the old one correct?
if( !check_credentials($g_myemail, $password0) ) {
    //Log it
    loggit(2,"Old password was wrong: [$password0]");
    $jsondata['status'] = "false";
    $jsondata['description'] = "The old password was wrong.";
    echo json_encode($jsondata);
    exit(1);
}

// Do the passwords match
if($password1 != $password2) {
  //Log it
  loggit(2,"The passwords didn't match: [$password1 | $password2]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "The passwords didn't match.";
  echo json_encode($jsondata);
  exit(1);
}

// Does it meet the complexity requirements
$passlen = strlen($password1);
if($passlen < $security_minpasslength) {
  //Log it
  loggit(2,"The password was shorter that required length: [$password1 | $password2 | $security_minpasslength]");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Passwords must be at least [$security_minpasslength] characters.";
  echo json_encode($jsondata);
  exit(1);
}

//Change the password
set_password($uid, $password1);

//Give feedback that all went well
$jsondata['prefname'] = "";
$jsondata['status'] = "true";
$jsondata['description'] = "Password changed.";
echo json_encode($jsondata);

return(0);

?>

