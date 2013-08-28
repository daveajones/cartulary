<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init_noauth.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$prefs = array();
$jsondata = array();
$jsondata['prefname'] = "";

//Is open sub enabled?
if( $cg_opensignup != 1 ) {
    //Log it
    loggit(2,"Someone submitted an open reg request, but it's disabled.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Registration is closed.";
    echo json_encode($jsondata);
    exit(1);
}

//Increment the reg attempt counter for this ip
$regatt = get_registration_attempts($_SERVER['REMOTE_ADDR']);
if( $regatt == -1 ) {
    //Log it
    loggit(2,"Already created a user within the last 2 days: [$ip].");
    $jsondata['status'] = "false";
    $jsondata['description'] = "You can only create one user per 48 hours.";
    echo json_encode($jsondata);
    exit(1);
} else
if( $regatt < 4 ) {
    update_registration_attempt($_SERVER['REMOTE_ADDR']);
} else {
    //Log it
    loggit(2,"Registration attempt count exceeded for ip: [$ip].");
    $jsondata['status'] = "false";
    $jsondata['description'] = "You've tried to register too many times. 48 hour ban.";
    echo json_encode($jsondata);
    exit(1);
}

//Test incoming values
$name = $_POST['name'];
$email = $_POST['email'];
$secret = $_POST['secret'];
if( empty($name) || empty($email) || empty($secret) ) {
    //Log it
    loggit(2,"The name: [$name], email: [$email] or secret: [$secret] was missing.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "All fields are required.";
    echo json_encode($jsondata);
    exit(1);
}

//Check the secret
if( $secret != $cg_opensignup_phrase ) {
    //Log it
    loggit(2,"The open reg secret given: [$secret] didn't match.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "Incorrect secret phrase.";
    echo json_encode($jsondata);
    exit(1);
}

//Make sure the name at least looks legit
if( !preg_match("/[a-zA-Z0-9]{4,60}/", $name) ) {
    //Log it
    loggit(2,"The name: [$name] doesn't look valid.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "The name doesn't look valid.";
    echo json_encode($jsondata);
    exit(1);
}

//Make sure the email at least looks legit
if( !preg_match("/.*\@.*\..*/i", $email) ) {
    //Log it
    loggit(2,"The email: [$email] doesn't look valid.");
    $jsondata['status'] = "false";
    $jsondata['description'] = "The email address doesn't look valid.";
    echo json_encode($jsondata);
    exit(1);
}

//Create the user
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

//Set the registration attempts table to -1 to signify a successful registration
set_registration_attempts($_SERVER['REMOTE_ADDR'], -1);

//--------------------------------------------------------------------------------
//Give feedback that all went well
$jsondata['id'] = $newuid;
$jsondata['email'] = $email;
$jsondata['password'] = $password;
$jsondata['status'] = "true";
$jsondata['description'] = "User registered successfully.";
echo json_encode($jsondata);

exit(0);