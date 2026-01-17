<?php

require_once 'config.php';
require_once 'Course.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 2) {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden Action - You do not have permission to access this page.');
}

$userId = getCurrentUserId();
$course = new Course();

// Get professor's courses
$myCourses = $course->getCoursesByProfessor($userId);
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

// Get students based on course filter
if ($courseId) {
    $students = $course->getEnrolledStudents($courseId);
    $selectedCourse = $course->getCourseById($courseId);
    
    // Get performance data for each student
    foreach ($students as &$student) {
        $stmt = getDBConnection()->prepare(
            "SELECT COUNT(*) as total_assignments,
                    COUNT(s.submission_id) as submitted_assignments,
                    COUNT(g.grade_id) as graded_assignments,
                    AVG(g.percentage) as average_grade
             FROM assignments a
             LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
             LEFT JOIN grades g ON s.submission_id = g.submission_id
             WHERE a.course_id = ?"
        );
        $stmt->execute([$student['user_id'], $courseId]);
        $stats = $stmt->fetch();
        $student['stats'] = $stats;
    }
} else {
    // Get all students across all courses
    $allStudents = [];
    foreach ($myCourses as $c) {
        $courseStudents = $course->getEnrolledStudents($c['course_id']);
        foreach ($courseStudents as $s) {
            $s['course_name'] = $c['course_name'];
            $s['course_code'] = $c['course_code'];
            $allStudents[] = $s;
        }
    }
    $students = $allStudents;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Metropolitan College</title>
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
                <a href="professor_assignments.php" class="nav-link">Assignments</a>
                <a href="professor_students.php" class="nav-link active">Students</a>
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
            <div class="section-header">
                <h1>Students <?php echo $courseId ? '- ' . sanitizeOutput($selectedCourse['course_code']) : ''; ?></h1>
            </div>

            <!-- Course Filter -->
            <div class="filter-section">
                <label>Filter by Course:</label>
                <select onchange="if(this.value) window.location.href='professor_students.php?course_id='+this.value; else window.location.href='professor_students.php';" class="form-control" style="max-width: 400px;">
                    <option value="">All Courses</option>
                    <?php foreach ($myCourses as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>" <?php echo ($courseId == $c['course_id']) ? 'selected' : ''; ?>>
                            <?php echo sanitizeOutput($c['course_code'] . ' - ' . $c['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Students List -->
            <div class="section">
                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <p>No students enrolled <?php echo $courseId ? 'in this course' : 'in any of your courses'; ?> yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <?php if (!$courseId): ?>
                                        <th>Course</th>
                                    <?php endif; ?>
                                    <th>Enrolled Date</th>
                                    <?php if ($courseId): ?>
                                        <th>Assignments</th>
                                        <th>Avg Grade</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><strong><?php echo sanitizeOutput($s['username']); ?></strong></td>
                                    <td><?php echo sanitizeOutput($s['email']); ?></td>
                                    <?php if (!$courseId): ?>
                                        <td><?php echo sanitizeOutput($s['course_code']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo date('M d, Y', strtotime($s['enrollment_date'])); ?></td>
                                    <?php if ($courseId): ?>
                                        <td>
                                            <?php echo $s['stats']['submitted_assignments']; ?>/<?php echo $s['stats']['total_assignments']; ?> submitted
                                            <br>
                                            <small style="color: #666;"><?php echo $s['stats']['graded_assignments']; ?> graded</small>
                                        </td>
                                        <td>
                                            <?php if ($s['stats']['average_grade']): ?>
                                                <span class="grade-badge grade-<?php 
                                                    $avg = round($s['stats']['average_grade']);
                                                    echo $avg >= 90 ? 'excellent' : ($avg >= 70 ? 'good' : ($avg >= 50 ? 'average' : 'poor')); 
                                                ?>">
                                                    <?php echo number_format($s['stats']['average_grade'], 1); ?>%
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #666;">Not graded yet</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($courseId): ?>
                        <div style="margin-top: 2rem; padding: 1rem; background: #fff5f5; border-radius: 8px;">
                            <h3>Course Statistics</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                                <div>
                                    <strong>Total Students:</strong>
                                    <div style="font-size: 1.5rem; color: #dc2626;"><?php echo count($students); ?></div>
                                </div>
                                <div>
                                    <strong>Avg Submission Rate:</strong>
                                    <div style="font-size: 1.5rem; color: #dc2626;">
                                        <?php 
                                        $totalAssignments = 0;
                                        $totalSubmissions = 0;
                                        foreach ($students as $s) {
                                            $totalAssignments += $s['stats']['total_assignments'];
                                            $totalSubmissions += $s['stats']['submitted_assignments'];
                                        }
                                        $submissionRate = $totalAssignments > 0 ? round(($totalSubmissions / $totalAssignments) * 100, 1) : 0;
                                        echo $submissionRate;
                                        ?>%
                                    </div>
                                </div>
                                <div>
                                    <strong>Class Average:</strong>
                                    <div style="font-size: 1.5rem; color: #dc2626;">
                                        <?php 
                                        $totalGrade = 0;
                                        $gradedCount = 0;
                                        foreach ($students as $s) {
                                            if ($s['stats']['average_grade']) {
                                                $totalGrade += $s['stats']['average_grade'];
                                                $gradedCount++;
                                            }
                                        }
                                        $classAverage = $gradedCount > 0 ? round($totalGrade / $gradedCount, 1) : 0;
                                        echo $classAverage;
                                        ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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