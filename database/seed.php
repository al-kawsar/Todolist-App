<?php
// database/seed.php - Insert sample data for testing
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';
require_once '../utils/auth.php';

// Sample Users
$users = [
    [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'Password123',
        'full_name' => 'John Doe'
    ],
    [
        'username' => 'janedoe',
        'email' => 'jane@example.com',
        'password' => 'Password123',
        'full_name' => 'Jane Doe'
    ]
];

// Insert sample users
foreach ($users as $user) {
    // Check if the user already exists
    $sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $user['username'], $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User doesn't exist, create it
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", 
            $user['username'], 
            $user['email'], 
            $hashedPassword, 
            $user['full_name']
        );
        
        $stmt->execute();
        
        echo "User {$user['username']} created successfully<br>";
    } else {
        echo "User {$user['username']} already exists<br>";
    }
    
    $stmt->close();
}

// Get user IDs
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$username = 'johndoe';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$johnId = $user['user_id'];
$stmt->close();

$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
$username = 'janedoe';
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$janeId = $user['user_id'];
$stmt->close();

// Sample Lists
$lists = [
    [
        'user_id' => $johnId,
        'title' => 'Work Tasks',
        'description' => 'All my work-related tasks',
        'color' => '#3498db',
        'icon' => 'briefcase'
    ],
    [
        'user_id' => $johnId,
        'title' => 'Personal Tasks',
        'description' => 'Personal to-dos and errands',
        'color' => '#e74c3c',
        'icon' => 'home'
    ],
    [
        'user_id' => $johnId,
        'title' => 'Shopping List',
        'description' => 'Items to buy',
        'color' => '#2ecc71',
        'icon' => 'shopping-cart'
    ],
    [
        'user_id' => $janeId,
        'title' => 'Study Plan',
        'description' => 'Courses and study materials',
        'color' => '#9b59b6',
        'icon' => 'book'
    ]
];

// Insert sample lists
foreach ($lists as $list) {
    // Check if the list already exists
    $sql = "SELECT list_id FROM lists WHERE user_id = ? AND title = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $list['user_id'], $list['title']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // List doesn't exist, create it
        $sql = "INSERT INTO lists (user_id, title, description, color, icon) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", 
            $list['user_id'], 
            $list['title'], 
            $list['description'], 
            $list['color'], 
            $list['icon']
        );
        
        $stmt->execute();
        
        echo "List '{$list['title']}' created successfully<br>";
    } else {
        echo "List '{$list['title']}' already exists<br>";
    }
    
    $stmt->close();
}

// Get list IDs
$workListId = null;
$personalListId = null;
$shoppingListId = null;
$studyListId = null;

$sql = "SELECT list_id, title FROM lists WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $johnId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['title'] === 'Work Tasks') {
        $workListId = $row['list_id'];
    } elseif ($row['title'] === 'Personal Tasks') {
        $personalListId = $row['list_id'];
    } elseif ($row['title'] === 'Shopping List') {
        $shoppingListId = $row['list_id'];
    }
}

$stmt->close();

$sql = "SELECT list_id, title FROM lists WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $janeId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['title'] === 'Study Plan') {
        $studyListId = $row['list_id'];
    }
}

$stmt->close();

// Sample Tags
$tags = [
    [
        'user_id' => $johnId,
        'name' => 'Urgent',
        'color' => '#e74c3c'
    ],
    [
        'user_id' => $johnId,
        'name' => 'Important',
        'color' => '#f39c12'
    ],
    [
        'user_id' => $johnId,
        'name' => 'Later',
        'color' => '#3498db'
    ],
    [
        'user_id' => $janeId,
        'name' => 'Study',
        'color' => '#9b59b6'
    ],
    [
        'user_id' => $janeId,
        'name' => 'Project',
        'color' => '#2ecc71'
    ]
];

// Insert sample tags
foreach ($tags as $tag) {
    // Check if the tag already exists
    $sql = "SELECT tag_id FROM tags WHERE user_id = ? AND name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tag['user_id'], $tag['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Tag doesn't exist, create it
        $sql = "INSERT INTO tags (user_id, name, color) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", 
            $tag['user_id'], 
            $tag['name'], 
            $tag['color']
        );
        
        $stmt->execute();
        
        echo "Tag '{$tag['name']}' created successfully<br>";
    } else {
        echo "Tag '{$tag['name']}' already exists<br>";
    }
    
    $stmt->close();
}

// Get tag IDs
$urgentTagId = null;
$importantTagId = null;
$laterTagId = null;
$studyTagId = null;
$projectTagId = null;

