<?php

// Assign a username & password for your gallery.
define('USERNAME', 'admin');
define('PASSWORD', 'admin123');

// Gallery Configuration
define('IMAGES_PER_DAY', 15); // How many images will be displayed in a single day before the "View All" button shows up.
define('DAYS_PER_PAGE', 30); // How many days will be listed per page.
define('LOGIN_ATTEMPTS_THRESHOLD', 5); // Number of failed attempts are allowed before triggering a lockout.
define('LOCKOUT_TIME', 300); // Lockout time in seconds (default: 5 minutes)

// Upload Configuration
define('API_KEY', 'CHANGEME'); // <- Assign an API key that you want to connect your ShareX with.
define('DOMAIN_URL', 'https://google.com/'); // <- Change the domain of your website.
define('LENGTH_OF_STRING', 7); // <- Change how long you wish for the generated string of uploaded images to be.

// DANGER ZONE: Don't edit unless you know what you're doing.
define('UPLOAD_DIRECTORY', 'images/'); // if you change this you need to edit .htaccess as well (must end with a slash)