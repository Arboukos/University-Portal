<?php

require_once 'config.php';

class Assignment {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public function createAssignment($courseId, $title, $description, $dueDate, $maxPoints, $createdBy) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO assignments (course_id, title, description, due_date, max_points, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$courseId, $title, $description, $dueDate, $maxPoints, $createdBy]);
            
            return [
                'success' => true, 
                'message' => 'Assignment created successfully',
                'assignment_id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Create assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create assignment'];
        }
    }
    
    public function getAssignmentsByCourse($courseId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT a.*, u.username as created_by_name,
                 COUNT(DISTINCT s.submission_id) as total_submissions
                 FROM assignments a
                 INNER JOIN users u ON a.created_by = u.user_id
                 LEFT JOIN submissions s ON a.assignment_id = s.assignment_id
                 WHERE a.course_id = ?
                 GROUP BY a.assignment_id
                 ORDER BY a.due_date DESC"
            );
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get assignments error: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAssignmentsForStudent($studentId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT a.*, c.course_name, c.course_code, s.submission_id, s.submitted_at, s.status,
                 g.points_earned, g.max_points as grade_max_points, g.feedback
                 FROM assignments a
                 INNER JOIN courses c ON a.course_id = c.course_id
                 INNER JOIN enrollments e ON c.course_id = e.course_id
                 LEFT JOIN submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
                 LEFT JOIN grades g ON s.submission_id = g.submission_id
                 WHERE e.student_id = ? AND e.status = 'active'
                 ORDER BY a.due_date DESC"
            );
            $stmt->execute([$studentId, $studentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get student assignments error: " . $e->getMessage());
            return [];
        }
    }
    

    public function getAssignmentById($assignmentId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT a.*, c.course_name, c.course_code, u.username as created_by_name
                 FROM assignments a
                 INNER JOIN courses c ON a.course_id = c.course_id
                 INNER JOIN users u ON a.created_by = u.user_id
                 WHERE a.assignment_id = ?"
            );
            $stmt->execute([$assignmentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get assignment by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    public function submitAssignment($assignmentId, $studentId, $submissionText, $fileName = null) {
        try {
            // Check if already submitted
            $stmt = $this->pdo->prepare(
                "SELECT submission_id FROM submissions WHERE assignment_id = ? AND student_id = ?"
            );
            $stmt->execute([$assignmentId, $studentId]);
            $existing = $stmt->fetch();
            
            // Get assignment due date
            $stmt = $this->pdo->prepare("SELECT due_date FROM assignments WHERE assignment_id = ?");
            $stmt->execute([$assignmentId]);
            $assignment = $stmt->fetch();
            $isLate = (strtotime($assignment['due_date']) < time()) ? 'late' : 'submitted';
            
            if ($existing) {
                // Update existing submission
                $stmt = $this->pdo->prepare(
                    "UPDATE submissions SET submission_text = ?, file_name = ?, submitted_at = NOW(), status = ?
                     WHERE submission_id = ?"
                );
                $stmt->execute([$submissionText, $fileName, $isLate, $existing['submission_id']]);
                $message = 'Assignment resubmitted successfully';
            } else {
                // Create new submission
                $stmt = $this->pdo->prepare(
                    "INSERT INTO submissions (assignment_id, student_id, submission_text, file_name, status) 
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$assignmentId, $studentId, $submissionText, $fileName, $isLate]);
                $message = 'Assignment submitted successfully';
            }
            
            return ['success' => true, 'message' => $message];
        } catch (PDOException $e) {
            error_log("Submit assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit assignment'];
        }
    }
    
    public function getSubmissionsByAssignment($assignmentId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT s.*, u.username, u.email, g.points_earned, g.feedback, g.graded_at
                 FROM submissions s
                 INNER JOIN users u ON s.student_id = u.user_id
                 LEFT JOIN grades g ON s.submission_id = g.submission_id
                 WHERE s.assignment_id = ?
                 ORDER BY s.submitted_at DESC"
            );
            $stmt->execute([$assignmentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get submissions error: " . $e->getMessage());
            return [];
        }
    }
    

    public function gradeSubmission($submissionId, $studentId, $courseId, $assignmentId, $pointsEarned, $maxPoints, $feedback, $gradedBy) {
        try {
            $percentage = ($maxPoints > 0) ? ($pointsEarned / $maxPoints) * 100 : 0;
            
            // Check if already graded
            $stmt = $this->pdo->prepare("SELECT grade_id FROM grades WHERE submission_id = ?");
            $stmt->execute([$submissionId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing grade
                $stmt = $this->pdo->prepare(
                    "UPDATE grades SET points_earned = ?, max_points = ?, percentage = ?, 
                     feedback = ?, graded_by = ?, graded_at = NOW()
                     WHERE grade_id = ?"
                );
                $stmt->execute([$pointsEarned, $maxPoints, $percentage, $feedback, $gradedBy, $existing['grade_id']]);
            } else {
                // Insert new grade
                $stmt = $this->pdo->prepare(
                    "INSERT INTO grades (submission_id, student_id, course_id, assignment_id, 
                     points_earned, max_points, percentage, feedback, graded_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$submissionId, $studentId, $courseId, $assignmentId, $pointsEarned, $maxPoints, $percentage, $feedback, $gradedBy]);
            }
            
            // Update submission status
            $stmt = $this->pdo->prepare("UPDATE submissions SET status = 'graded' WHERE submission_id = ?");
            $stmt->execute([$submissionId]);
            
            return ['success' => true, 'message' => 'Assignment graded successfully'];
        } catch (PDOException $e) {
            error_log("Grade submission error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to grade assignment'];
        }
    }
    
    public function getGradesByStudent($studentId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT g.*, c.course_name, c.course_code, a.title as assignment_title, u.username as graded_by_name
                 FROM grades g
                 INNER JOIN courses c ON g.course_id = c.course_id
                 INNER JOIN assignments a ON g.assignment_id = a.assignment_id
                 INNER JOIN users u ON g.graded_by = u.user_id
                 WHERE g.student_id = ?
                 ORDER BY g.graded_at DESC"
            );
            $stmt->execute([$studentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get student grades error: " . $e->getMessage());
            return [];
        }
    }
    

    public function deleteAssignment($assignmentId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM assignments WHERE assignment_id = ?");
            $stmt->execute([$assignmentId]);
            return ['success' => true, 'message' => 'Assignment deleted successfully'];
        } catch (PDOException $e) {
            error_log("Delete assignment error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete assignment'];
        }
    }
}
?>