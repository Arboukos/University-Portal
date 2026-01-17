<?php


require_once 'config.php';
require_once 'Assignment.php';
require_once 'Course.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 1) {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden Action - You do not have permission to access this page.');
}

$userId = getCurrentUserId();
$assignment = new Assignment();
$course = new Course();

// Get all grades
$grades = $assignment->getGradesByStudent($userId);
$courses = $course->getCoursesByStudent($userId);

// Calculate overall statistics
$totalPoints = 0;
$totalMaxPoints = 0;
$courseStats = [];

foreach ($grades as $grade) {
    $totalPoints += $grade['points_earned'];
    $totalMaxPoints += $grade['max_points'];
    
    $courseId = $grade['course_id'];
    if (!isset($courseStats[$courseId])) {
        $courseStats[$courseId] = [
            'course_name' => $grade['course_name'],
            'course_code' => $grade['course_code'],
            'points' => 0,
            'max_points' => 0,
            'count' => 0
        ];
    }
    $courseStats[$courseId]['points'] += $grade['points_earned'];
    $courseStats[$courseId]['max_points'] += $grade['max_points'];
    $courseStats[$courseId]['count']++;
}

$overallAverage = $totalMaxPoints > 0 ? round(($totalPoints / $totalMaxPoints) * 100, 1) : 0;

// Calculate per-course averages
foreach ($courseStats as $cid => &$stats) {
    $stats['average'] = $stats['max_points'] > 0 ? round(($stats['points'] / $stats['max_points']) * 100, 1) : 0;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - Metropolitan College</title>
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
                <a href="student_grades.php" class="nav-link active">Grades</a>
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
            <h1>My Grades</h1>

            <!-- Overall Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?php echo $overallAverage; ?>%</h3>
                        <p>Overall Average</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo count($grades); ?></h3>
                        <p>Graded Assignments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <h3><?php echo count($courseStats); ?></h3>
                        <p>Courses with Grades</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($totalPoints, 1); ?></h3>
                        <p>Total Points Earned</p>
                    </div>
                </div>
            </div>

            <!-- Course Performance -->
            <?php if (!empty($courseStats)): ?>
            <div class="section">
                <h2>Performance by Course</h2>
                <div class="course-grades-grid">
                    <?php foreach ($courseStats as $stats): ?>
                        <div class="course-grade-card">
                            <h3><?php echo sanitizeOutput($stats['course_code']); ?></h3>
                            <p><?php echo sanitizeOutput($stats['course_name']); ?></p>
                            <div class="grade-circle">
                                <span class="grade-percentage-large"><?php echo $stats['average']; ?>%</span>
                            </div>
                            <div class="grade-details">
                                <p><?php echo $stats['count']; ?> assignment<?php echo $stats['count'] != 1 ? 's' : ''; ?> graded</p>
                                <p><?php echo number_format($stats['points'], 1); ?> / <?php echo $stats['max_points']; ?> points</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- All Grades Table -->
            <div class="section">
                <h2>All Grades</h2>
                <?php if (empty($grades)): ?>
                    <div class="empty-state">
                        <p>No graded assignments yet.</p>
                        <a href="student_assignments.php" class="btn btn-secondary">View Pending Assignments</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Assignment</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Graded Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $g): 
                                    $percentage = round($g['percentage'], 1);
                                    $gradeClass = $percentage >= 90 ? 'excellent' : ($percentage >= 70 ? 'good' : ($percentage >= 50 ? 'average' : 'poor'));
                                ?>
                                <tr>
                                    <td><strong><?php echo sanitizeOutput($g['course_code']); ?></strong></td>
                                    <td><?php echo sanitizeOutput($g['assignment_title']); ?></td>
                                    <td><?php echo number_format($g['points_earned'], 1); ?> / <?php echo $g['max_points']; ?></td>
                                    <td><span class="grade-badge grade-<?php echo $gradeClass; ?>"><?php echo $percentage; ?>%</span></td>
                                    <td><?php echo date('M d, Y', strtotime($g['graded_at'])); ?></td>
                                    <td>
                                        <a href="student_submit.php?assignment_id=<?php echo $g['assignment_id']; ?>" class="btn btn-sm btn-secondary">View Details</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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