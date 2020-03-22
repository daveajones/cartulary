<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_bin_init.php"?>
<?

//Get the user from the command line
$userEmail = trim($argv[1]);
if( strpos($userEmail, "@") === false ) {
    loggit(2,"The value: [$userEmail] doesn't look like an email address.");
    echo "The value: [$userEmail] doesn't look like an email address.\n";
    exit(1);
}

//Vars
$userId = get_user_id_from_email($userEmail);

//Make sure we have some user id input to use
if(empty($userId) || $userId == "none") {
    //Log it
    loggit(2,"The user id was missing.");
    echo "Couldn't get a user id: [$userId] for email: [$userEmail].\n";
    exit(1);
}

//Get the user's name so we can intelligently respond
$userName = get_user_name_from_uid($userId);

//Create a new password
$newPassword = random_gen(12);
set_password($userId, $newPassword);

//Disable two-factor auth for this user
disable_totp($userId);

//Log it
loggit(1,"Password reset for user: [$userId].");
echo "Password reset and two-factor disabled for user: [$userId].\n\n";
echo "The new password is:  $newPassword\n\n";

//--------------------------------------------------------------------------------
//Give feedback that all went well
echo "Done.\n\n";