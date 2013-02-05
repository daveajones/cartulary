<?include get_cfg_var("cartulary_conf").'/includes/env.php';?>
<?include "$confroot/$templates/php_cgi_init.php"?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");

// Get the input
//if ( isset($_POST['newpref']) ) { $newpref = $_POST['newpref']; } else { $newpref = ""; };
$jsondata = array();
$jsondata['fieldname'] = "";

//Set the uplaod directory
$uploadDir = "$confroot/$cg_folder_spool/";

//Get the datestamp
$datestamp = $_POST['datestamp'];

//Process the files coming in
if (!empty($_FILES)) {
	$tempFile   = $_FILES['Filedata']['tmp_name'];
	$uploadDir  = $uploadDir;
	$targetFile = $uploadDir . $uid."_".$datestamp."_".str_replace(' ', '', strtolower($_FILES['Filedata']['name']));
	$targetS3File = $datestamp."_".str_replace(' ', '', strtolower($_FILES['Filedata']['name']));

	// Validate the file type
	$fileTypes = array('jpg', 'jpeg', 'gif', 'png', 'mp3', 'pdf'); // Allowed file extensions
	$fileParts = pathinfo(str_replace(' ', '', strtolower($_FILES['Filedata']['name'])));
                loggit(3, "DEBUG: ".print_r($_FILES, TRUE));

	// Validate the filetype
	if (in_array($fileParts['extension'], $fileTypes)) {

		// Save the file
                loggit(3, "Upload: moving [".$tempFile."] to [".$targetFile."].");
		move_uploaded_file($tempFile,$targetFile);
		//echo 1;

		$s3info = get_s3_info($g_uid);
                if( $s3info != FALSE ) {
                  loggit(3, "Uploading enclosure to S3: ".print_r($targetFile, TRUE));

                  putFileInS3($targetFile, $targetS3File, $s3info['bucket']."/enc", $s3info['key'], $s3info['secret']);
		  $enclosure = array( 'url' => get_s3_url($uid, '/enc/', $targetS3File),
                                      'length' => filesize($targetFile),
                                      'type' => mime_content_type($targetFile)
                  );
		  loggit(3, "Unlinking file: $targetFile");
                  unlink($targetFile);

		  //Give feedback that all went well
		  echo json_encode($enclosure);
                  return(0);
		}

	} else {

		// The file type wasn't allowed
		echo 'Invalid file type.';

	}
}
?>
