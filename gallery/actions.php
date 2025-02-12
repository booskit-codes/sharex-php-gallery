<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized access');
}

$imageDirectory = realpath('../' . UPLOAD_DIRECTORY);

header('Content-Type: application/json'); // Set response header to JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => '']; // Default response
    try {
        switch ($_POST['action']) {
            case 'rename':
                $oldPath = realpath($_POST['old_path']);
                $newName = trim($_POST['new_name']);

                if (!$oldPath || strpos($oldPath, $imageDirectory) !== 0) {
                    throw new Exception('Invalid file path');
                }

                $extension = pathinfo($oldPath, PATHINFO_EXTENSION);
                $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '', $newName);
                $newPath = dirname($oldPath) . '/' . $safeName . '.' . $extension;

                if (!rename($oldPath, $newPath)) {
                    throw new Exception('Error renaming file');
                }
                $response['status'] = 'success';
                $response['message'] = 'File renamed successfully';
                break;

            case 'move':
                $imagePath = realpath($_POST['image_path']);
                $album = basename($_POST['album']);

                if (!$imagePath || strpos($imagePath, $imageDirectory) !== 0) {
                    throw new Exception('Invalid file path');
                }

                $albumPath = $imageDirectory . '/' . $album;
                if (!is_dir($albumPath)) {
                    throw new Exception('Album does not exist');
                }

                $newPath = $albumPath . '/' . basename($imagePath);
                if (!rename($imagePath, $newPath)) {
                    throw new Exception('Error moving file');
                }
                $response['status'] = 'success';
                $response['message'] = 'File moved to album successfully';
                break;

            case 'delete':
                $imagePath = realpath($_POST['image_path']);

                if (!$imagePath || strpos($imagePath, $imageDirectory) !== 0) {
                    throw new Exception('Invalid file path for deletion');
                }

                if (!file_exists($imagePath) || !is_file($imagePath)) {
                    throw new Exception('File not found for deletion');
                }

                if (!unlink($imagePath)) {
                    throw new Exception('Error deleting file');
                }
                $response['status'] = 'success';
                $response['message'] = 'File deleted successfully';
                break;


            case 'create_album':
                $albumName = preg_replace('/[^a-zA-Z0-9\-_]/', '', trim($_POST['album_name']));
                if (empty($albumName)) {
                    throw new Exception('Invalid album name');
                }

                $albumPath = $imageDirectory . '/' . $albumName;
                if (file_exists($albumPath)) {
                    throw new Exception('Album already exists');
                }

                if (!mkdir($albumPath, 0755)) {
                    throw new Exception('Error creating album');
                }
                $response['status'] = 'success';
                $response['message'] = 'Album created successfully';
                break;

            case 'upload_image':
            case 'drag_drop_upload':
                $fileInputName = ($_POST['action'] === 'upload_image') ? 'image_upload' : 'dragged_images';

                if (!isset($_FILES[$fileInputName]) || empty($_FILES[$fileInputName]['name'][0])) {
                    throw new Exception('No images were uploaded.');
                }

                $uploadUrl = DOMAIN_URL.'upload.php';
                $results = [];
                $uploadedFiles = $_FILES[$fileInputName];

                for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
                    if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $postData = [
                            'secret' => API_KEY,
                            'sharex' => new CURLFile($uploadedFiles['tmp_name'][$i], $uploadedFiles['type'][$i], $uploadedFiles['name'][$i])
                        ];

                        $ch = curl_init($uploadUrl);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        $responseCurl = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                        if (curl_errno($ch)) {
                            $results[] = ['filename' => $uploadedFiles['name'][$i], 'status' => 'error', 'message' => 'cURL error: ' . curl_error($ch)];
                        } elseif ($httpCode == 200) {
                            if (strpos($responseCurl, DOMAIN_URL) === 0) {
                                $results[] = ['filename' => $uploadedFiles['name'][$i], 'status' => 'success'];
                            } else {
                                $results[] = ['filename' => $uploadedFiles['name'][$i], 'status' => 'error', 'message' => 'Upload script error: ' . $responseCurl];
                            }
                        } else {
                            $results[] = ['filename' => $uploadedFiles['name'][$i], 'status' => 'error', 'message' => 'HTTP error: ' . $httpCode . ' - ' . $responseCurl];
                        }
                        curl_close($ch);
                    } elseif ($uploadedFiles['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $results[] = ['filename' => $uploadedFiles['name'][$i], 'status' => 'error', 'message' => 'Upload error: Error code ' . $uploadedFiles['error'][$i]];
                    }
                }

                $successCount = count(array_filter($results, function($result){ return $result['status'] === 'success'; }));
                $errorMessages = array_column(array_filter($results, function($result){ return $result['status'] === 'error'; }), 'message', 'filename');

                if ($successCount > 0 && empty($errorMessages)) {
                    $response['status'] = 'success';
                    $response['message'] = 'Successfully uploaded ' . $successCount . ' images.';
                } elseif ($successCount > 0 && !empty($errorMessages)) {
                    $response['status'] = 'partial_success';
                    $response['message'] = 'Successfully uploaded ' . $successCount . ' images with some errors.';
                    $response['errors'] = $errorMessages;
                } elseif (empty($successCount) && !empty($errorMessages)) {
                    $response['message'] = 'Image upload failed:<br>' . implode('<br>', $errorMessages);
                    $response['errors'] = $errorMessages;
                } else {
                    $response['message'] = 'Unknown upload error.';
                }

                break;


            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
    exit; // Stop further execution to prevent redirect
}

// For actions that are not POST requests (though this script should only handle POST)
$response = ['status' => 'error', 'message' => 'Invalid request method.'];
echo json_encode($response);
exit;