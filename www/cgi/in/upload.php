<?php
// Includes
include get_cfg_var("cartulary_conf").'/includes/env.php';
include "$confroot/$includes/util.php";
include "$confroot/$includes/auth.php";
include "$confroot/$includes/posts.php";

//Get the user id from the session id
// Valid session?
if(!is_logged_in()) {
  loggit(2,"User attempted to upload an enclosure without being logged in first.");
  exit(0);
}

//Who is this user?
$uid = get_user_id_from_sid(get_session_id());

//Get this users prefs
$prefs = get_user_prefs($uid);

// Set the uplaod directory
$uploadDir = "$confroot/$spooldir/";

//Get the datestamp
$datestamp = $_POST['datestamp'];

if (!empty($_FILES)) {
	$tempFile   = $_FILES['Filedata']['tmp_name'][0];
	$uploadDir  = $uploadDir;
	$targetFile = $uploadDir . $uid."_".$datestamp."_".str_replace(' ', '', strtolower($_FILES['Filedata']['name'][0]));

	// Validate the file type
	$fileTypes = array('jpg', 'jpeg', 'gif', 'png', 'mp3', 'pdf'); // Allowed file extensions
	$fileParts = pathinfo(str_replace(' ', '', strtolower($_FILES['Filedata']['name'][0])));

	// Validate the filetype
	if (in_array($fileParts['extension'], $fileTypes)) {

		// Save the file
                loggit(3, "Upload: moving [".$tempFile."] to [".$targetFile."].");
		move_uploaded_file($tempFile,$targetFile);
		echo 1;

	} else {

		// The file type wasn't allowed
		echo 'Invalid file type.';

	}
}
?>
