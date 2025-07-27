<?php
session_start();
include '../config/db.php';
include '../includes/sidebar.php';

if (!isset($_SESSION['active_session'])) {
    die("No active session selected. Please set an academic session first.");
}
$session_id = $_SESSION['active_session'];

// Handle Add Teacher
if (isset($_POST['add_teacher'])) {
    $name         = $_POST['name'];
    $email        = $_POST['email'];
    $phone        = $_POST['phone'];
    $subject      = $_POST['subject'];
    $address      = $_POST['address'];
    $joining_date = $_POST['joining_date'];

    $stmt = $pdo->prepare("INSERT INTO teachers (session_id, name, email, phone, subject, address, joining_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$session_id, $name, $email, $phone, $subject, $address, $joining_date]);

    header("Location: teachers.php");
    exit();
}

// Handle Delete Teacher
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ? AND session_id = ?");
    $del_stmt->execute([$delete_id, $session_id]);
    header("Location: teachers.php");
    exit();
}

// Handle Edit Teacher - Load existing data
$edit_teacher = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_stmt = $pdo->prepare("SELECT * FROM teachers WHERE id = ? AND session_id = ?");
    $edit_stmt->execute([$edit_id, $session_id]);
    $edit_teacher = $edit_stmt->fetch();
}

// Handle Update Teacher
if (isset($_POST['update_teacher'])) {
    $id           = $_POST['id'];
    $name         = $_POST['name'];
    $email        = $_POST['email'];
    $phone        = $_POST['phone'];
    $subject      = $_POST['subject'];
    $address      = $_POST['address'];
    $joining_date = $_POST['joining_date'];

    $update_stmt = $pdo->prepare("UPDATE teachers SET name = ?, email = ?, phone = ?, subject = ?, address = ?, joining_date = ? WHERE id = ? AND session_id = ?");
    $update_stmt->execute([$name, $email, $phone, $subject, $address, $joining_date, $id, $session_id]);

    header("Location: teachers.php");
    exit();
}

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $teachers = $pdo->prepare("SELECT * FROM teachers WHERE session_id = ? AND (name LIKE ? OR subject LIKE ?) ORDER BY id DESC");
    $like_search = "%$search%";
    $teachers->execute([$session_id, $like_search, $like_search]);
} else {
    $teachers = $pdo->prepare("SELECT * FROM teachers WHERE session_id = ? ORDER BY id DESC");
    $teachers->execute([$session_id]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Teachers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="main-content p-4" style="margin-left: 250px;">
    <h3 class="mb-4">Manage Teachers</h3>

    <!-- Search Form -->
    <form method="GET" class="mb-4 w-50">
        <div class="input-group">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by Name or Subject">
            <button class="btn btn-outline-primary" type="submit">Search</button>
            <?php if ($search !== ''): ?>
                <a href="teachers.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Add/Edit Teacher Form -->
    <form method="POST" class="row g-3 mb-5">
        <?php if ($edit_teacher): ?>
            <input type="hidden" name="id" value="<?= $edit_teacher['id'] ?>">
        <?php endif; ?>

        <div class="col-md-4">
            <input type="text" name="name" class="form-control" placeholder="Full Name" required value="<?= $edit_teacher ? htmlspecialchars($edit_teacher['name']) : '' ?>">
        </div>
        <div class="col-md-4">
            <input type="email" name="email" class="form-control" placeholder="Email" required value="<?= $edit_teacher ? htmlspecialchars($edit_teacher['email']) : '' ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="phone" class="form-control" placeholder="Phone Number" required value="<?= $edit_teacher ? htmlspecialchars($edit_teacher['phone']) : '' ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="subject" class="form-control" placeholder="Subject" required value="<?= $edit_teacher ? htmlspecialchars($edit_teacher['subject']) : '' ?>">
        </div>
        <div class="col-md-4">
            <input type="date" name="joining_date" class="form-control" required value="<?= $edit_teacher ? htmlspecialchars($edit_teacher['joining_date']) : '' ?>">
        </div>
        <div class="col-md-12">
            <textarea name="address" class="form-control" placeholder="Address" rows="3" required><?= $edit_teacher ? htmlspecialchars($edit_teacher['address']) : '' ?></textarea>
        </div>
        <div class="col-md-2">
            <?php if ($edit_teacher): ?>
                <button type="submit" name="update_teacher" class="btn btn-success w-100">Update Teacher</button>
                <a href="teachers.php" class="btn btn-secondary mt-2 w-100">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_teacher" class="btn btn-primary w-100">Add Teacher</button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Teachers Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Subject</th>
                <th>Joining Date</th>
                <th>Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $teachers->fetch()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td><?= htmlspecialchars($row['joining_date']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td>
                    <a href="?edit_id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure to delete this teacher?')" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
