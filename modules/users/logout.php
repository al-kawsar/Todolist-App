<?php
// modules/users/logout.php - Handle user logout
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';
require_once '../../utils/auth.php';

// Log the current user out
logoutUser();

// Redirect to login page
redirect('../../login.php');