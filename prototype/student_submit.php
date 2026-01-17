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

$assignmentId = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

if (!$assignmentId) {
    redirect('student_assignments.php');
}

// Get assignment details
$assignmentDetails = $assignment->getAssignmentById($assignmentId);
if (!$assignmentDetails) {
    redirect('student_assignments.php');
}

// Check if already submitted
$stmt = getDBConnection()->prepare(
    "SELECT s.*, g.points_earned, g.max_points as grade_max_points, g.feedback, g.graded_at
     FROM submissions s
     LEFT JOIN grades g ON s.submission_id = g.submission_id
     WHERE s.assignment_id = ? AND s.student_id = ?"
);
$stmt->execute([$assignmentId, $userId]);
$existingSubmission = $stmt->fetch();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionText = trim($_POST['submission_text'] ?? '');
    
    if (empty($submissionText)) {
        $error = 'Submission text is required';
    } else {
        $result = $assignment->submitAssignment($assignmentId, $userId, $submissionText);
        if ($result['success']) {
            $message = $result['message'];
            // Refresh submission data
            $stmt->execute([$assignmentId, $userId]);
            $existingSubmission = $stmt->fetch();
        } else {
            $error = $result['message'];
        }
    }
}

$isPastDue = strtotime($assignmentDetails['due_date']) < time();
$isGraded = !empty($existingSubmission['points_earned']);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment - Metropolitan College</title>
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
            <div class="back-link">
                <a href="student_assignments.php">← Back to Assignments</a>
            </div>

            <div class="section">
                <div class="assignment-detail-header">
                    <div>
                        <span class="course-badge"><?php echo sanitizeOutput($assignmentDetails['course_code']); ?></span>
                        <h1><?php echo sanitizeOutput($assignmentDetails['title']); ?></h1>
                        <p style="color: #666;">Course: <?php echo sanitizeOutput($assignmentDetails['course_name']); ?></p>
                    </div>
                </div>

                <div class="assignment-info-grid">
                    <div class="info-item">
                        <strong>Due Date:</strong>
                        <span><?php echo date('M d, Y H:i', strtotime($assignmentDetails['due_date'])); ?></span>
                        <?php if ($isPastDue): ?>
                            <span class="status-badge status-overdue">Past Due</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <strong>Maximum Points:</strong>
                        <span><?php echo $assignmentDetails['max_points']; ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Instructor:</strong>
                        <span>Prof. <?php echo sanitizeOutput($assignmentDetails['created_by_name']); ?></span>
                    </div>
                </div>

                <div class="assignment-description-full">
                    <h3>Assignment Description</h3>
                    <p><?php echo nl2br(sanitizeOutput($assignmentDetails['description'])); ?></p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo sanitizeOutput($message); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo sanitizeOutput($error); ?></div>
                <?php endif; ?>

                <!-- Existing Submission Display -->
                <?php if ($existingSubmission): ?>
                    <div class="submission-box">
                        <h3>Your Submission</h3>
                        <div class="submission-meta">
                            <span>📅 Submitted: <?php echo date('M d, Y H:i', strtotime($existingSubmission['submitted_at'])); ?></span>
                            <?php if ($existingSubmission['status'] == 'late'): ?>
                                <span class="status-badge status-overdue">Late Submission</span>
                            <?php endif; ?>
                        </div>
                        <div class="submission-content">
                            <?php echo nl2br(sanitizeOutput($existingSubmission['submission_text'])); ?>
                        </div>

                        <?php if ($isGraded): ?>
                            <div class="grade-section">
                                <h4>Grade</h4>
                                <div class="grade-display-large">
                                    <span class="grade-score-large"><?php echo number_format($existingSubmission['points_earned'], 1); ?></span>
                                    <span class="grade-max">/ <?php echo $existingSubmission['grade_max_points']; ?></span>
                                    <span class="grade-percentage">(<?php echo number_format(($existingSubmission['points_earned'] / $existingSubmission['grade_max_points']) * 100, 1); ?>%)</span>
                                </div>
                                <?php if ($existingSubmission['feedback']): ?>
                                    <div class="feedback-section">
                                        <h4>Instructor Feedback</h4>
                                        <p><?php echo nl2br(sanitizeOutput($existingSubmission['feedback'])); ?></p>
                                        <p style="font-size: 0.9rem; color: #666; margin-top: 1rem;">
                                            Graded on: <?php echo date('M d, Y H:i', strtotime($existingSubmission['graded_at'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Your submission is awaiting grading by the instructor.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Submission Form -->
                <?php if (!$isGraded): ?>
                    <div class="submission-form-section">
                        <h3><?php echo $existingSubmission ? 'Resubmit Assignment' : 'Submit Assignment'; ?></h3>
                        <?php if ($isPastDue): ?>
                            <div class="alert alert-error">
                                This assignment is past due. Your submission will be marked as late.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="submission-form">
                            <div class="form-group">
                                <label for="submission_text">Your Answer</label>
                                <textarea 
                                    id="submission_text" 
                                    name="submission_text" 
                                    rows="15" 
                                    required
                                    placeholder="Enter your assignment submission here..."
                                    class="submission-textarea"
                                ><?php echo $existingSubmission ? sanitizeOutput($existingSubmission['submission_text']) : ''; ?></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $existingSubmission ? 'Update Submission' : 'Submit Assignment'; ?>
                                </button>
                                <a href="student_assignments.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        This assignment has been graded and cannot be resubmitted.
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