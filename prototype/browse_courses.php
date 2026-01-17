<?php

require_once 'config.php';
require_once 'course.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 1) {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden Action - You do not have permission to access this page.');
}

$userId = getCurrentUserId();
$course = new Course();

$message = '';
$error = '';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $courseId = intval($_POST['course_id']);
    $result = $course->enrollStudent($userId, $courseId);
    
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Get all courses
$allCourses = $course->getAllCourses();
$enrolledCourses = $course->getCoursesByStudent($userId);

// Create array of enrolled course IDs for easy checking
$enrolledIds = array_column($enrolledCourses, 'course_id');
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - Metropolitan College</title>
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
            <h1>Browse Courses</h1>
            <p style="color: #666; margin-bottom: 2rem;">Explore available courses and enroll to start learning</p>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo sanitizeOutput($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>

            <?php if (empty($allCourses)): ?>
                <div class="empty-state">
                    <p>No courses available at the moment.</p>
                </div>
            <?php else: ?>
                <div class="course-grid">
                    <?php foreach ($allCourses as $c): 
                        $isEnrolled = in_array($c['course_id'], $enrolledIds);
                    ?>
                        <div class="course-card <?php echo $isEnrolled ? 'enrolled' : ''; ?>">
                            <div class="course-header">
                                <h3><?php echo sanitizeOutput($c['course_code']); ?></h3>
                                <span class="course-semester"><?php echo sanitizeOutput($c['semester']); ?></span>
                            </div>
                            <h4><?php echo sanitizeOutput($c['course_name']); ?></h4>
                            <p class="course-professor">Prof. <?php echo sanitizeOutput($c['professor_name']); ?></p>
                            <div class="course-description-preview">
                                <?php 
                                $desc = $c['description'] ?? 'No description available';
                                echo sanitizeOutput(substr($desc, 0, 150)); 
                                if (strlen($desc) > 150) echo '...';
                                ?>
                            </div>
                            <div class="course-stats">
                                <span>👥 <?php echo $c['enrolled_students']; ?> students enrolled</span>
                            </div>
                            <div class="course-actions">
                                <?php if ($isEnrolled): ?>
                                    <span class="status-badge status-graded">Enrolled</span>
                                    <a href="student_courses.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-secondary">View Course</a>
                                <?php else: ?>
                                    <form method="POST" style="width: 100%;">
                                        <input type="hidden" name="course_id" value="<?php echo $c['course_id']; ?>">
                                        <button type="submit" name="enroll" class="btn btn-sm btn-primary" style="width: 100%;">Enroll Now</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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