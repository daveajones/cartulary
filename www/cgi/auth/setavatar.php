<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Get the input
$jsondata = array();
$jsondata['fieldname'] = "";

//Is S3 available?
$s3info = get_s3_info($uid);
if($s3info == FALSE) {
  //Log it
  loggit(2,"User tried to upload an avatar without S3 enabled: [$uid | $sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "S3 must be enabled for avatar upload.";
  echo json_encode($jsondata);
  exit(1);
}

//Get the file details and stick it in S3
if( isset($_FILES['avatarFile']) && $_FILES['avatarFile']['size'] > 0 ) {
    $files = $_FILES['avatarFile'];

    $encfile = date('YmdHis')."_".strtolower($files['name']);
    putFileInS3($files['tmp_name'], $encfile, $s3info['bucket']."/avatar", $s3info['key'], $s3info['secret']);
    $s3url = get_s3_url($uid, '/avatar/', $encfile);
} else {
  //Log it
  loggit(2,"Couldn't get a good file upload for this avatar: [$uid | $sid].");
  $jsondata['status'] = "false";
  $jsondata['description'] = "Can't find a valid file upload.";
  echo json_encode($jsondata);
  exit(1);
}

//Set the new avatar url in the prefs
$prefs['avatarurl'] = $s3url;
set_user_prefs($uid, $prefs);

//Rebuild social outline to reflect change
build_social_outline($uid);
loggit(1, "User: [$uid]'s social outline was rebuilt.");

//Log it
loggit(1,"User: [$uid] uploaded a new avatar to: [$s3url].");
$jsondata['status'] = "true";
$jsondata['description'] = "Avatar updated.";

//Give feedback that all went well
$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
header("Cache-control: no-cache, must-revalidate");
if(!$xhr) {
  header("Content-Type: text/html");
} else {
  header("Content-Type: application/json");
}

if(!$xhr) {
  $resp = '<textarea>'.json_encode($jsondata).'</textarea>';
} else {
  $resp = json_encode($jsondata);
}
echo $resp;

loggit(3, "Avatar upload cgi returning: [$resp]");
return(0);

?>
