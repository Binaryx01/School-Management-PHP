<?php
include '../config/db.php';
include '../includes/sidebar.php';
session_start();

// Handle new session form submission
if (isset($_POST['create_session'])) {
    $session_name = trim($_POST['session_name']);
    if (!empty($session_name)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM school_sessions WHERE session_name = ?");
        $stmt->execute([$session_name]);
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            $stmt = $pdo->prepare("INSERT INTO school_sessions (session_name) VALUES (?)");
            $stmt->execute([$session_name]);
        }
    }
}

// Handle set active session
if (isset($_POST['set_active'])) {
    $session_id = intval($_POST['session_id']);

    $pdo->query("UPDATE school_sessions SET is_active = 0");
    $stmt = $pdo->prepare("UPDATE school_sessions SET is_active = 1 WHERE id = ?");
    $stmt->execute([$session_id]);

    $_SESSION['active_session'] = $session_id;
}

// Fetch all sessions
$sessions = $pdo->query("SELECT * FROM school_sessions ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Sessions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
        }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #343a40;
            color: #fff;
        }
        .main-content {
            flex-grow: 1;
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
        <h3 class="mb-4">Academic Sessions</h3>

        <!-- Create new session -->
        <form method="POST" class="row g-3 mb-4">
            <div class="col-auto">
                <input type="text" name="session_name" class="form-control" placeholder="e.g., 2024/25" required>
            </div>
            <div class="col-auto">
                <button type="submit" name="create_session" class="btn btn-primary">Add Session</button>
            </div>
        </form>

        <!-- List existing sessions -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Session Name</th>
                    <th>Status</th>
                    <th>Set Active</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['session_name']) ?></td>
                    <td>
                        <?= $row['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                    </td>
                    <td>
                        <?php if (!$row['is_active']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="session_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="set_active" class="btn btn-sm btn-outline-primary">Set Active</button>
                        </form>
                        <?php else: ?>
                            <button class="btn btn-sm btn-success" disabled>Active</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
