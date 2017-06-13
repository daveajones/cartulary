<? include get_cfg_var("cartulary_conf") . '/includes/env.php'; ?>
<? include "$confroot/$templates/php_cgi_init.php" ?>
<?
// Json header
header("Cache-control: no-cache, must-revalidate");
header("Content-Type: application/json");
$jsondata = array();
$jsondata['fieldname'] = "";

//Set the uplaod directory
$uploadDir = "$confroot/$cg_folder_spool/";

//Get the datestamp
$datestamp = $_POST['datestamp'];

//Process the files coming in
if ( !empty( $_FILES ) ) {
    $tempFile = $_FILES['Filedata']['tmp_name'];
    $fileName = cleanFilename($_FILES['Filedata']['name']);
    $fileType = $_FILES['Filedata']['type'];
    $uploadDir = $uploadDir;
    $targetFile = $uploadDir . $uid . "_" . $datestamp . "_" . $fileName;
    $targetS3File = $datestamp . "_" . $fileName;

    // Validate the file type
    $fileParts = pathinfo($targetFile);

    // Save the file
    loggit(3, "Upload: moving [" . $tempFile . "] to [" . $targetFile . "].");
    move_uploaded_file($tempFile, $targetFile);

    // If this is a jpeg, rotate it correctly
    if (striposa($targetFile, array('.jpg', '.jpeg')) !== FALSE || striposa($fileType, array('jpg', 'jpeg')) !== FALSE) {
        image_fix_orientation($targetFile);
    }

    $s3info = get_s3_info($g_uid);
    if ( $s3info != FALSE ) {
        loggit(3, "Uploading enclosure to S3: " . print_r($targetFile, TRUE));

        putFileInS3($targetFile, $targetS3File, $s3info['bucket'] . "/enc", $s3info['key'], $s3info['secret']);
        $enclosure = array( 'url' => get_s3_url($uid, '/enc/', $targetS3File),
            'length' => filesize($targetFile),
            'type' => mime_content_type($targetFile)
        );
        loggit(3, "Unlinking file: $targetFile");
        unlink($targetFile);

        //Give feedback that all went well
        echo json_encode($enclosure);
        return ( 0 );
    }
} else {
    loggit(2, "Upload attempt was blank [" .print_r($_FILES, TRUE) . "].");
}