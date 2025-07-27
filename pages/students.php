<?php
session_start();
include '../config/db.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['active_session'])) {
    die("No active session selected. Please set an academic session first.");
}
$session_id = $_SESSION['active_session'];

// Handle Add Student
if (isset($_POST['add_student'])) {
    $first_name     = $_POST['first_name'];
    $last_name      = $_POST['last_name'];
    $date_of_birth  = $_POST['date_of_birth'];
    $gender         = $_POST['gender'];
    $class_id       = $_POST['class_id'];
    $section_id     = $_POST['section_id'];
    $guardian_name  = $_POST['guardian_name'];
    $contact_number = $_POST['contact_number'];
    $address        = $_POST['address'];

    $stmt = $pdo->prepare("INSERT INTO students 
        (session_id, first_name, last_name, date_of_birth, gender, class_id, section_id, guardian_name, contact_number, address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $session_id, $first_name, $last_name, $date_of_birth, $gender, $class_id, $section_id,
        $guardian_name, $contact_number, $address
    ]);

    // Redirect to avoid resubmission on refresh
    header("Location: students.php");
    exit();
}

// Handle Delete Student
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $pdo->prepare("DELETE FROM students WHERE id = ? AND session_id = ?");
    $del_stmt->execute([$delete_id, $session_id]);
    header("Location: students.php");
    exit();
}

// Handle Edit Student - Load existing data
$edit_student = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_stmt = $pdo->prepare("SELECT * FROM students WHERE id = ? AND session_id = ?");
    $edit_stmt->execute([$edit_id, $session_id]);
    $edit_student = $edit_stmt->fetch();
}

// Handle Update Student
if (isset($_POST['update_student'])) {
    $id             = $_POST['id'];
    $first_name     = $_POST['first_name'];
    $last_name      = $_POST['last_name'];
    $date_of_birth  = $_POST['date_of_birth'];
    $gender         = $_POST['gender'];
    $class_id       = $_POST['class_id'];
    $section_id     = $_POST['section_id'];
    $guardian_name  = $_POST['guardian_name'];
    $contact_number = $_POST['contact_number'];
    $address        = $_POST['address'];

    $update_stmt = $pdo->prepare("UPDATE students SET
        first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, class_id = ?, section_id = ?, guardian_name = ?, contact_number = ?, address = ?
        WHERE id = ? AND session_id = ?");
    $update_stmt->execute([
        $first_name, $last_name, $date_of_birth, $gender, $class_id, $section_id,
        $guardian_name, $contact_number, $address, $id, $session_id
    ]);

    header("Location: students.php");
    exit();
}

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $students = $pdo->prepare("SELECT s.*, c.class_name, sec.section_name FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN sections sec ON s.section_id = sec.id
        WHERE s.session_id = ? AND (s.first_name LIKE ? OR s.last_name LIKE ?)
        ORDER BY s.id DESC");
    $like_search = "%$search%";
    $students->execute([$session_id, $like_search, $like_search]);
} else {
    $students = $pdo->prepare("SELECT s.*, c.class_name, sec.section_name FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN sections sec ON s.section_id = sec.id
        WHERE s.session_id = ?
        ORDER BY s.id DESC");
    $students->execute([$session_id]);
}

$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name ASC")->fetchAll();
$sections = $pdo->query("SELECT * FROM sections ORDER BY section_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="main-content p-4" style="margin-left: 250px;">
    <h3 class="mb-4">Manage Students</h3>

    <!-- Search Form -->
    <form method="GET" class="mb-4 w-50">
        <div class="input-group">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by First or Last Name">
            <button class="btn btn-outline-primary" type="submit">Search</button>
            <?php if($search !== ''): ?>
                <a href="students.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Add/Edit Student Form -->
    <form method="POST" class="row g-3 mb-5">
        <?php if ($edit_student): ?>
            <input type="hidden" name="id" value="<?= $edit_student['id'] ?>">
        <?php endif; ?>

        <div class="col-md-3">
            <input type="text" name="first_name" class="form-control" placeholder="First Name" required
                value="<?= $edit_student ? htmlspecialchars($edit_student['first_name']) : '' ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="last_name" class="form-control" placeholder="Last Name" required
                value="<?= $edit_student ? htmlspecialchars($edit_student['last_name']) : '' ?>">
        </div>
        <div class="col-md-3">
            <input type="date" name="date_of_birth" class="form-control" required
                value="<?= $edit_student ? htmlspecialchars($edit_student['date_of_birth']) : '' ?>">
        </div>
        <div class="col-md-3">
            <select name="gender" class="form-select" required>
                <option value="" disabled <?= !$edit_student ? 'selected' : '' ?>>Gender</option>
                <option <?= $edit_student && $edit_student['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                <option <?= $edit_student && $edit_student['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                <option <?= $edit_student && $edit_student['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="class_id" class="form-select" required>
                <option value="" disabled <?= !$edit_student ? 'selected' : '' ?>>Select Class</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?= $class['id'] ?>" <?= $edit_student && $edit_student['class_id'] == $class['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($class['class_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="section_id" class="form-select" required>
                <option value="" disabled <?= !$edit_student ? 'selected' : '' ?>>Select Section</option>
                <?php foreach ($sections as $section): ?>
                    <option value="<?= $section['id'] ?>" <?= $edit_student && $edit_student['section_id'] == $section['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($section['section_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="guardian_name" class="form-control" placeholder="Guardian Name" required
                value="<?= $edit_student ? htmlspecialchars($edit_student['guardian_name']) : '' ?>">
        </div>
        <div class="col-md-3">
            <input type="text" name="contact_number" class="form-control" placeholder="Contact Number" required
                value="<?= $edit_student ? htmlspecialchars($edit_student['contact_number']) : '' ?>">
        </div>
        <div class="col-md-6">
            <input type="text" name="address" class="form-control" placeholder="Address" required
                value="<?= $edit_student ? htmlspecialchars($edit_student['address']) : '' ?>">
        </div>
        <div class="col-md-2">
            <?php if ($edit_student): ?>
                <button type="submit" name="update_student" class="btn btn-success w-100">Update Student</button>
                <a href="students.php" class="btn btn-secondary mt-2 w-100">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_student" class="btn btn-primary w-100">Add Student</button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Students Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Full Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Class</th>
                <th>Section</th>
                <th>Guardian</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $students->fetch()): ?>
            <tr>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['date_of_birth']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= htmlspecialchars($row['class_name']) ?></td>
                <td><?= htmlspecialchars($row['section_name']) ?></td>
                <td><?= htmlspecialchars($row['guardian_name']) ?></td>
                <td><?= htmlspecialchars($row['contact_number']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td>
                    <a href="?edit_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this student?')" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
