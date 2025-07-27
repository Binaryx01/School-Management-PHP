<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['active_session'])) {
    echo "<div class='alert alert-danger m-3'>No active session selected. Please set an academic session first.</div>";
    exit;
}

$session_id = $_SESSION['active_session'];
$success = '';
$class_id = $_GET['class_id'] ?? '';
$attendance_date = $_GET['attendance_date'] ?? date('Y-m-d');
$students = [];

// Fetch classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_date'])) {
    $attendance_date = $_POST['attendance_date'];
    $class_id = $_POST['class_id'];

    // Delete existing attendance for the same date/class
    $stmt = $pdo->prepare("DELETE FROM attendance WHERE session_id = ? AND class_id = ? AND date = ?");
    $stmt->execute([$session_id, $class_id, $attendance_date]);

    // Insert new attendance
    foreach ($_POST['status'] as $student_id => $status) {
        $stmt = $pdo->prepare("INSERT INTO attendance (session_id, student_id, class_id, date, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$session_id, $student_id, $class_id, $attendance_date, $status]);
    }

    $success = "Attendance saved successfully!";
}

// Load students
if (!empty($class_id)) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE class_id = ? AND session_id = ?");
    $stmt->execute([$class_id, $session_id]);
    $students = $stmt->fetchAll();
}

// Load existing attendance if exists
$attendance_map = [];
if (!empty($class_id) && !empty($attendance_date)) {
    $stmt = $pdo->prepare("SELECT student_id, status FROM attendance WHERE session_id = ? AND class_id = ? AND date = ?");
    $stmt->execute([$session_id, $class_id, $attendance_date]);
    $attendances = $stmt->fetchAll();
    foreach ($attendances as $a) {
        $attendance_map[$a['student_id']] = $a['status'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: #fff;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <?php include '../includes/sidebar.php'; ?>
    </div>

    <div class="main-content">
        <h3 class="mb-4">Attendance Management</h3>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Class & Date Selection -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="class_id" class="form-label">Class</label>
                <select name="class_id" id="class_id" class="form-select" required>
                    <option value="">-- Select Class --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['id'] ?>" <?= ($class_id == $class['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['class_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="attendance_date" class="form-label">Date</label>
                <input type="date" name="attendance_date" id="attendance_date" class="form-control" value="<?= htmlspecialchars($attendance_date) ?>" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Load Students</button>
            </div>
        </form>

        <!-- Attendance Form -->
        <?php if (!empty($students)): ?>
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">
            <input type="hidden" name="attendance_date" value="<?= $attendance_date ?>">

            <table class="table table-bordered bg-white">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Attendance Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                        <td>
                            <select name="status[<?= $student['id'] ?>]" class="form-select">
                                <?php
                                $current_status = $attendance_map[$student['id']] ?? '';
                                foreach (['Present', 'Absent', 'Late', 'Leave'] as $status_option):
                                ?>
                                <option value="<?= $status_option ?>" <?= ($current_status === $status_option) ? 'selected' : '' ?>>
                                    <?= $status_option ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" class="btn btn-success">Save Attendance</button>
        </form>
        <?php elseif (!empty($class_id)): ?>
            <div class="alert alert-warning">No students found for this class.</div>
        <?php endif; ?>
    </div>

</body>
</html>