$sql = "SELECT tag_id, name FROM tags WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $johnId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['name'] === 'Urgent') {
        $urgentTagId = $row['tag_id'];
    } elseif ($row['name'] === 'Important') {
        $importantTagId = $row['tag_id'];
    } elseif ($row['name'] === 'Later') {
        $laterTagId = $row['tag_id'];
    }
}

$stmt->close();

$sql = "SELECT tag_id, name FROM tags WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $janeId);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if ($row['name'] === 'Study') {
        $studyTagId = $row['tag_id'];
    } elseif ($row['name'] === 'Project') {
        $projectTagId = $row['tag_id'];
    }
}

$stmt->close();

// Sample Tasks
$currentDate = date('Y-m-d H:i:s');
$tomorrow = date('Y-m-d H:i:s', strtotime('+1 day'));
$nextWeek = date('Y-m-d H:i:s', strtotime('+1 week'));

$tasks = [
    [
        'list_id' => $workListId,
        'user_id' => $johnId,
        'title' => 'Complete project proposal',
        'description' => 'Finish the draft and submit to the manager',
        'priority' => 'high',
        'status' => 'in_progress',
        'due_date' => $tomorrow,
        'tags' => [$urgentTagId, $importantTagId]
    ],
    [
        'list_id' => $workListId,
        'user_id' => $johnId,
        'title' => 'Schedule team meeting',
        'description' => 'Coordinate with all team members for the weekly sync',
        'priority' => 'medium',
        'status' => 'pending',
        'due_date' => $nextWeek,
        'tags' => [$importantTagId]
    ],
    [
        'list_id' => $personalListId,
        'user_id' => $johnId,
        'title' => 'Pay utility bills',
        'description' => 'Electricity, water, and internet bills',
        'priority' => 'high',
        'status' => 'pending',
        'due_date' => $tomorrow,
        'tags' => [$urgentTagId]
    ],
    [
        'list_id' => $personalListId,
        'user_id' => $johnId,
        'title' => 'Schedule dentist appointment',
        'description' => 'Call the dentist and book for next month',
        'priority' => 'low',
        'status' => 'pending',
        'due_date' => $nextWeek,
        'tags' => [$laterTagId]
    ],
    [
        'list_id' => $shoppingListId,
        'user_id' => $johnId,
        'title' => 'Buy groceries',
        'description' => 'Milk, eggs, bread, vegetables',
        'priority' => 'medium',
        'status' => 'pending',
        'due_date' => $tomorrow,
        'tags' => []
    ],
    [
        'list_id' => $studyListId,
        'user_id' => $janeId,
        'title' => 'Complete online course',
        'description' => 'Finish the web development course',
        'priority' => 'high',
        'status' => 'in_progress',
        'due_date' => $nextWeek,
        'tags' => [$studyTagId, $projectTagId]
    ]
];

// Insert sample tasks
foreach ($tasks as $task) {
    // Check if the task already exists
    $sql = "SELECT task_id FROM tasks WHERE user_id = ? AND title = ? AND list_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $task['user_id'], $task['title'], $task['list_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Task doesn't exist, create it
        $sql = "INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssss", 
            $task['list_id'], 
            $task['user_id'], 
            $task['title'], 
            $task['description'], 
            $task['priority'], 
            $task['status'], 
            $task['due_date']
        );
        
        $stmt->execute();
        $taskId = $conn->insert_id;
        
        // Add tags to the task
        if (!empty($task['tags'])) {
            $tagSql = "INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)";
            $tagStmt = $conn->prepare($tagSql);
            
            foreach ($task['tags'] as $tagId) {
                $tagStmt->bind_param("ii", $taskId, $tagId);
                $tagStmt->execute();
            }
            
            $tagStmt->close();
        }
        
        // Create sample subtasks for this task
        $subtasks = [
            "Step 1 for " . $task['title'],
            "Step 2 for " . $task['title']
        ];
        
        $subtaskSql = "INSERT INTO subtasks (task_id, title) VALUES (?, ?)";
        $subtaskStmt = $conn->prepare($subtaskSql);
        
        foreach ($subtasks as $subtaskTitle) {
            $subtaskStmt->bind_param("is", $taskId, $subtaskTitle);
            $subtaskStmt->execute();
        }
        
        $subtaskStmt->close();
        
        echo "Task '{$task['title']}' created successfully<br>";
    } else {
        echo "Task '{$task['title']}' already exists<br>";
    }
    
    $stmt->close();
}

echo "<br>Sample data seeded successfully!";