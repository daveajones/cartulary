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
if (isset($_POST['datestamp']) && is_numeric($_POST['datestamp'])) {
    $datestamp = $_POST['datestamp'];
} else {
    $datestamp = (string)microtime(TRUE);
}

$sizes = NULL;
if (isset($_POST['sizes']) && !empty($_POST['sizes'])) {
    $sizes = json_decode($_POST['sizes'], TRUE);
    loggit(3, print_r($_POST, TRUE));
}


//Process the files coming in
if (!empty($_FILES)) {
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
    if ($s3info != FALSE) {
        loggit(3, "Uploading enclosure to S3: " . print_r($targetFile, TRUE));

        putFileInS3($targetFile, $targetS3File, $s3info['bucket'] . "/enc", $s3info['key'], $s3info['secret'], array(
            'Cache-Control'     => 'max-age=31556926'
        ));
        $enclosure = array(
            'url' => get_s3_url($uid, '/enc/', $targetS3File),
            'length' => filesize($targetFile),
            'type' => mime_content_type($targetFile)
        );


        //Make resized copies if any were requested
        if (!empty($sizes) && is_array($sizes)) {
            foreach ($sizes as $size) {
                $width = $size['size'];
                if (is_numeric($width) && $width < 4000) {
                    $newS3File = $targetS3File . "_" . $size['size'] . "." . $size['type'];
                    $newTargetFile = $targetFile . "_" . $width;
                    image_resize($targetFile, $newTargetFile, $size['type'], $width, NULL, NULL);
                    putFileInS3($newTargetFile, $newS3File, $s3info['bucket'] . "/enc", $s3info['key'], $s3info['secret'], array(
                        'Cache-Control'     => 'max-age=31556926'
                    ));
                    loggit(3, "Uploading: [$newTargetFile] to S3 as: [$newS3File].");
                }
            }
        }

        loggit(3, "Unlinking file: $targetFile");
        unlink($targetFile);

        //Give back info if there was any
        if (isset($_POST['element']) && is_numeric($_POST['element'])) {
            $enclosure['element'] = $_POST['element'];
        }
        $enclosure['datestamp'] = $_POST['datestamp'];

        //Give feedback that all went well
        echo json_encode($enclosure);
        return (0);
    }
} else {
    loggit(2, "Upload attempt was blank [" . print_r($_FILES, TRUE) . "].");
}


function image_resize($src, $dst, $newtype, $width, $height, $crop = 0)
{

    if (!list($w, $h) = getimagesize($src)) return "Unsupported picture type!";

    if ($w > $h) {
        $ar = $w / $h;
        $longside = 'w';
    } else {
        $ar = $h / $w;
        $longside = 'h';
    }
//    if(empty($height) && $longside == 'w') {
//        $height = round($width / $ar);
//    }
//    if(empty($width) && $longside == 'h') {
//        $width = round($height / $ar);
//    }
    $height = round($width / $ar);

    $type = strtolower(substr(strrchr($src, "."), 1));
    if ($type == 'jpeg') $type = 'jpg';
    switch ($type) {
        case 'bmp':
            $img = imagecreatefromwbmp($src);
            break;
        case 'gif':
            $img = imagecreatefromgif($src);
            break;
        case 'jpg':
            $img = imagecreatefromjpeg($src);
            break;
        case 'png':
            $img = imagecreatefrompng($src);
            break;
        default :
            return "Unsupported picture type!";
    }

    // resize
    if ($crop) {
        if ($w < $width or $h < $height) return "Picture is too small!";
        $ratio = max($width / $w, $height / $h);
        $h = $height / $ratio;
        $x = ($w - $width / $ratio) / 2;
        $w = $width / $ratio;
    } else {
        if ($w < $width and $h < $height) return "Picture is too small!";
        $ratio = min($width / $w, $height / $h);
        $width = $w * $ratio;
        $height = $h * $ratio;
        $x = 0;
    }

    loggit(3, "[$src|$dst|$newtype|$width|$height]");
    $new = imagecreatetruecolor($width, $height);

    // preserve transparency
    if ($type == "gif" or $type == "png") {
        imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
        imagealphablending($new, false);
        imagesavealpha($new, true);
    }

    imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

    switch ($newtype) {
        case 'bmp':
            imagewbmp($new, $dst);
            break;
        case 'gif':
            imagegif($new, $dst);
            break;
        case 'jpg':
            imagejpeg($new, $dst);
            break;
        case 'png':
            imagepng($new, $dst);
            break;
    }
    return true;
}