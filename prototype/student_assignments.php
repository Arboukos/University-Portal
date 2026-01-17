<?php

require_once 'config.php';
require_once 'Assignment.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 1) {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden Action - You do not have permission to access this page.');
}

$userId = getCurrentUserId();
$assignment = new Assignment();

// Get all assignments for student
$assignments = $assignment->getAssignmentsForStudent($userId);

// Filter by status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filteredAssignments = $assignments;

if ($filter == 'pending') {
    $filteredAssignments = array_filter($assignments, function($a) {
        return empty($a['submission_id']) && strtotime($a['due_date']) >= time();
    });
} elseif ($filter == 'submitted') {
    $filteredAssignments = array_filter($assignments, function($a) {
        return !empty($a['submission_id']) && empty($a['points_earned']);
    });
} elseif ($filter == 'graded') {
    $filteredAssignments = array_filter($assignments, function($a) {
        return !empty($a['points_earned']);
    });
} elseif ($filter == 'overdue') {
    $filteredAssignments = array_filter($assignments, function($a) {
        return empty($a['submission_id']) && strtotime($a['due_date']) < time();
    });
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Metropolitan College</title>
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
                <a href="student_dashboard.php" class="nav-link">Dashboard</a>
                <a href="student_courses.php" class="nav-link">My Courses</a>
                <a href="student_assignments.php" class="nav-link active">Assignments</a>
                <a href="student_grades.php" class="nav-link">Grades</a>
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
            <h1>My Assignments</h1>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="student_assignments.php?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    All (<?php echo count($assignments); ?>)
                </a>
                <a href="student_assignments.php?filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                    Pending
                </a>
                <a href="student_assignments.php?filter=submitted" class="filter-tab <?php echo $filter == 'submitted' ? 'active' : ''; ?>">
                    Submitted
                </a>
                <a href="student_assignments.php?filter=graded" class="filter-tab <?php echo $filter == 'graded' ? 'active' : ''; ?>">
                    Graded
                </a>
                <a href="student_assignments.php?filter=overdue" class="filter-tab <?php echo $filter == 'overdue' ? 'active' : ''; ?>">
                    Overdue
                </a>
            </div>

            <!-- Assignments List -->
            <div class="section">
                <?php if (empty($filteredAssignments)): ?>
                    <div class="empty-state">
                        <p>No assignments found in this category.</p>
                        <?php if ($filter != 'all'): ?>
                            <a href="student_assignments.php" class="btn btn-secondary">View All Assignments</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="assignments-list">
                        <?php foreach ($filteredAssignments as $a): 
                            $isPast = strtotime($a['due_date']) < time();
                            $statusClass = !empty($a['points_earned']) ? 'graded' : (!empty($a['submission_id']) ? 'submitted' : ($isPast ? 'overdue' : 'pending'));
                            $statusText = !empty($a['points_earned']) ? 'Graded' : (!empty($a['submission_id']) ? 'Submitted' : ($isPast ? 'Overdue' : 'Pending'));
                        ?>
                        <div class="assignment-card">
                            <div class="assignment-header">
                                <div>
                                    <span class="course-badge"><?php echo sanitizeOutput($a['course_code']); ?></span>
                                    <h3 class="assignment-title"><?php echo sanitizeOutput($a['title']); ?></h3>
                                </div>
                                <span class="status-badge status-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </div>
                            <div class="assignment-meta">
                                <span>📅 Due: <?php echo date('M d, Y H:i', strtotime($a['due_date'])); ?></span>
                                <span>🎯 <?php echo $a['max_points']; ?> points</span>
                                <?php if (!empty($a['points_earned'])): ?>
                                    <span>✅ Score: <?php echo number_format($a['points_earned'], 1); ?>/<?php echo $a['grade_max_points']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="assignment-description">
                                <?php echo nl2br(sanitizeOutput(substr($a['description'], 0, 200))); ?>
                                <?php if (strlen($a['description']) > 200): ?>...<?php endif; ?>
                            </div>
                            <?php if (!empty($a['feedback'])): ?>
                                <div class="feedback-preview">
                                    <strong>Feedback:</strong> <?php echo sanitizeOutput(substr($a['feedback'], 0, 100)); ?>
                                    <?php if (strlen($a['feedback']) > 100): ?>...<?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="assignment-actions">
                                <?php if (empty($a['submission_id'])): ?>
                                    <a href="student_submit.php?assignment_id=<?php echo $a['assignment_id']; ?>" class="btn btn-sm btn-primary">Submit Assignment</a>
                                <?php else: ?>
                                    <a href="student_submit.php?assignment_id=<?php echo $a['assignment_id']; ?>" class="btn btn-sm btn-secondary">View Submission</a>
                                    <?php if (!empty($a['points_earned'])): ?>
                                        <div class="grade-display">
                                            <span class="grade-score"><?php echo number_format($a['points_earned'], 1); ?>/<?php echo $a['grade_max_points']; ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
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