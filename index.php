<?php
// index.php - Main entry point
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'utils/auth.php';

redirect('login.php');

// Redirect to dashboard if user is logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Organize Your Tasks</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/output.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="node_modules/sweetalert2/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="node_modules/sweetalert2/dist/sweetalert2.min.css">
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <i class="fas fa-check-circle text-blue-500 text-3xl mr-2"></i>
                            <span class="text-xl font-bold text-gray-800"><?= APP_NAME ?></span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="login.php"
                            class="text-gray-700 hover:text-blue-500 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="register.php"
                            class="ml-4 px-4 py-2 rounded-md text-sm font-medium text-white bg-blue-500 hover:bg-blue-600">Sign
                            up</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="py-12 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                    <div>
                        <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight sm:text-5xl md:text-6xl">
                            <span class="block">Organize your tasks</span>
                            <span class="block text-blue-500">and boost productivity</span>
                        </h1>
                        <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg">
                            <?= APP_NAME ?> helps you organize your tasks, collaborate with others, and get more done
                            every day.
                        </p>
                        <div class="mt-8 sm:flex">
                            <div class="rounded-md shadow">
                                <a href="register.php"
                                    class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-500 hover:bg-blue-600 md:py-4 md:text-lg md:px-10">
                                    Get started for free
                                </a>
                            </div>
                            <div class="mt-3 sm:mt-0 sm:ml-3">
                                <a href="#features"
                                    class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-blue-100 hover:bg-blue-200 md:py-4 md:text-lg md:px-10">
                                    Learn more
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-10 md:mt-0">
                        <img src="assets/img/task-management.svg" alt="Task Management" class="w-full max-w-md mx-auto">
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                        Features
                    </h2>
                    <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                        Everything you need to stay organized and productive.
                    </p>
                </div>

                <div class="mt-10">
                    <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Feature 1 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                        <i class="fas fa-list-check text-white text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Task Management</h3>
                                        <p class="mt-1 text-sm text-gray-500">Create, organize and track your tasks in
                                            custom lists.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 2 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                        <i class="fas fa-bell text-white text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Reminders & Due Dates</h3>
                                        <p class="mt-1 text-sm text-gray-500">Never miss a deadline with reminders and
                                            due dates.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 3 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                        <i class="fas fa-tags text-white text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Tags & Categories</h3>
                                        <p class="mt-1 text-sm text-gray-500">Organize your tasks with tags and
                                            categories.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 4 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                                        <i class="fas fa-chart-line text-white text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Progress Tracking</h3>
                                        <p class="mt-1 text-sm text-gray-500">Track your productivity with visual
                                            statistics.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 5 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                                        <i class="fas fa-users text-white text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Collaboration</h3>
                                        <p class="mt-1 text-sm text-gray-500">Share lists and tasks with friends and
                                            colleagues.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Feature 6 -->
                        <div class="bg-white overflow-hidden shadow rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                        <i class="fas fa-mobile-alt text-white text-xl"></i>
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">Responsive Design</h3>
                                        <p class="mt-1 text-sm text-gray-500">Access your tasks from any device with our
                                            responsive design.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div class="text-gray-500 text-sm">
                        &copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.
                    </div>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-gray-500">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- jQuery -->
    <script src="node_modules/jquery/dist/jquery.min.js"></script>
</body>

</html>