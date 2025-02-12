// app.js
function toggleDropdown(button) {
    const dropdown = button.nextElementSibling;
    dropdown.classList.toggle('hidden');
    // Close other dropdowns if opening this one
    document.querySelectorAll('.actions-button').forEach(btn => {
        if (btn !== button) {
            btn.nextElementSibling.classList.add('hidden');
        }
    });
}

function renameImage(event, imagePath) {
    event.preventDefault();
    document.getElementById('old_path').value = imagePath;
    $('#renameModal').modal('show');
}

function moveImage(event, imagePath) {
    event.preventDefault();
    document.getElementById('image_path_move').value = imagePath;
    $('#moveToAlbumWarningModal').modal('show');
}

function deleteImage(event, imagePath) {
    event.preventDefault();
    document.getElementById('image_path_delete').value = imagePath;
    $('#deleteConfirmModal').modal('show');
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.thumbnail-container')) {
        document.querySelectorAll('.actions-dropdown').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});

// Drag and Drop Functionality
const dragDropContainer = document.getElementById('dragDropContainer'); // Keep this for potential future use or existing HTML structure
const dropOverlay = document.getElementById('drop-overlay');
const body = document.body; // Target the body for drag and drop

body.addEventListener('dragover', function(e) {
    e.preventDefault(); // Prevent default to allow drop
    body.classList.add('dragover'); // Apply dragover class to body
    dropOverlay.classList.remove('hidden');
});

body.addEventListener('dragenter', function(e) {
    e.preventDefault(); // Prevent default to allow drop
    body.classList.add('dragover'); // Apply dragover class to body
    dropOverlay.classList.remove('hidden');
});

body.addEventListener('dragleave', function(e) {
    e.preventDefault();
    body.classList.remove('dragover'); // Remove dragover class from body
    dropOverlay.classList.add('hidden');
});

body.addEventListener('drop', function(e) {
    e.preventDefault(); // Prevent default to prevent browser handling of file drop
    body.classList.remove('dragover'); // Remove dragover class from body
    dropOverlay.classList.add('hidden');

    const files = e.dataTransfer.files;
    uploadFiles(files);
});

function uploadFiles(files) {
    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('dragged_images[]', files[i]); // Use 'dragged_images[]' as the name
    }
    formData.append('action', 'drag_drop_upload'); // Set action for backend

    fetch('actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json(); // Parse JSON response
    })
    .then(data => {
        // Handle response data and display notification
        showNotification(data);
        console.log('Upload response:', data); // Optional logging
    })
    .catch(error => {
        console.error('Upload error:', error);
        showNotification({ status: 'error', message: 'Image upload failed. Please check console for details.' });
    });
}

function showNotification(response) {
    let alertClass = '';
    let messageText = '';

    if (response.status === 'success') {
        alertClass = 'alert-success';
        messageText = response.message;
        // Refresh the gallery to show new images immediately.
        reloadGallery();
    } else if (response.status === 'partial_success') {
        alertClass = 'alert-warning';
        messageText = response.message;
        if (response.errors) {
            messageText += '<br>Errors:<ul>';
            for (const filename in response.errors) {
                messageText += `<li>${filename}: ${response.errors[filename]}</li>`;
            }
            messageText += '</ul>';
        }
        // Refresh the gallery to show new images immediately.
        reloadGallery();
    }
    else {
        alertClass = 'alert-danger';
        messageText = response.message;
    }

    const notificationDiv = document.createElement('div');
    notificationDiv.className = `upload-notification alert ${alertClass} position-fixed top-0 end-0 m-3`;
    notificationDiv.innerHTML = messageText;
    document.body.appendChild(notificationDiv);

    // Auto-hide the notification after a few seconds
    setTimeout(() => {
        notificationDiv.remove();
    }, 5000); // 5 seconds, adjust as needed
}

function reloadGallery() {
    // Simple reload for now - can be optimized to update only image grid later
    window.location.reload();
}

function submitRenameForm() {
    const form = document.getElementById('renameForm');
    const formData = new FormData(form);

    fetch('actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data);
        if (data.status === 'success') {
            $('#renameModal').modal('hide');
            reloadGallery(); // Reload gallery to reflect rename
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification({ status: 'error', message: 'Error renaming image.' });
    });
}

function submitMoveForm() {
    const form = document.getElementById('moveForm');
    const formData = new FormData(form);

    fetch('actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data);
        if (data.status === 'success') {
            $('#moveToAlbumWarningModal').modal('hide');
            reloadGallery(); // Reload gallery to reflect move
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification({ status: 'error', message: 'Error moving image.' });
    });
}

function submitDeleteForm() {
    const form = document.getElementById('deleteForm');
    const formData = new FormData(form);

    fetch('actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data);
        if (data.status === 'success') {
            $('#deleteConfirmModal').modal('hide');
            reloadGallery(); // Reload gallery to reflect deletion
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification({ status: 'error', message: 'Error deleting image.' });
    });
}


function submitCreateAlbumForm() {
    const form = document.getElementById('createAlbumForm');
    const formData = new FormData(form);

    fetch('actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data);
        if (data.status === 'success') {
            $('#createAlbumModal').modal('hide');
            reloadGallery(); // Reload gallery to reflect new album
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification({ status: 'error', message: 'Error creating album.' });
    });
}

function submitUploadImageForm() {
    const form = document.getElementById('uploadImageForm');
    const formData = new FormData(form);

    fetch('actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data);
        if (data.status === 'success' || data.status === 'partial_success') {
            $('#uploadImageModal').modal('hide');
            reloadGallery(); // Reload gallery to show new images
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification({ status: 'error', message: 'Error uploading images.' });
    });
}