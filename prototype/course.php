<?php

require_once 'config.php';

class Course {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public function createCourse($professorId, $courseCode, $courseName, $description, $semester) {
        try {
            // Check if course code already exists
            $stmt = $this->pdo->prepare("SELECT course_id FROM courses WHERE course_code = ?");
            $stmt->execute([$courseCode]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Course code already exists'];
            }
            
            // Insert course
            $stmt = $this->pdo->prepare(
                "INSERT INTO courses (course_code, course_name, description, professor_id, semester) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$courseCode, $courseName, $description, $professorId, $semester]);
            
            return [
                'success' => true, 
                'message' => 'Course created successfully',
                'course_id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Create course error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create course'];
        }
    }
    

    public function getCoursesByProfessor($professorId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*, COUNT(DISTINCT e.student_id) as enrolled_students
                 FROM courses c
                 LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
                 WHERE c.professor_id = ?
                 GROUP BY c.course_id
                 ORDER BY c.created_at DESC"
            );
            $stmt->execute([$professorId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get professor courses error: " . $e->getMessage());
            return [];
        }
    }
    

    public function getAllCourses() {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*, u.username as professor_name, COUNT(DISTINCT e.student_id) as enrolled_students
                 FROM courses c
                 INNER JOIN users u ON c.professor_id = u.user_id
                 LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
                 GROUP BY c.course_id
                 ORDER BY c.course_code"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all courses error: " . $e->getMessage());
            return [];
        }
    }
    

    public function getCoursesByStudent($studentId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*, u.username as professor_name, e.enrollment_date, e.status
                 FROM courses c
                 INNER JOIN enrollments e ON c.course_id = e.course_id
                 INNER JOIN users u ON c.professor_id = u.user_id
                 WHERE e.student_id = ? AND e.status = 'active'
                 ORDER BY c.course_code"
            );
            $stmt->execute([$studentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get student courses error: " . $e->getMessage());
            return [];
        }
    }
    

    public function enrollStudent($studentId, $courseId) {
        try {
            // Check if already enrolled
            $stmt = $this->pdo->prepare(
                "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND course_id = ?"
            );
            $stmt->execute([$studentId, $courseId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Already enrolled in this course'];
            }
            
            // Enroll student
            $stmt = $this->pdo->prepare(
                "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)"
            );
            $stmt->execute([$studentId, $courseId]);
            
            return ['success' => true, 'message' => 'Successfully enrolled in course'];
        } catch (PDOException $e) {
            error_log("Enroll student error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to enroll in course'];
        }
    }
    

    public function getCourseById($courseId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT c.*, u.username as professor_name
                 FROM courses c
                 INNER JOIN users u ON c.professor_id = u.user_id
                 WHERE c.course_id = ?"
            );
            $stmt->execute([$courseId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get course by ID error: " . $e->getMessage());
            return null;
        }
    }
    

    public function getEnrolledStudents($courseId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.user_id, u.username, u.email, e.enrollment_date
                 FROM users u
                 INNER JOIN enrollments e ON u.user_id = e.student_id
                 WHERE e.course_id = ? AND e.status = 'active'
                 ORDER BY u.username"
            );
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get enrolled students error: " . $e->getMessage());
            return [];
        }
    }
    

    public function updateCourse($courseId, $courseName, $description, $semester) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE courses SET course_name = ?, description = ?, semester = ? WHERE course_id = ?"
            );
            $stmt->execute([$courseName, $description, $semester, $courseId]);
            return ['success' => true, 'message' => 'Course updated successfully'];
        } catch (PDOException $e) {
            error_log("Update course error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update course'];
        }
    }
    

    public function deleteCourse($courseId, $professorId) {
        try {
            // Verify professor owns the course
            $stmt = $this->pdo->prepare("SELECT professor_id FROM courses WHERE course_id = ?");
            $stmt->execute([$courseId]);
            $course = $stmt->fetch();
            
            if (!$course || $course['professor_id'] != $professorId) {
                return ['success' => false, 'message' => 'Unauthorized action'];
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM courses WHERE course_id = ?");
            $stmt->execute([$courseId]);
            return ['success' => true, 'message' => 'Course deleted successfully'];
        } catch (PDOException $e) {
            error_log("Delete course error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete course'];
        }
    }
}
?>