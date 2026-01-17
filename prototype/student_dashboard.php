<?php

require_once 'config.php';
require_once 'Course.php';
require_once 'Assignment.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 1) {
    header('HTTP/1.1 403 Forbidden');
    die('<h1>403 Forbidden</h1><p>Access denied. You do not have permission to access this page.</p>');
}

$username = getCurrentUsername();
$userId = getCurrentUserId();

$course = new Course();
$assignment = new Assignment();

// Get student data
$enrolledCourses = $course->getCoursesByStudent($userId);
$assignments = $assignment->getAssignmentsForStudent($userId);
$grades = $assignment->getGradesByStudent($userId);

// Calculate statistics
$totalCourses = count($enrolledCourses);
$totalAssignments = count($assignments);
$completedAssignments = count(array_filter($assignments, function($a) { return !empty($a['submission_id']); }));
$gradedAssignments = count(array_filter($assignments, function($a) { return !empty($a['points_earned']); }));

// Calculate average grade
$totalPoints = 0;
$totalMaxPoints = 0;
foreach ($grades as $grade) {
    $totalPoints += $grade['points_earned'];
    $totalMaxPoints += $grade['max_points'];
}
$averageGrade = $totalMaxPoints > 0 ? round(($totalPoints / $totalMaxPoints) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Metropolitan College</title>
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
                <a href="student_dashboard.php" class="nav-link active">Dashboard</a>
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
            <div class="welcome-section">
                <h1>Welcome, <?php echo sanitizeOutput($username); ?>! 👋</h1>
                <p class="role-badge">Student</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <h3><?php echo $totalCourses; ?></h3>
                        <p>Enrolled Courses</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3><?php echo $completedAssignments; ?>/<?php echo $totalAssignments; ?></h3>
                        <p>Submitted Assignments</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $averageGrade; ?>%</h3>
                        <p>Average Grade</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo $gradedAssignments; ?></h3>
                        <p>Graded Assignments</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-grid">
                    <a href="student_courses.php" class="action-card">
                        <div class="action-icon">📚</div>
                        <h3>View Courses</h3>
                        <p>See your enrolled courses</p>
                    </a>
                    <a href="student_assignments.php" class="action-card">
                        <div class="action-icon">📝</div>
                        <h3>View Assignments</h3>
                        <p>Check pending assignments</p>
                    </a>
                    <a href="student_grades.php" class="action-card">
                        <div class="action-icon">📊</div>
                        <h3>View Grades</h3>
                        <p>See your performance</p>
                    </a>
                    <a href="browse_courses.php" class="action-card">
                        <div class="action-icon">🔍</div>
                        <h3>Browse Courses</h3>
                        <p>Find new courses</p>
                    </a>
                </div>
            </div>

            <!-- Recent Assignments -->
            <div class="section">
                <h2>Upcoming Assignments</h2>
                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <p>No assignments yet. Enroll in courses to see assignments.</p>
                        <a href="browse_courses.php" class="btn btn-primary">Browse Courses</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Assignment</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $upcomingAssignments = array_slice($assignments, 0, 5);
                                foreach ($upcomingAssignments as $a): 
                                    $isPast = strtotime($a['due_date']) < time();
                                    $statusClass = !empty($a['points_earned']) ? 'graded' : (!empty($a['submission_id']) ? 'submitted' : ($isPast ? 'overdue' : 'pending'));
                                    $statusText = !empty($a['points_earned']) ? 'Graded' : (!empty($a['submission_id']) ? 'Submitted' : ($isPast ? 'Overdue' : 'Pending'));
                                ?>
                                <tr>
                                    <td><strong><?php echo sanitizeOutput($a['course_code']); ?></strong></td>
                                    <td><?php echo sanitizeOutput($a['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($a['due_date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                    <td>
                                        <?php if (!empty($a['points_earned'])): ?>
                                            <?php echo number_format($a['points_earned'], 1); ?>/<?php echo $a['grade_max_points']; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="section-footer">
                        <a href="student_assignments.php" class="btn btn-secondary">View All Assignments</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Enrolled Courses -->
            <div class="section">
                <h2>My Courses</h2>
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
                                <div class="course-actions">
                                    <a href="student_courses.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-secondary">View Details</a>
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