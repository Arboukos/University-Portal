<?php

require_once 'config.php';
require_once 'Assignment.php';

// Check authentication and role
if (!isLoggedIn() || getCurrentRoleId() != 2) {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden Action - You do not have permission to access this page.');
}

$userId = getCurrentUserId();
$assignment = new Assignment();

$submissionId = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;

if (!$submissionId) {
    redirect('professor_assignments.php');
}

// Get submission details
$stmt = getDBConnection()->prepare(
    "SELECT s.*, a.title as assignment_title, a.max_points, a.assignment_id, a.course_id,
     c.course_name, c.course_code, u.username, u.email,
     g.points_earned, g.feedback, g.graded_at
     FROM submissions s
     INNER JOIN assignments a ON s.assignment_id = a.assignment_id
     INNER JOIN courses c ON a.course_id = c.course_id
     INNER JOIN users u ON s.student_id = u.user_id
     LEFT JOIN grades g ON s.submission_id = g.submission_id
     WHERE s.submission_id = ?"
);
$stmt->execute([$submissionId]);
$submission = $stmt->fetch();

if (!$submission) {
    redirect('professor_assignments.php');
}

$message = '';
$error = '';

// Handle grading form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $pointsEarned = floatval($_POST['points_earned']);
    $feedback = trim($_POST['feedback']);
    
    if ($pointsEarned < 0 || $pointsEarned > $submission['max_points']) {
        $error = 'Points must be between 0 and ' . $submission['max_points'];
    } else {
        $result = $assignment->gradeSubmission(
            $submissionId,
            $submission['student_id'],
            $submission['course_id'],
            $submission['assignment_id'],
            $pointsEarned,
            $submission['max_points'],
            $feedback,
            $userId
        );
        
        if ($result['success']) {
            $message = $result['message'];
            // Refresh submission data
            $stmt->execute([$submissionId]);
            $submission = $stmt->fetch();
        } else {
            $error = $result['message'];
        }
    }
}

$isLate = strtotime($submission['submitted_at']) > strtotime('-1 hour', strtotime($submission['submitted_at']));
$percentage = !empty($submission['points_earned']) ? round(($submission['points_earned'] / $submission['max_points']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submission - Metropolitan College</title>
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
            <div class="back-link">
                <a href="professor_assignments.php?assignment_id=<?php echo $submission['assignment_id']; ?>">← Back to Submissions</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo sanitizeOutput($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo sanitizeOutput($error); ?></div>
            <?php endif; ?>

            <div class="section">
                <!-- Assignment & Student Info -->
                <div class="grading-header">
                    <div class="grading-info">
                        <span class="course-badge"><?php echo sanitizeOutput($submission['course_code']); ?></span>
                        <h1><?php echo sanitizeOutput($submission['assignment_title']); ?></h1>
                        <p style="color: #666; margin-top: 0.5rem;">Student: <strong><?php echo sanitizeOutput($submission['username']); ?></strong> (<?php echo sanitizeOutput($submission['email']); ?>)</p>
                    </div>
                    <?php if (!empty($submission['points_earned'])): ?>
                        <div class="current-grade-display">
                            <div>Current Grade</div>
                            <div class="grade-display-large">
                                <span class="grade-score-large"><?php echo number_format($submission['points_earned'], 1); ?></span>
                                <span class="grade-max">/ <?php echo $submission['max_points']; ?></span>
                                <span class="grade-percentage">(<?php echo $percentage; ?>%)</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="submission-meta-bar">
                    <span>📅 Submitted: <?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></span>
                    <?php if ($submission['status'] == 'late'): ?>
                        <span class="status-badge status-overdue">Late Submission</span>
                    <?php endif; ?>
                    <?php if (!empty($submission['graded_at'])): ?>
                        <span>✅ Graded: <?php echo date('M d, Y H:i', strtotime($submission['graded_at'])); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Student Submission -->
                <div class="submission-display">
                    <h2>Student Submission</h2>
                    <div class="submission-content-large">
                        <?php echo nl2br(sanitizeOutput($submission['submission_text'])); ?>
                    </div>
                </div>

                <!-- Grading Form -->
                <div class="grading-form-section">
                    <h2><?php echo !empty($submission['points_earned']) ? 'Update Grade' : 'Grade Submission'; ?></h2>
                    <form method="POST" class="grading-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="points_earned">Points Earned *</label>
                                <input 
                                    type="number" 
                                    id="points_earned" 
                                    name="points_earned" 
                                    required 
                                    min="0" 
                                    max="<?php echo $submission['max_points']; ?>" 
                                    step="0.1"
                                    value="<?php echo !empty($submission['points_earned']) ? $submission['points_earned'] : ''; ?>"
                                    placeholder="Enter points (0-<?php echo $submission['max_points']; ?>)"
                                >
                                <small>Maximum Points: <?php echo $submission['max_points']; ?></small>
                            </div>

                            <div class="form-group">
                                <label>Calculated Percentage</label>
                                <div class="percentage-display" id="percentageDisplay">
                                    <?php echo !empty($submission['points_earned']) ? $percentage . '%' : '-'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="feedback">Feedback</label>
                            <textarea 
                                id="feedback" 
                                name="feedback" 
                                rows="8" 
                                placeholder="Provide feedback to the student about their submission..."
                            ><?php echo !empty($submission['feedback']) ? sanitizeOutput($submission['feedback']) : ''; ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="grade_submission" class="btn btn-primary">
                                <?php echo !empty($submission['points_earned']) ? 'Update Grade' : 'Submit Grade'; ?>
                            </button>
                            <a href="professor_assignments.php?assignment_id=<?php echo $submission['assignment_id']; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
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
    <script>
        // Calculate percentage in real-time
        const pointsInput = document.getElementById('points_earned');
        const percentageDisplay = document.getElementById('percentageDisplay');
        const maxPoints = <?php echo $submission['max_points']; ?>;

        pointsInput.addEventListener('input', function() {
            const points = parseFloat(this.value) || 0;
            const percentage = maxPoints > 0 ? ((points / maxPoints) * 100).toFixed(1) : 0;
            percentageDisplay.textContent = percentage + '%';
        });
    </script>
</body>
</html>
