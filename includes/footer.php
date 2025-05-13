<?php
// includes/footer.php - Footer component
// This file should be included in all pages that require the footer

// Make sure config is loaded
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}
?>

<footer class="bg-white mt-auto">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <div class="text-gray-500 text-sm">
                &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
            </div>
            <div class="flex space-x-6">
                <a href="#" class="text-gray-400 hover:text-gray-500">
                    <i class="fab fa-github"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-gray-500">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="text-gray-400 hover:text-gray-500">
                    <i class="fab fa-linkedin"></i>
                </a>
            </div>
        </div>
    </div>
</footer>

