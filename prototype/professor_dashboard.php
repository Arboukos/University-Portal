<?php

require_once 'config.php';
require_once 'Course.php';
require_once 'Assignment.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 2) {
    header('HTTP/1.1 403 Forbidden');
    die('<h1>403 Forbidden</h1><p>Access denied. You do not have permission to access this page.</p>');
}

$username = getCurrentUsername();
$userId = getCurrentUserId();

$course = new Course();
$assignment = new Assignment();

// Get professor data
$courses = $course->getCoursesByProfessor($userId);

// Calculate statistics
$totalCourses = count($courses);
$totalStudents = array_sum(array_column($courses, 'enrolled_students'));
$totalAssignments = 0;
$pendingGrades = 0;

foreach ($courses as $c) {
    $courseAssignments = $assignment->getAssignmentsByCourse($c['course_id']);
    $totalAssignments += count($courseAssignments);
    
    foreach ($courseAssignments as $a) {
        $submissions = $assignment->getSubmissionsByAssignment($a['assignment_id']);
        foreach ($submissions as $s) {
            if (empty($s['points_earned'])) {
                $pendingGrades++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard - Metropolitan College</title>
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
                <a href="professor_dashboard.php" class="nav-link active">Dashboard</a>
                <a href="professor_courses.php" class="nav-link">My Courses</a>
                <a href="professor_assignments.php" class="nav-link">Assignments</a>
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
            <div class="welcome-section">
                <h1>Welcome, Prof. <?php echo sanitizeOutput($username); ?>! 👨‍🏫</h1>
                <p class="role-badge">Professor</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📖</div>
                    <div class="stat-content">
                        <h3><?php echo $totalCourses; ?></h3>
                        <p>My Courses</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?php echo $totalStudents; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3><?php echo $totalAssignments; ?></h3>
                        <p>Active Assignments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3><?php echo $pendingGrades; ?></h3>
                        <p>Pending Grades</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-grid">
                    <a href="professor_courses.php?action=create" class="action-card action-primary">
                        <div class="action-icon">➕</div>
                        <h3>Create Course</h3>
                        <p>Add a new course</p>
                    </a>
                    <a href="professor_courses.php" class="action-card">
                        <div class="action-icon">📖</div>
                        <h3>Manage Courses</h3>
                        <p>Edit or view courses</p>
                    </a>
                    <a href="professor_assignments.php?action=create" class="action-card action-primary">
                        <div class="action-icon">📄</div>
                        <h3>Create Assignment</h3>
                        <p>Post new assignment</p>
                    </a>
                    <a href="professor_assignments.php" class="action-card">
                        <div class="action-icon">✍️</div>
                        <h3>Grade Submissions</h3>
                        <p>Review student work</p>
                    </a>
                </div>
            </div>

            <!-- My Courses -->
            <div class="section">
                <div class="section-header">
                    <h2>My Courses</h2>
                    <a href="professor_courses.php?action=create" class="btn btn-primary">Create New Course</a>
                </div>
                
                <?php if (empty($courses)): ?>
                    <div class="empty-state">
                        <p>You haven't created any courses yet.</p>
                        <a href="professor_courses.php?action=create" class="btn btn-primary">Create Your First Course</a>
                    </div>
                <?php else: ?>
                    <div class="course-grid">
                        <?php foreach ($courses as $c): ?>
                            <div class="course-card">
                                <div class="course-header">
                                    <h3><?php echo sanitizeOutput($c['course_code']); ?></h3>
                                    <span class="course-semester"><?php echo sanitizeOutput($c['semester']); ?></span>
                                </div>
                                <h4><?php echo sanitizeOutput($c['course_name']); ?></h4>
                                <div class="course-stats">
                                    <span>👥 <?php echo $c['enrolled_students']; ?> students</span>
                                </div>
                                <div class="course-actions">
                                    <a href="professor_courses.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-secondary">Manage</a>
                                    <a href="professor_assignments.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-secondary">Assignments</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <?php if ($pendingGrades > 0): ?>
            <div class="section">
                <h2>Pending Grades</h2>
                <div class="alert alert-info">
                    You have <?php echo $pendingGrades; ?> assignment<?php echo $pendingGrades != 1 ? 's' : ''; ?> waiting to be graded.
                    <a href="professor_assignments.php" style="color: inherit; text-decoration: underline;">Grade now</a>
                </div>
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