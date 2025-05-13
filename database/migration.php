<?php
// database/migration.php - Create database and tables
require_once '../config/config.php';

// Connect to MySQL without database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('admin', 'regular', 'premium') DEFAULT 'regular'
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully or already exists<br>";
} else {
    echo "Error creating table 'users': " . $conn->error . "<br>";
}

// Create lists table
$sql = "CREATE TABLE IF NOT EXISTS lists (
    list_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(20) DEFAULT '#3498db',
    icon VARCHAR(50) DEFAULT 'list',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'lists' created successfully or already exists<br>";
} else {
    echo "Error creating table 'lists': " . $conn->error . "<br>";
}

// Create tasks table
$sql = "CREATE TABLE IF NOT EXISTS tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATETIME,
    reminder DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (list_id) REFERENCES lists(list_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'tasks' created successfully or already exists<br>";
} else {
    echo "Error creating table 'tasks': " . $conn->error . "<br>";
}

// Create tags table
$sql = "CREATE TABLE IF NOT EXISTS tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'tags' created successfully or already exists<br>";
} else {
    echo "Error creating table 'tags': " . $conn->error . "<br>";
}

// Create task_tags table (relationship between tasks and tags)
$sql = "CREATE TABLE IF NOT EXISTS task_tags (
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'task_tags' created successfully or already exists<br>";
} else {
    echo "Error creating table 'task_tags': " . $conn->error . "<br>";
}

// Create subtasks table
$sql = "CREATE TABLE IF NOT EXISTS subtasks (
    subtask_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'subtasks' created successfully or already exists<br>";
} else {
    echo "Error creating table 'subtasks': " . $conn->error . "<br>";
}

// Create comments table
$sql = "CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'comments' created successfully or already exists<br>";
} else {
    echo "Error creating table 'comments': " . $conn->error . "<br>";
}

// Create attachments table
$sql = "CREATE TABLE IF NOT EXISTS attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'attachments' created successfully or already exists<br>";
} else {
    echo "Error creating table 'attachments': " . $conn->error . "<br>";
}

// Create activity_logs table
$sql = "CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'complete', 'login', 'share') NOT NULL,
    entity_type ENUM('task', 'list', 'user', 'tag', 'subtask', 'comment', 'attachment') NOT NULL,
    entity_id INT NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'activity_logs' created successfully or already exists<br>";
} else {
    echo "Error creating table 'activity_logs': " . $conn->error . "<br>";
}

// Create collaborators table
$sql = "CREATE TABLE IF NOT EXISTS collaborators (
    collaboration_id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('list', 'task') NOT NULL,
    entity_id INT NOT NULL,
    user_id INT NOT NULL,
    permission ENUM('view', 'edit', 'admin') DEFAULT 'view',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'collaborators' created successfully or already exists<br>";
} else {
    echo "Error creating table 'collaborators': " . $conn->error . "<br>";
}

// Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    type ENUM('reminder', 'mention', 'share', 'comment', 'system') NOT NULL,
    entity_type ENUM('task', 'list', 'comment', 'system') NOT NULL,
    entity_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'notifications' created successfully or already exists<br>";
} else {
    echo "Error creating table 'notifications': " . $conn->error . "<br>";
}

// Add indexes for better performance
$conn->query("CREATE INDEX IF NOT EXISTS idx_tasks_user_id ON tasks(user_id)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_tasks_list_id ON tasks(list_id)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_tasks_due_date ON tasks(due_date)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_lists_user_id ON lists(user_id)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_tags_user_id ON tags(user_id)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_subtasks_task_id ON subtasks(task_id)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_comments_task_id ON comments(task_id)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)");
$conn->query("CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id)");

echo "<br>Database setup completed successfully!";

// Close connection
$conn->close();