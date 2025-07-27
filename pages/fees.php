<?php
include '../config/db.php';
include '../includes/sidebar.php';
session_start();

if (!isset($_SESSION['active_session'])) {
    echo "<div class='alert alert-danger m-3'>No active session selected. Please set an academic session first.</div>";
    exit;
}

$session_id = $_SESSION['active_session'];
$success = '';
$error = '';

// Fetch all classes
$classes = $pdo->query("SELECT * FROM classes ORDER BY class_name")->fetchAll();

// Handle create fee type
if (isset($_POST['new_fee_type']) && !empty(trim($_POST['new_fee_type']))) {
    $new_type = trim($_POST['new_fee_type']);
    try {
        $stmt = $pdo->prepare("INSERT INTO fee_types (type_name) VALUES (?)");
        $stmt->execute([$new_type]);
        $success = "New fee type '$new_type' added successfully!";
    } catch (PDOException $e) {
        $error = "Error adding fee type: " . $e->getMessage();
    }
}

// Fetch all fee types
$fee_types = $pdo->query("SELECT type_name FROM fee_types ORDER BY type_name")->fetchAll(PDO::FETCH_COLUMN);

// Get class and section selection
$class_id = $_GET['class_id'] ?? '';
$section_id = $_GET['section_id'] ?? '';

// Fetch sections based on class
$sections = [];
if ($class_id) {
    $stmt = $pdo->prepare("SELECT * FROM sections WHERE class_id = ? ORDER BY section_name");
    $stmt->execute([$class_id]);
    $sections = $stmt->fetchAll();
}

// Fetch students based on class & section
$students = [];
if ($class_id && $section_id) {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE class_id = ? AND section_id = ? AND session_id = ? ORDER BY first_name");
    $stmt->execute([$class_id, $section_id, $session_id]);
    $students = $stmt->fetchAll();
}

// Handle fee submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    try {
        $student_id = $_POST['student_id'];
        $amount = $_POST['amount'];
        $fee_type = $_POST['fee_type'];
        $payment_date = $_POST['payment_date'];
        $payment_status = $_POST['payment_status'];
        $description = $_POST['description'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO fees (session_id, student_id, amount, fee_type, payment_date, payment_status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$session_id, $student_id, $amount, $fee_type, $payment_date, $payment_status, $description]);
        $success = "Fee recorded successfully for student ID $student_id!";
    } catch (PDOException $e) {
        $error = "Error recording fee: " . $e->getMessage();
    }
}

// Fetch all fees with student info
$stmt = $pdo->prepare("SELECT f.*, s.first_name, s.last_name, c.class_name, sec.section_name 
    FROM fees f 
    JOIN students s ON f.student_id = s.id 
    JOIN classes c ON s.class_id = c.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE f.session_id = ?
    ORDER BY f.payment_date DESC, f.created_at DESC");
$stmt->execute([$session_id]);
$fee_records = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Management | School System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
            padding: 1rem 1.35rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .table {
            font-size: 0.9rem;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #5a5c69;
        }
        
        .badge-paid {
            background-color: var(--success-color);
        }
        
        .badge-unpaid {
            background-color: var(--danger-color);
        }
        
        .badge-partial {
            background-color: #f6c23e;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box .bi-search {
            position: absolute;
            top: 10px;
            left: 10px;
            color: #d1d3e2;
        }
        
        .search-box .form-control {
            padding-left: 2.5rem;
            border-radius: 10rem;
            background-color: #f8f9fc;
        }
        
        .nav-pills .nav-link.active {
            background-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar text-white vh-100 position-fixed" style="width: 14rem;">
            <?php include '../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-grow-1" style="margin-left: 14rem;">
            <div class="container-fluid py-4 px-5">
                <!-- Page Heading -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="bi bi-cash-stack me-2"></i>Fees Management
                    </h1>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control" placeholder="Search fees..." style="width: 250px;">
                    </div>
                </div>
                
                <!-- Alerts -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Fee Entry Section -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-plus-circle me-2"></i>Record New Fee</span>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Class</label>
                                        <select name="class_id" class="form-select" required onchange="this.form.submit()">
                                            <option value="">-- Select Class --</option>
                                            <?php foreach ($classes as $class): ?>
                                                <option value="<?= $class['id'] ?>" <?= ($class_id == $class['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($class['class_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Section</label>
                                        <select name="section_id" class="form-select" required onchange="this.form.submit()" <?= empty($sections) ? 'disabled' : '' ?>>
                                            <option value="">-- Select Section --</option>
                                            <?php foreach ($sections as $sec): ?>
                                                <option value="<?= $sec['id'] ?>" <?= ($section_id == $sec['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($sec['section_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>

                                <?php if (!empty($students)): ?>
                                <form method="POST">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Student</label>
                                            <select name="student_id" class="form-select" required>
                                                <option value="">-- Select Student --</option>
                                                <?php foreach ($students as $student): ?>
                                                    <option value="<?= $student['id'] ?>">
                                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Amount (Rs.)</label>
                                            <input type="number" step="0.01" name="amount" class="form-control" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Fee Type</label>
                                            <select name="fee_type" class="form-select" required>
                                                <option value="">-- Select Type --</option>
                                                <?php foreach ($fee_types as $type): ?>
                                                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Payment Date</label>
                                            <input type="date" name="payment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Status</label>
                                            <select name="payment_status" class="form-select">
                                                <option value="Paid">Paid</option>
                                                <option value="Unpaid">Unpaid</option>
                                                <option value="Partial">Partial</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Description (Optional)</label>
                                            <textarea name="description" class="form-control" rows="2"></textarea>
                                        </div>

                                        <div class="col-12 mt-3">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="bi bi-save me-2"></i>Record Fee
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Types Section -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="bi bi-tags me-2"></i>Manage Fee Types
                            </div>
                            <div class="card-body">
                                <form method="POST" class="mb-4">
                                    <div class="input-group">
                                        <input type="text" name="new_fee_type" class="form-control" placeholder="Enter new fee type..." required>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-1"></i>Add
                                        </button>
                                    </div>
                                </form>

                                <h6 class="mb-3">Available Fee Types</h6>
                                <?php if (empty($fee_types)): ?>
                                    <div class="alert alert-info">No fee types defined yet. Add one above.</div>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($fee_types as $type): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary py-2 px-3">
                                                <?= htmlspecialchars($type) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fee Records Table -->
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-list-check me-2"></i>Fee Records</span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item" href="#">All Records</a></li>
                                <li><a class="dropdown-item" href="#">This Month</a></li>
                                <li><a class="dropdown-item" href="#">Paid</a></li>
                                <li><a class="dropdown-item" href="#">Unpaid</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Class</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fee_records)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No fee records found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fee_records as $fee): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?></td>
                                            <td><?= $fee['class_name'] . ' ' . $fee['section_name'] ?></td>
                                            <td><?= $fee['fee_type'] ?></td>
                                            <td>Rs. <?= number_format($fee['amount'], 2) ?></td>
                                            <td><?= date('d M Y', strtotime($fee['payment_date'])) ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?= 
                                                    $fee['payment_status'] === 'Paid' ? 'badge-paid' : 
                                                    ($fee['payment_status'] === 'Unpaid' ? 'badge-unpaid' : 'badge-partial') 
                                                ?>">
                                                    <?= $fee['payment_status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-receipt"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Auto-focus search input
        document.querySelector('.search-box input').focus();
    </script>
</body>
</html>