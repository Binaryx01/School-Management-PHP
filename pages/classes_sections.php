<?php
include '../config/db.php';
include '../includes/sidebar.php';
session_start();

// Get active session ID from session
$active_session = $_SESSION['active_session'] ?? null;
if (!$active_session) {
    die("No active session set. Please set an active session first.");
}

// Handle add class
if (isset($_POST['add_class'])) {
    $class_name = trim($_POST['class_name']);
    if ($class_name !== '') {
        $stmt = $pdo->prepare("INSERT INTO classes (class_name, session_id) VALUES (?, ?)");
        $stmt->execute([$class_name, $active_session]);
    }
}

// Handle add section
if (isset($_POST['add_section'])) {
    $section_name = trim($_POST['section_name']);
    $class_id = intval($_POST['class_id']);
    if ($section_name !== '' && $class_id > 0) {
        $stmt = $pdo->prepare("INSERT INTO sections (section_name, class_id, session_id) VALUES (?, ?, ?)");
        $stmt->execute([$section_name, $class_id, $active_session]);
    }
}

// Handle delete class
if (isset($_GET['delete_class'])) {
    $del_class_id = intval($_GET['delete_class']);
    // Delete sections first due to FK constraint (or cascade will handle)
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ? AND session_id = ?");
    $stmt->execute([$del_class_id, $active_session]);
    header("Location: classes_sections.php");
    exit;
}

// Handle delete section
if (isset($_GET['delete_section'])) {
    $del_section_id = intval($_GET['delete_section']);
    $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ? AND session_id = ?");
    $stmt->execute([$del_section_id, $active_session]);
    header("Location: classes_sections.php");
    exit;
}

// Fetch all classes with their sections
$stmt = $pdo->prepare("SELECT c.id AS class_id, c.class_name, s.id AS section_id, s.section_name 
                       FROM classes c
                       LEFT JOIN sections s ON c.id = s.class_id
                       WHERE c.session_id = ?
                       ORDER BY c.class_name, s.section_name");
$stmt->execute([$active_session]);
$rows = $stmt->fetchAll();

// Organize data into array: classes => sections
$classes = [];
foreach ($rows as $row) {
    $cid = $row['class_id'];
    if (!isset($classes[$cid])) {
        $classes[$cid] = [
            'name' => $row['class_name'],
            'sections' => []
        ];
    }
    if ($row['section_id']) {
        $classes[$cid]['sections'][$row['section_id']] = $row['section_name'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Classes & Sections Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { display: flex; margin: 0; }
        .sidebar { width: 250px; min-height: 100vh; background-color: #343a40; color: white; }
        .main-content { flex-grow: 1; padding: 2rem; background: #f8f9fa; }
        .section-list { margin-left: 2rem; }
    </style>
</head>
<body>
    <div class="sidebar">
        <?php include '../includes/sidebar.php'; ?>
    </div>
    <div class="main-content">
        <h3>Classes & Sections (Session ID: <?= htmlspecialchars($active_session) ?>)</h3>

        <!-- Add Class Form -->
        <form method="POST" class="row g-3 mb-4">
            <div class="col-auto">
                <input type="text" name="class_name" class="form-control" placeholder="New Class Name" required>
            </div>
            <div class="col-auto">
                <button type="submit" name="add_class" class="btn btn-primary">Add Class</button>
            </div>
        </form>

        <!-- List Classes and Sections -->
        <?php if (empty($classes)): ?>
            <p>No classes found for this session.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($classes as $cid => $class): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?= htmlspecialchars($class['name']) ?></strong>
                            <a href="?delete_class=<?= $cid ?>" 
                               onclick="return confirm('Delete this class and all its sections?');"
                               class="btn btn-sm btn-danger">Delete Class</a>
                        </div>

                        <!-- Sections under class -->
                        <ul class="list-group section-list mt-2">
                            <?php foreach ($class['sections'] as $sid => $section_name): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($section_name) ?>
                                    <a href="?delete_section=<?= $sid ?>" 
                                       onclick="return confirm('Delete this section?');"
                                       class="btn btn-sm btn-outline-danger">Delete Section</a>
                                </li>
                            <?php endforeach; ?>

                            <!-- Add Section Form -->
                            <li class="list-group-item">
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="class_id" value="<?= $cid ?>">
                                    <input type="text" name="section_name" class="form-control form-control-sm" placeholder="New Section Name" required>
                                    <button type="submit" name="add_section" class="btn btn-sm btn-success">Add Section</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
