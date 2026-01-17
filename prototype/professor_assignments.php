<?php

require_once 'config.php';
require_once 'Course.php';
require_once 'Assignment.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 2) {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden Action - You do not have permission to access this page.');
}

$userId = getCurrentUserId();
$course = new Course();
$assignment = new Assignment();

$message = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$assignmentId = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

$selectedAssignment = null;
$submissions = [];

// Get assignment details if viewing
if ($assignmentId) {
    $selectedAssignment = $assignment->getAssignmentById($assignmentId);
    $submissions = $assignment->getSubmissionsByAssignment($assignmentId);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_assignment'])) {
        $cid = intval($_POST['course_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $dueDate = $_POST['due_date'];
        $maxPoints = intval($_POST['max_points']);
        
        $result = $assignment->createAssignment($cid, $title, $description, $dueDate, $maxPoints, $userId);
        if ($result['success']) {
            $message = $result['message'];
            $action = 'list';
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['delete_assignment'])) {
        $result = $assignment->deleteAssignment($assignmentId);
        if ($result['success']) {
            $message = $result['message'];
            $action = 'list';
            $assignmentId = 0;
        } else {
            $error = $result['message'];
        }
    }
}

// Get professor's courses
$myCourses = $course->getCoursesByProfessor($userId);

// Get all assignments for selected course or all courses
if ($courseId) {
    $assignments = $assignment->getAssignmentsByCourse($courseId);
    $selectedCourse = $course->getCourseById($courseId);
} else {
    $assignments = [];
    foreach ($myCourses as $c) {
        $courseAssignments = $assignment->getAssignmentsByCourse($c['course_id']);
        foreach ($courseAssignments as &$a) {
            $a['course_code'] = $c['course_code'];
            $a['course_name'] = $c['course_name'];
        }
        $assignments = array_merge($assignments, $courseAssignments);
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments - Metropolitan College</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><a href="index.php" style="color: inherit; text-decoration: none;">🎓 Metropolitan College</a></h1>
            </div>
            <div class="nav-menu" id="navMenu">
                <a href="professor_dashboard.php" class="nav-link">Dashboard</a>
                <a href="professor_courses.php" class="nav-link">My Courses</a>
                <a href="professor_assignments.php" class="nav-link active">Assignments</a>
                <a href="professor_students.php" class="nav-link">Students</a>
                <a href="logout.php" class="nav-link">Logout</a>
                <button class="nav-toggle" id="navToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo sanitizeOutput($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>

            <?php if ($action == 'create'): ?>
                <!-- Create Assignment Form -->
                <div class="back-link">
                    <a href="professor_assignments.php">← Back to Assignments</a>
                </div>
                
                <div class="form-section">
                    <h2>Create New Assignment</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="course_id">Select Course *</label>
                            <select id="course_id" name="course_id" required class="form-control">
                                <option value="">-- Select Course --</option>
                                <?php foreach ($myCourses as $c): ?>
                                    <option value="<?php echo $c['course_id']; ?>" <?php echo ($courseId == $c['course_id']) ? 'selected' : ''; ?>>
                                        <?php echo sanitizeOutput($c['course_code'] . ' - ' . $c['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Assignment Title *</label>
                            <input type="text" id="title" name="title" required maxlength="200" placeholder="e.g., Midterm Project">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="8" required placeholder="Provide detailed instructions for the assignment..."></textarea>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="due_date">Due Date *</label>
                                <input type="datetime-local" id="due_date" name="due_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_points">Maximum Points *</label>
                                <input type="number" id="max_points" name="max_points" required min="1" max="1000" value="100">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="create_assignment" class="btn btn-primary">Create Assignment</button>
                            <a href="professor_assignments.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>

            <?php elseif ($assignmentId && $selectedAssignment): ?>
                <!-- View Assignment & Submissions -->
                <div class="back-link">
                    <a href="professor_assignments.php<?php echo $courseId ? '?course_id='.$courseId : ''; ?>">← Back to Assignments</a>
                </div>
                
                <div class="assignment-detail">
                    <div class="assignment-detail-header">
                        <div>
                            <span class="course-badge"><?php echo sanitizeOutput($selectedAssignment['course_code']); ?></span>
                            <h1><?php echo sanitizeOutput($selectedAssignment['title']); ?></h1>
                            <p style="color: #666;"><?php echo sanitizeOutput($selectedAssignment['course_name']); ?></p>
                        </div>
                        <form method="POST" style="display: inline;">
                            <button type="button" onclick="if(confirm('Delete this assignment and all submissions?')) this.form.submit();" class="btn btn-secondary" style="background: #991b1b;">Delete Assignment</button>
                            <input type="hidden" name="delete_assignment" value="1">
                        </form>
                    </div>

                    <div class="assignment-info-grid">
                        <div class="info-item">
                            <strong>Due Date:</strong>
                            <span><?php echo date('M d, Y H:i', strtotime($selectedAssignment['due_date'])); ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Maximum Points:</strong>
                            <span><?php echo $selectedAssignment['max_points']; ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Total Submissions:</strong>
                            <span><?php echo count($submissions); ?></span>
                        </div>
                    </div>

                    <div class="assignment-description-full">
                        <h3>Assignment Description</h3>
                        <p><?php echo nl2br(sanitizeOutput($selectedAssignment['description'])); ?></p>
                    </div>

                    <!-- Submissions -->
                    <div class="section">
                        <h2>Student Submissions</h2>
                        <?php if (empty($submissions)): ?>
                            <div class="empty-state">
                                <p>No submissions yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Email</th>
                                            <th>Submitted</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $s): 
                                            $isLate = strtotime($selectedAssignment['due_date']) < strtotime($s['submitted_at']);
                                            $statusClass = !empty($s['points_earned']) ? 'graded' : ($isLate ? 'overdue' : 'submitted');
                                            $statusText = !empty($s['points_earned']) ? 'Graded' : ($isLate ? 'Late' : 'Pending');
                                        ?>
                                        <tr>
                                            <td><strong><?php echo sanitizeOutput($s['username']); ?></strong></td>
                                            <td><?php echo sanitizeOutput($s['email']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($s['submitted_at'])); ?></td>
                                            <td><span class="status-badge status-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                            <td>
                                                <?php if (!empty($s['points_earned'])): ?>
                                                    <?php echo number_format($s['points_earned'], 1); ?> / <?php echo $selectedAssignment['max_points']; ?>
                                                <?php else: ?>
                                                    Not graded
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="professor_grade.php?submission_id=<?php echo $s['submission_id']; ?>" class="btn btn-sm btn-primary">
                                                    <?php echo !empty($s['points_earned']) ? 'View/Edit Grade' : 'Grade'; ?>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- List Assignments -->
                <div class="section-header">
                    <h1>Assignments <?php echo $courseId ? '- ' . sanitizeOutput($selectedCourse['course_code']) : ''; ?></h1>
                    <a href="professor_assignments.php?action=create<?php echo $courseId ? '&course_id='.$courseId : ''; ?>" class="btn btn-primary">Create Assignment</a>
                </div>

                <!-- Course Filter -->
                <?php if (!$courseId): ?>
                <div class="filter-section">
                    <label>Filter by Course:</label>
                    <select onchange="if(this.value) window.location.href='professor_assignments.php?course_id='+this.value; else window.location.href='professor_assignments.php';" class="form-control" style="max-width: 300px;">
                        <option value="">All Courses</option>
                        <?php foreach ($myCourses as $c): ?>
                            <option value="<?php echo $c['course_id']; ?>">
                                <?php echo sanitizeOutput($c['course_code'] . ' - ' . $c['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <p>No assignments created yet.</p>
                        <a href="professor_assignments.php?action=create" class="btn btn-primary">Create Your First Assignment</a>
                    </div>
                <?php else: ?>
                    <div class="assignments-list">
                        <?php foreach ($assignments as $a): 
                            $isPast = strtotime($a['due_date']) < time();
                        ?>
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <div>
                                    <?php if (!$courseId): ?>
                                        <span class="course-badge"><?php echo sanitizeOutput($a['course_code']); ?></span>
                                    <?php endif; ?>
                                    <h3 class="assignment-title"><?php echo sanitizeOutput($a['title']); ?></h3>
                                </div>
                                <?php if ($isPast): ?>
                                    <span class="status-badge status-overdue">Past Due</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">Active</span>
                                <?php endif; ?>
                            </div>
                            <div class="assignment-meta">
                                <span>📅 Due: <?php echo date('M d, Y H:i', strtotime($a['due_date'])); ?></span>
                                <span>🎯 <?php echo $a['max_points']; ?> points</span>
                                <span>📝 <?php echo $a['total_submissions']; ?> submission<?php echo $a['total_submissions'] != 1 ? 's' : ''; ?></span>
                            </div>
                            <div class="assignment-actions">
                                <a href="professor_assignments.php?assignment_id=<?php echo $a['assignment_id']; ?>" class="btn btn-sm btn-primary">View Submissions</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025-2026 Metropolitan College. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>