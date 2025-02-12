<?php
session_start();

// Include the settings file
require_once '../config.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Set the directory containing images using the constant
$imageDirectory = '../'.UPLOAD_DIRECTORY;

// Get the date from the query string
$date = isset($_GET['date']) ? $_GET['date'] : null;

if (!$date) {
    die('Date not specified.');
}

// Get all images in the directory
$images = glob("$imageDirectory*.{jpg,jpeg,png,gif}", GLOB_BRACE);

// Filter images by the given date
$imagesForDay = array_filter($images, function ($image) use ($date) {
    return date('Y-m-d', filemtime($image)) === $date;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="resources/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="resources/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="resources/favicon-16x16.png">
    <link rel="manifest" href="resources/site.webmanifest">
    <link rel="stylesheet" href="resources/app.css">
    <title>Images for <?= htmlspecialchars($date) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Images for <?= htmlspecialchars($date) ?></h1>
        <div class="d-flex justify-content-end mb-4">
            <a href="index.php" class="btn btn-primary">Back to Gallery</a>
        </div>
        <div class="thumbnails">
            <?php foreach ($imagesForDay as $image): ?>
                <div class="thumbnail-container">
                    <a href="<?= htmlspecialchars($image) ?>" target="_blank">
                        <img src="<?= htmlspecialchars($image) ?>" alt="Image" class="thumbnail">
                    </a>
                    <div class="image-actions">
                        <button class="actions-button" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-v"></i></button>
                        <div class="actions-dropdown hidden">
                            <a href="#" onclick="renameImage(event, '<?= htmlspecialchars($image) ?>')" data-image-path="<?= htmlspecialchars($image) ?>">Rename</a>
                            <a href="#" onclick="deleteImage(event, '<?= htmlspecialchars($image) ?>')" data-image-path="<?= htmlspecialchars($image) ?>" class="delete-action">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-primary">Back to Gallery</a>
        </div>
    </div>

    <div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="renameModalLabel">Rename Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="renameForm" method="post">
                        <input type="hidden" name="action" value="rename">
                        <input type="hidden" name="old_path" id="old_path" value="">
                        <div class="mb-3">
                            <label for="new_name" class="form-label">New Name</label>
                            <input type="text" class="form-control" id="new_name" name="new_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitRenameForm()">Rename</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this image?</p>
                    <form id="deleteForm" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="image_path" id="image_path_delete" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="submitDeleteForm()">Delete Image</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="resources/app.js"></script>

</body>
</html>