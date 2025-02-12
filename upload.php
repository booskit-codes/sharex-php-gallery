<?php
// Include the settings file
require_once 'config.php';

$secret_key = API_KEY;
$upload_dir = UPLOAD_DIRECTORY;
$domain_url = DOMAIN_URL;
$lengthofstring = LENGTH_OF_STRING;

function RandomString($length, $numofUpper) {
    $keys = array_merge(range(0, 9), range('a', 'z'));
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $char = $keys[mt_rand(0, count($keys) - 1)];
        if ($numofUpper > 0 && mt_rand(0, 1) === 1) {
            $char = strtoupper($char);
            $numofUpper--;
        }
        $key .= $char;
    }
    return $key;
}

if (isset($_POST['secret'])) {
    if ($_POST['secret'] == $secret_key) {
        // Check for upload errors
        if ($_FILES["sharex"]["error"] !== UPLOAD_ERR_OK) {
            die("Upload error: " . $_FILES["sharex"]["error"]);
        }

        // Ensure the upload directory exists
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                die("Failed to create directory: $upload_dir");
            }
        }

        $numUpperCase = mt_rand(0, $lengthofstring);
        $target_file = basename($_FILES["sharex"]["name"]);
        $fileType = pathinfo($target_file, PATHINFO_EXTENSION);

        // Default extension if empty
        if (empty($fileType)) {
            $fileType = 'png';
        }

        $retryCount = 0;
        $maxRetries = 5;
        $uploadSuccess = false;

        while ($retryCount < $maxRetries && !$uploadSuccess) {
            $filename = RandomString($lengthofstring, $numUpperCase);
            $filepath = $upload_dir . $filename . '.' . $fileType;

            if (!file_exists($filepath)) {
                if (move_uploaded_file($_FILES["sharex"]["tmp_name"], $filepath)) {
                    echo $domain_url . $filename . '.' . $fileType;
                    $uploadSuccess = true;
                } else {
                    echo 'File upload failed - check directory permissions.';
                    $uploadSuccess = true; // Exit loop
                }
            } else {
                $retryCount++;
            }
        }

        if (!$uploadSuccess) {
            echo 'File upload failed - maximum retries exceeded.';
        }
    } else {
        echo 'Invalid Secret Key';
    }
} else {
    echo 'No post data received';
}
?>
