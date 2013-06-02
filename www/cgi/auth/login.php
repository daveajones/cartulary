<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?


// Get the input
$email=$_POST['email'];
$password=$_POST['password'];
$type = $_POST['type'];

if( $type == 'json' ) {
  //Set up json
  header("Content-Type: application/json");
  $jsondata = array();
  $jsondata['fieldname'] = "";
}

// Is it blank?
if(empty($email) || empty($password)) {
  //Log it
  loggit(2,"The email[$email] or password[$password] was blank.");
  header("Location: $loginerrorpage?code=0");
  exit(1);
}

// Cleanse the input
$email = htmlentities($email);
$password = htmlentities($password);

// Make sure that the given values are of a sane length
if( (preg_match("/.{6,320}/",$email)==0) || (preg_match("/.{6,128}/",$password)==0) ) {
  //Log it
  loggit(2,"The email: [$email] or password: [$password] given isn't a sane length.");
  header("Location: $loginerrorpage?code=30");
  exit(1);
}

//See if the user actually exists first
if(get_user_id_from_email($email) == "none") {
  //Log it
  loggit(2,"The email[$email] given doesn't exist.");
  header("Location: $loginerrorpage?code=6");
  exit(1);
}

//Check if max bad login count exceeded for this user
if(badlogin_check($email) == FALSE) {
  //Log it
  loggit(2,"Bad login count exceeded for: [$email]");
  header("Location: $loginerrorpage?code=8");
  exit(1);
}

//Check the login credentials against the database
$uid = check_credentials($email,$password);
if($uid == FALSE) {
  //Log it
  loggit(2,"Login attempt failed for: [$email | $password].");
  //Increment the bad attempt counter
  badlogin_inc($email);
  header("Location: $loginerrorpage?code=2");
  exit(1);
} else {
  badlogin_reset($email);
}


dologin:

//Log it
loggit(1,"User Logged in successfully: [$email | $uid].");

//Reset the bad login attempt counter
badlogin_reset($email);

//Make a new session
if( ($sid = new_session($uid)) == FALSE ) {
  loggit(2,"Could not create session: [$uid]");
  header ("Location: $loginerrorpage?code=4");
  exit(1);
}

//Make a cookie for this session
setcookie($sidcookie, $sid, 0, "/");


//Is this the first time this user is logging in?
if(is_user_active($uid)) {
  //Is there a previously requested uri we need to follow through on?
  if( !empty($_COOKIE[$postfollowcookie]) ) {
    $pfc = $_COOKIE[$postfollowcookie];
    loggit(3, "FOLLOW: $pfc");
    if( $type == 'json' ) {
        $jsondata['goloc'] = $pfc;
        $jsondata['status'] = "true";
        $jsondata['description'] = "Login success. Redirecting.";
        echo json_encode($jsondata);
    } else {
    	header("Location: $pfc");
    }
    exit(0);
  }

  //Redirect to the start page
  if( $type == 'json' ) {
      $jsondata['goloc'] = $startpage;
      $jsondata['status'] = "true";
      $jsondata['description'] = "Login success. Redirecting.";
      echo json_encode($jsondata);
  } else {
      header("Location: $startpage");
  }
} else {
  //Log it
  loggit(3,"Redirecting new user: [$email | $uid] with sid: [$sid] to the activation page: [$activatepage].");

  //Set the activation stage
  $stg = get_activation_stage($uid);
  if($stg == 0) {
    set_activation_stage($uid, 1);
    if( $type == 'json' ) {
        $jsondata['goloc'] = $activatepage."1";
        $jsondata['status'] = "true";
        $jsondata['description'] = "Login success. Redirecting.";
        echo json_encode($jsondata);
    } else {
        header("Location: $activatepage"."1");
    }
  } else {
    //Redirect to the activation page
    if( $type == 'json' ) {
        $jsondata['goloc'] = $activatepage.$stg;
        $jsondata['status'] = "true";
        $jsondata['description'] = "Login success. Redirecting.";
        echo json_encode($jsondata);
    } else {
        header("Location: $activatepage".$stg);
    }
  }
}

return(0);

?>
