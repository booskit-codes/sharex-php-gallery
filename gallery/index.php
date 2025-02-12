<?php
// index.php
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

// Get all folders (albums) in the image directory
$albums = array_filter(glob("$imageDirectory*"), 'is_dir');

// Get the current page number from the query string, default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Set the number of days per page using the constant
$daysPerPage = DAYS_PER_PAGE;

// Get all images in the directory (excluding albums)
$images = array_filter(glob("$imageDirectory*.{jpg,jpeg,png,gif}", GLOB_BRACE), 'is_file');

// Sort images by date created (descending)
usort($images, function ($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Group images by date
$groupedImages = [];
foreach ($images as $image) {
    $date = date('Y-m-d', filemtime($image));
    if (!isset($groupedImages[$date])) {
        $groupedImages[$date] = [];
    }
    $groupedImages[$date][] = $image;
}

// Calculate statistics
$totalImages = count($images);
$currentMonth = date('Y-m');
$imagesThisMonth = count(array_filter($images, function ($image) use ($currentMonth) {
    return strpos(date('Y-m', filemtime($image)), $currentMonth) === 0;
}));

$months = array_unique(array_map(function ($image) {
    return date('Y-m', filemtime($image));
}, $images));

$averageImagesPerMonth = count($months) > 0 ? round($totalImages / count($months)) : 0;
$totalDiskSpace = array_sum(array_map('filesize', $images));
$diskSpaceMB = round($totalDiskSpace / (1024 * 1024), 2);

// Pagination: split groups into chunks of days per page
$dates = array_keys($groupedImages);
$totalPages = ceil(count($dates) / $daysPerPage);
$startIndex = ($page - 1) * $daysPerPage;
$datesToDisplay = array_slice($dates, $startIndex, $daysPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <link rel="apple-touch-icon" sizes="180x180" href="resources/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="resources/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="resources/favicon-16x16.png">
    <link rel="manifest" href="resources/site.webmanifest">
    <link rel="stylesheet" href="resources/app.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container py-5 drag-drop-container" id="dragDropContainer">
        <div id="drop-overlay">
            <p><i class="fas fa-upload fa-3x"></i><br>Drop images here to upload</p>
        </div>
        <div class="d-flex justify-content-end mb-4">
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
        <h1 class="text-center mb-4">Image Gallery</h1>

        <div class="mb-5">
            <h2 class="text-center mb-4">📊 Gallery Statistics</h2>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-images fa-2x text-primary"></i>
                             </h5>
                            <p class="card-text">Total Images</p>
                            <h4 class="text-success"><?= $totalImages ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-calendar-day fa-2x text-warning"></i>
                            </h5>
                            <p class="card-text">Images This Month</p>
                            <h4 class="text-success"><?= $imagesThisMonth ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line fa-2x text-info"></i>
                            </h5>
                            <p class="card-text">Avg Images/Month</p>
                            <h4 class="text-success"><?= $averageImagesPerMonth ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-database fa-2x text-danger"></i>
                            </h5>
                            <p class="card-text">Total Disk Space</p>
                            <h4 class="text-success"><?= $diskSpaceMB ?> MB</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($albums)): ?>
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-center mb-0">📁 Albums</h2>
                    <div>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createAlbumModal">
                            <i class="fas fa-plus"></i> Create New Album
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                            <i class="fas fa-upload"></i> Upload Image
                        </button>
                    </div>
                </div>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                    <?php foreach ($albums as $album): ?>
                        <?php $albumName = basename($album); ?>
                        <div class="col">
                            <div class="card shadow-sm h-100">
                                <div class="card-body text-center">
                                    <h5 class="card-title"><?= htmlspecialchars($albumName) ?></h5>
                                    <a href="album.php?album=<?= urlencode($albumName) ?>" class="btn btn-primary">
                                        <i class="fas fa-folder-open"></i> View Album
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>


        <?php foreach ($datesToDisplay as $date): ?>
            <div class="gallery">
                <h2><?= htmlspecialchars($date) ?></h2>
                <div class="thumbnails">
                    <?php
                    $imagesForDay = $groupedImages[$date];
                    $displayImages = array_slice($imagesForDay, 0, IMAGES_PER_DAY); // Use constant for limit
                    foreach ($displayImages as $image): ?>
                        <div class="thumbnail-container">
                            <a href="<?= htmlspecialchars($image) ?>" target="_blank">
                                <img src="<?= htmlspecialchars($image) ?>" alt="Image" class="thumbnail">
                            </a>
                            <div class="image-actions">
                                <button class="actions-button" onclick="toggleDropdown(this)"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="actions-dropdown hidden">
                                    <a href="#" onclick="renameImage(event, '<?= htmlspecialchars($image) ?>')" data-image-path="<?= htmlspecialchars($image) ?>">Rename</a>
                                    <a href="#" onclick="moveImage(event, '<?= htmlspecialchars($image) ?>')" data-image-path="<?= htmlspecialchars($image) ?>">Move to Album</a>
                                    <a href="#" onclick="deleteImage(event, '<?= htmlspecialchars($image) ?>')" data-image-path="<?= htmlspecialchars($image) ?>" class="delete-action">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($imagesForDay) > IMAGES_PER_DAY): ?>
                    <a href="view_all.php?date=<?= urlencode($date) ?>" class="btn btn-link">View All Images</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="pagination text-center">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">&laquo; Previous</a>
            <?php endif; ?>

            <?php
            // Determine the range of pages to display
            $maxVisiblePages = 5; // Maximum number of pages to display in the paginator
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);

            // Adjust the range to maintain the maximum visible pages
            if ($endPage - $startPage < $maxVisiblePages - 1) {
                if ($startPage == 1) {
                    $endPage = min($totalPages, $startPage + $maxVisiblePages - 1);
                } elseif ($endPage == $totalPages) {
                    $startPage = max(1, $endPage - $maxVisiblePages + 1);
                }
            }

            // Show the first page and "..." if not in range
            if ($startPage > 1):
            ?>
                <a href="?page=1">1</a>
                <?php if ($startPage > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            // Display the range of pages
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>">Next &raquo;</a>
            <?php endif; ?>
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
                    <form id="renameForm" method="post"> <input type="hidden" name="action" value="rename">
                        <input type="hidden" name="old_path" id="old_path" value="">
                        <div class="mb-3">
                            <label for="new_name" class="form-label">New Name</label>
                            <input type="text" class="form-control" id="new_name" name="new_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitRenameForm()">Rename</button> </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="moveToAlbumWarningModal" tabindex="-1" aria-labelledby="moveToAlbumWarningModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="moveToAlbumWarningModalLabel">Move to Album Warning</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Moving an image to an album will prevent it from being displayed on the main gallery page and date views.</p>
                    <p>Are you sure you want to continue?</p>
                    <form id="moveForm" method="post"> <input type="hidden" name="action" value="move">
                        <input type="hidden" name="image_path" id="image_path_move" value="">
                        <div class="mb-3">
                            <label for="album_select" class="form-label">Select Album</label>
                            <select class="form-select" id="album_select" name="album" required>
                                <?php foreach ($albums as $album): ?>
                                    <?php $albumName = basename($album); ?>
                                    <option value="<?= htmlspecialchars($albumName) ?>"><?= htmlspecialchars($albumName) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="submitMoveForm()">Move to Album</button> </div>
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

    <div class="modal fade" id="createAlbumModal" tabindex="-1" aria-labelledby="createAlbumModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAlbumModalLabel">Create New Album</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createAlbumForm" method="post"> <input type="hidden" name="action" value="create_album">
                        <div class="mb-3">
                            <label for="album_name" class="form-label">Album Name</label>
                            <input type="text" class="form-control" id="album_name" name="album_name" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitCreateAlbumForm()">Create Album</button> </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadImageModalLabel">Upload Images</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadImageForm" method="post" enctype="multipart/form-data"> <input type="hidden" name="action" value="upload_image">
                        <div class="mb-3">
                            <label for="image_upload" class="form-label">Select Images</label>
                            <input type="file" class="form-control" id="image_upload" name="image_upload[]" multiple accept="image/*" required>
                            <small class="text-muted">You can select multiple images.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitUploadImageForm()">Upload Images</button> </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="resources/app.js"></script>

</body>
</html>
