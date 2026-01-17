<?php


require_once 'config.php';
require_once 'Course.php';
require_once 'Assignment.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 1) {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden Action - You do not have permission to access this page.');
}

$userId = getCurrentUserId();
$course = new Course();
$assignment = new Assignment();

// Get specific course if requested
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$selectedCourse = null;
$courseAssignments = [];

if ($courseId) {
    $selectedCourse = $course->getCourseById($courseId);
    if ($selectedCourse) {
        $courseAssignments = $assignment->getAssignmentsByCourse($courseId);
    }
}

$enrolledCourses = $course->getCoursesByStudent($userId);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Metropolitan College</title>
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
                <a href="student_courses.php" class="nav-link active">My Courses</a>
                <a href="student_assignments.php" class="nav-link">Assignments</a>
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
            <?php if ($selectedCourse): ?>
                <!-- Course Details View -->
                <div class="back-link">
                    <a href="student_courses.php">← Back to All Courses</a>
                </div>
                
                <div class="course-detail">
                    <div class="course-detail-header">
                        <div>
                            <h1><?php echo sanitizeOutput($selectedCourse['course_name']); ?></h1>
                            <p class="course-code"><?php echo sanitizeOutput($selectedCourse['course_code']); ?> • <?php echo sanitizeOutput($selectedCourse['semester']); ?></p>
                            <p class="course-prof">Instructor: Prof. <?php echo sanitizeOutput($selectedCourse['professor_name']); ?></p>
                        </div>
                    </div>
                    
                    <div class="course-description">
                        <h3>Course Description</h3>
                        <p><?php echo nl2br(sanitizeOutput($selectedCourse['description'])); ?></p>
                    </div>
                    
                    <div class="section">
                        <h2>Course Assignments</h2>
                        <?php if (empty($courseAssignments)): ?>
                            <div class="empty-state">
                                <p>No assignments posted yet for this course.</p>
                            </div>
                        <?php else: ?>
                            <div class="assignments-list">
                                <?php foreach ($courseAssignments as $a): 
                                    // Check if student has submitted
                                    $stmt = getDBConnection()->prepare(
                                        "SELECT s.*, g.points_earned, g.max_points as grade_max_points, g.feedback
                                         FROM submissions s
                                         LEFT JOIN grades g ON s.submission_id = g.submission_id
                                         WHERE s.assignment_id = ? AND s.student_id = ?"
                                    );
                                    $stmt->execute([$a['assignment_id'], $userId]);
                                    $submission = $stmt->fetch();
                                    
                                    $isPast = strtotime($a['due_date']) < time();
                                    $statusClass = !empty($submission['points_earned']) ? 'graded' : (!empty($submission) ? 'submitted' : ($isPast ? 'overdue' : 'pending'));
                                    $statusText = !empty($submission['points_earned']) ? 'Graded' : (!empty($submission) ? 'Submitted' : ($isPast ? 'Overdue' : 'Pending'));
                                ?>
                                <div class="assignment-card">
                                    <div class="assignment-header">
                                        <h3 class="assignment-title"><?php echo sanitizeOutput($a['title']); ?></h3>
                                        <span class="status-badge status-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </div>
                                    <div class="assignment-meta">
                                        <span>📅 Due: <?php echo date('M d, Y H:i', strtotime($a['due_date'])); ?></span>
                                        <span>🎯 <?php echo $a['max_points']; ?> points</span>
                                    </div>
                                    <div class="assignment-description">
                                        <?php echo nl2br(sanitizeOutput($a['description'])); ?>
                                    </div>
                                    <div class="assignment-actions">
                                        <?php if (empty($submission)): ?>
                                            <a href="student_submit.php?assignment_id=<?php echo $a['assignment_id']; ?>" class="btn btn-sm btn-primary">Submit Assignment</a>
                                        <?php else: ?>
                                            <a href="student_submit.php?assignment_id=<?php echo $a['assignment_id']; ?>" class="btn btn-sm btn-secondary">View Submission</a>
                                            <?php if (!empty($submission['points_earned'])): ?>
                                                <div class="grade-display">
                                                    <span class="grade-score"><?php echo number_format($submission['points_earned'], 1); ?>/<?php echo $submission['grade_max_points']; ?></span>
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
            <?php else: ?>
                <!-- All Courses View -->
                <h1>My Courses</h1>
                
                <?php if (empty($enrolledCourses)): ?>
                    <div class="empty-state">
                        <p>You are not enrolled in any courses yet.</p>
                        <a href="browse_courses.php" class="btn btn-primary">Browse Courses</a>
                    </div>
                <?php else: ?>
                    <div class="course-grid">
                        <?php foreach ($enrolledCourses as $c): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <h3><?php echo sanitizeOutput($c['course_code']); ?></h3>
                                    <span class="course-semester"><?php echo sanitizeOutput($c['semester']); ?></span>
                                </div>
                                <h4><?php echo sanitizeOutput($c['course_name']); ?></h4>
                                <p class="course-professor">Prof. <?php echo sanitizeOutput($c['professor_name']); ?></p>
                                <p style="font-size: 0.9rem; color: #666;">Enrolled: <?php echo date('M d, Y', strtotime($c['enrollment_date'])); ?></p>
                                <div class="course-actions">
                                    <a href="student_courses.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <a href="browse_courses.php" class="btn btn-secondary">Browse More Courses</a>
                </div>
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