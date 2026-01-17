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

$message = '';
$error = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$selectedCourse = null;

// Get course details if editing/viewing
if ($courseId) {
    $selectedCourse = $course->getCourseById($courseId);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_course'])) {
        $courseCode = trim($_POST['course_code']);
        $courseName = trim($_POST['course_name']);
        $description = trim($_POST['description']);
        $semester = trim($_POST['semester']);
        
        $result = $course->createCourse($userId, $courseCode, $courseName, $description, $semester);
        if ($result['success']) {
            $message = $result['message'];
            $action = 'list';
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['update_course'])) {
        $courseName = trim($_POST['course_name']);
        $description = trim($_POST['description']);
        $semester = trim($_POST['semester']);
        
        $result = $course->updateCourse($courseId, $courseName, $description, $semester);
        if ($result['success']) {
            $message = $result['message'];
            $selectedCourse = $course->getCourseById($courseId);
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['delete_course'])) {
        $result = $course->deleteCourse($courseId, $userId);
        if ($result['success']) {
            $message = $result['message'];
            $action = 'list';
            $courseId = 0;
        } else {
            $error = $result['message'];
        }
    }
}

$myCourses = $course->getCoursesByProfessor($userId);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Metropolitan College</title>
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
                <a href="professor_courses.php" class="nav-link active">My Courses</a>
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
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo sanitizeOutput($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>

            <?php if ($action == 'create'): ?>
                <!-- Create Course Form -->
                <div class="back-link">
                    <a href="professor_courses.php">← Back to My Courses</a>
                </div>
                
                <div class="form-section">
                    <h2>Create New Course</h2>
                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="course_code">Course Code *</label>
                                <input type="text" id="course_code" name="course_code" required maxlength="20" placeholder="e.g., CS101">
                            </div>
                            
                            <div class="form-group">
                                <label for="semester">Semester *</label>
                                <input type="text" id="semester" name="semester" required maxlength="20" placeholder="e.g., Fall 2025">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_name">Course Name *</label>
                            <input type="text" id="course_name" name="course_name" required maxlength="100" placeholder="e.g., Introduction to Programming">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Course Description</label>
                            <textarea id="description" name="description" rows="6" placeholder="Describe the course content, objectives, and requirements..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="create_course" class="btn btn-primary">Create Course</button>
                            <a href="professor_courses.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>

            <?php elseif ($courseId && $selectedCourse): ?>
                <!-- View/Edit Course -->
                <div class="back-link">
                    <a href="professor_courses.php">← Back to My Courses</a>
                </div>
                
                <div class="course-detail">
                    <div class="course-detail-header">
                        <div>
                            <h1><?php echo sanitizeOutput($selectedCourse['course_name']); ?></h1>
                            <p class="course-code"><?php echo sanitizeOutput($selectedCourse['course_code']); ?> • <?php echo sanitizeOutput($selectedCourse['semester']); ?></p>
                        </div>
                        <div class="course-actions">
                            <a href="professor_assignments.php?course_id=<?php echo $courseId; ?>" class="btn btn-primary">Manage Assignments</a>
                        </div>
                    </div>

                    <!-- Edit Course Form -->
                    <div class="form-section">
                        <h3>Edit Course Details</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label>Course Code</label>
                                <input type="text" value="<?php echo sanitizeOutput($selectedCourse['course_code']); ?>" disabled>
                                <small>Course code cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="course_name">Course Name *</label>
                                <input type="text" id="course_name" name="course_name" required maxlength="100" 
                                       value="<?php echo sanitizeOutput($selectedCourse['course_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="semester">Semester *</label>
                                <input type="text" id="semester" name="semester" required maxlength="20" 
                                       value="<?php echo sanitizeOutput($selectedCourse['semester']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Course Description</label>
                                <textarea id="description" name="description" rows="6"><?php echo sanitizeOutput($selectedCourse['description']); ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_course" class="btn btn-primary">Update Course</button>
                                <button type="button" onclick="if(confirm('Are you sure you want to delete this course? This will also delete all assignments and grades.')) document.getElementById('deleteForm').submit();" class="btn btn-secondary" style="background: #991b1b;">Delete Course</button>
                            </div>
                        </form>
                        
                        <form method="POST" id="deleteForm" style="display: none;">
                            <input type="hidden" name="delete_course" value="1">
                        </form>
                    </div>

                    <!-- Enrolled Students -->
                    <div class="section">
                        <h3>Enrolled Students</h3>
                        <?php 
                        $students = $course->getEnrolledStudents($courseId);
                        if (empty($students)): 
                        ?>
                            <div class="empty-state">
                                <p>No students enrolled yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Enrolled Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td><?php echo sanitizeOutput($s['username']); ?></td>
                                            <td><?php echo sanitizeOutput($s['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($s['enrollment_date'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- List Courses -->
                <div class="section-header">
                    <h1>My Courses</h1>
                    <a href="professor_courses.php?action=create" class="btn btn-primary">Create New Course</a>
                </div>

                <?php if (empty($myCourses)): ?>
                    <div class="empty-state">
                        <p>You haven't created any courses yet.</p>
                        <a href="professor_courses.php?action=create" class="btn btn-primary">Create Your First Course</a>
                    </div>
                <?php else: ?>
                    <div class="course-grid">
                        <?php foreach ($myCourses as $c): ?>
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
                                    <a href="professor_courses.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-primary">Manage</a>
                                    <a href="professor_assignments.php?course_id=<?php echo $c['course_id']; ?>" class="btn btn-sm btn-secondary">Assignments</a>
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