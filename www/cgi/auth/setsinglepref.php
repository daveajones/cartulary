<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

//Get the initial set of prefs to make sure we have a good set of data to alter
if($prefs == FALSE) {
  $prefs = init_user_prefs($uid);
}

// Get the input
if( isset($_POST['prefs']) ) {
    $pref = $_POST['prefs'];
} else {
    //Give feedback
    $jsondata['status'] = "false";
    $jsondata['description'] = "Prefs not updated.";
    $jsondata['prefname'] = "";
    echo json_encode($jsondata);
    exit(0);
}

loggit(3, "DEBUG: ".print_r($_POST, TRUE));

//--------------------------------------------------------

$newprefs = array_merge($prefs, $pref);

//--------------------------------------------------------


//Set the prefs
set_user_prefs($uid, $newprefs);

//Log it
loggit(1,"Prefs for user: [$uid] were updated.");


//Give feedback that all went well
$jsondata['status'] = "true";
$jsondata['description'] = "Prefs updated successfully.";
$jsondata['prefname'] = "";
echo json_encode($jsondata);

return(0);