<?php
// Database configuration
include('config.php');

// Create connection
$con = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_boards':
            $result = mysqli_query($con, "SELECT * FROM boards");
            if (!$result) {
                echo json_encode(['error' => mysqli_error($con)]);
                exit;
            }
            $boards = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $boards[] = $row;
            }
            echo json_encode($boards);
            break;
            
        case 'get_semesters':
            $board_id = (int)$_GET['board_id'];
            $query = "SELECT * FROM semesters WHERE board_id = $board_id";
            $result = mysqli_query($con, $query);
            
            if (!$result) {
                echo json_encode(['error' => mysqli_error($con), 'query' => $query]);
                exit;
            }
            
            $semesters = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $semesters[] = $row;
            }
            
            // Debug output - remove in production
            error_log("Semesters for board $board_id: " . print_r($semesters, true));
            
            echo json_encode($semesters);
            break;
            
        case 'get_subjects':
            $semester_id = (int)$_GET['semester_id'];
            $result = mysqli_query($con, "SELECT * FROM subjects WHERE semester_id = $semester_id");
            $subjects = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $subjects[] = $row;
            }
            echo json_encode($subjects);
            break;
            
        case 'get_questions':
            $subject_id = (int)$_GET['subject_id'];
            $result = mysqli_query($con, 
                "SELECT q.*, GROUP_CONCAT(a.answer_id, '||', a.answer_text, '||', a.is_correct SEPARATOR ';;') AS answers 
                 FROM questions q 
                 LEFT JOIN answers a ON q.question_id = a.question_id 
                 WHERE q.subject_id = $subject_id 
                 GROUP BY q.question_id");
            
            $questions = [];
            while ($row = mysqli_fetch_assoc($result)) {
                // Parse answers
                $answers = [];
                if ($row['answers']) {
                    $answer_parts = explode(';;', $row['answers']);
                    foreach ($answer_parts as $part) {
                        list($id, $text, $correct) = explode('||', $part);
                        $answers[] = [
                            'id' => $id,
                            'text' => $text,
                            'correct' => (bool)$correct
                        ];
                    }
                }
                
                $row['answers'] = $answers;
                $questions[] = $row;
            }
            echo json_encode($questions);
            break;
            
        case 'save_answer':
            session_start();
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
                exit;
            }
            
            $user_id = (int)$_SESSION['user_id'];
            $question_id = (int)$_POST['question_id'];
            $answer_id = (int)$_POST['answer_id'];
            
            // Check if answer is correct
            $correct_check = mysqli_query($con, 
                "SELECT is_correct FROM answers WHERE answer_id = $answer_id");
            $is_correct = mysqli_fetch_assoc($correct_check)['is_correct'];
            
            // Save progress
            mysqli_query($con, 
                "INSERT INTO user_progress (user_id, question_id, selected_answer_id, is_correct) 
                 VALUES ($user_id, $question_id, $answer_id, $is_correct)
                 ON DUPLICATE KEY UPDATE 
                    selected_answer_id = $answer_id, 
                    is_correct = $is_correct");
            
            echo json_encode(['success' => true, 'is_correct' => $is_correct]);
            break;
            
        case 'check_answer':
            $question_id = (int)$_POST['question_id'];
            $answer_id = (int)$_POST['answer_id'];
            
            // Check if answer is correct
            $result = mysqli_query($con, 
                "SELECT is_correct FROM answers WHERE answer_id = $answer_id AND question_id = $question_id");
            
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                echo json_encode(['success' => true, 'is_correct' => (bool)$row['is_correct']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Answer not found']);
            }
            break;
            
       default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// Start session for user tracking
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Q&A System with Multiple Answers</title>
    <style>
        .container { display: flex; gap: 20px; margin-bottom: 20px; }
        .selection-box { flex: 1; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .selection-box h2 { margin-top: 0; }
        .item-list { list-style: none; padding: 0; }
        .item-list li { padding: 8px 0; cursor: pointer; }
        .item-list li:hover { background: #f5f5f5; }
        .item-list li.active { font-weight: bold; color: #0066cc; }
        
        .questions-container { margin-top: 20px; }
        .question-box { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .answers-container { margin-top: 10px; }
        .answer-option { margin: 5px 0; padding: 8px; border: 1px solid #eee; border-radius: 4px; cursor: pointer; }
        .answer-option:hover { background: #f9f9f9; }
        .answer-option.selected { background: #e6f7ff; border-color: #1890ff; }
        .answer-option.correct { background: #f6ffed; border-color: #52c41a; }
        .answer-option.incorrect { background: #fff2f0; border-color: #ff4d4f; }
        .feedback { margin-top: 10px; padding: 10px; border-radius: 4px; }
        .feedback.correct { background: #f6ffed; color: #52c41a; }
        .feedback.incorrect { background: #fff2f0; color: #ff4d4f; }
        .loading { color: #666; font-style: italic; }
         .error-message {
            color: #ff4d4f;
            padding: 10px;
            background: #fff2f0;
            border-radius: 4px;
            margin-top: 10px;
        }
        .check-answer-btn {
            margin-top: 10px;
            padding: 8px 15px;
            background: #1890ff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .check-answer-btn:hover {
            background: #40a9ff;
        }
    </style>
</head>
<body>
    <h1>Question & Answer System</h1>
    
    <div class="container">
        <!-- Board Selection -->
        <div class="selection-box">
            <h2>Board</h2>
            <ul class="item-list" id="board-list">
                <?php
                $boards = mysqli_query($con, "SELECT * FROM boards");
                if (!$boards) {
                    echo '<div class="error-message">Error loading boards: ' . mysqli_error($con) . '</div>';
                } else {
                    while ($board = mysqli_fetch_assoc($boards)) {
                        echo "<li data-id='{$board['board_id']}'>{$board['board_name']}</li>";
                    }
                }
                ?>
            </ul>
        </div>
        
        <!-- Semester Selection -->
        <div class="selection-box">
            <h2>Semester</h2>
            <div id="semester-container">
                <p class="loading">Select a board first</p>
                <ul class="item-list" id="semester-list" style="display:none"></ul>
            </div>
        </div>
        
        <!-- Subject Selection -->
        <div class="selection-box">
            <h2>Subject</h2>
            <div id="subject-container">
                <p class="loading">Select a semester first</p>
                <ul class="item-list" id="subject-list" style="display:none"></ul>
            </div>
        </div>
    </div>
    
    <!-- Questions Display -->
    <div id="questions-container" style="display:none">
        <h2>Questions</h2>
        <div id="questions-list"></div>
    </div>
    
    <script>
    // Current selections
    let currentBoard = null;
    let currentSemester = null;
    let currentSubject = null;
    
    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Board selection
        document.getElementById('board-list').addEventListener('click', function(e) {
            if (e.target.tagName === 'LI') {
                // Update UI
                document.querySelectorAll('#board-list li').forEach(li => li.classList.remove('active'));
                e.target.classList.add('active');
                
                // Load semesters
                currentBoard = e.target.dataset.id;
                loadSemesters(currentBoard);
                
                // Reset downstream
                resetSelection('semester');
                resetSelection('subject');
                document.getElementById('questions-container').style.display = 'none';
            }
        });
        
        // Semester selection
        document.getElementById('semester-list').addEventListener('click', function(e) {
            if (e.target.tagName === 'LI') {
                // Update UI
                document.querySelectorAll('#semester-list li').forEach(li => li.classList.remove('active'));
                e.target.classList.add('active');
                
                // Load subjects
                currentSemester = e.target.dataset.id;
                loadSubjects(currentSemester);
                
                // Reset downstream
                resetSelection('subject');
                document.getElementById('questions-container').style.display = 'none';
            }
        });
        
        // Subject selection
        document.getElementById('subject-list').addEventListener('click', function(e) {
            if (e.target.tagName === 'LI') {
                // Update UI
                document.querySelectorAll('#subject-list li').forEach(li => li.classList.remove('active'));
                e.target.classList.add('active');
                
                // Load questions
                currentSubject = e.target.dataset.id;
                loadQuestions(currentSubject);
            }
        });
    });
    
    // Load semesters
    function loadSemesters(boardId) {
        const container = document.getElementById('semester-container');
        const list = document.getElementById('semester-list');
        
        container.querySelector('.loading').style.display = 'block';
        list.style.display = 'none';
        list.innerHTML = '';
        
        fetch(`question.php?action=get_semesters&board_id=${boardId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(semesters => {
                // Debug: Check what we're receiving
                console.log('Received semesters:', semesters);
                
                if (semesters && semesters.length > 0) {
                    list.innerHTML = ''; // Clear previous content
                    
                    semesters.forEach(semester => {
                        const li = document.createElement('li');
                        li.textContent = semester.semester_name;
                        li.dataset.id = semester.semester_id;
                        list.appendChild(li);
                    });
                    
                    container.querySelector('.loading').style.display = 'none';
                    list.style.display = 'block';
                } else {
                    container.querySelector('.loading').textContent = 'No semesters found';
                    console.warn('No semesters returned for board:', boardId);
                }
            })
            .catch(error => {
                container.querySelector('.loading').textContent = 'Error loading semesters';
                console.error('Fetch error:', error);
            });
    }
    
    // Load subjects
    function loadSubjects(semesterId) {
        const container = document.getElementById('subject-container');
        const list = document.getElementById('subject-list');
        
        container.querySelector('.loading').style.display = 'block';
        list.style.display = 'none';
        list.innerHTML = '';
        
        fetch(`question.php?action=get_subjects&semester_id=${semesterId}`)
            .then(response => response.json())
            .then(subjects => {
                if (subjects.length > 0) {
                    subjects.forEach(subject => {
                        const li = document.createElement('li');
                        li.textContent = subject.subject_name;
                        li.dataset.id = subject.subject_id;
                        list.appendChild(li);
                    });
                    
                    container.querySelector('.loading').style.display = 'none';
                    list.style.display = 'block';
                } else {
                    container.querySelector('.loading').textContent = 'No subjects found';
                }
            });
    }
    
    // Load questions with answers
    function loadQuestions(subjectId) {
        const container = document.getElementById('questions-container');
        const list = document.getElementById('questions-list');
        
        container.style.display = 'block';
        list.innerHTML = '<p>Loading questions...</p>';
        
        fetch(`question.php?action=get_questions&subject_id=${subjectId}`)
            .then(response => response.json())
            .then(questions => {
                if (questions.length > 0) {
                    list.innerHTML = '';
                    
                    questions.forEach(question => {
                        const questionDiv = document.createElement('div');
                        questionDiv.className = 'question-box';
                        questionDiv.dataset.id = question.question_id;
                        
                        // Create answer options
                        let answersHtml = '';
                        if (question.answers && question.answers.length > 0) {
                            answersHtml = '<div class="answers-container">';
                            question.answers.forEach(answer => {
                                answersHtml += `
                                    <div class="answer-option" data-id="${answer.id}" data-correct="${answer.correct}">
                                        ${answer.text}
                                    </div>
                                `;
                            });
                            answersHtml += '</div>';
                        }
                        
                        questionDiv.innerHTML = `
                            <h3>${question.question_text}</h3>
                            ${answersHtml}
                           
                            <div class="feedback" style="display:none"></div>
                        `;
                        
                        list.appendChild(questionDiv);
                    });
                    
                    // Add event listeners for answer selection
   // Replace the existing answer-option click event listener with this corrected version
document.querySelectorAll('.answer-option').forEach(option => {
    option.addEventListener('click', function () {
        const questionDiv = this.closest('.question-box');
        const allOptions = questionDiv.querySelectorAll('.answer-option');
        const feedback = questionDiv.querySelector('.feedback');

        // Clear previous states
        allOptions.forEach(opt => {
            opt.classList.remove('selected', 'correct', 'incorrect');
        });

        // Mark this option as selected
        this.classList.add('selected');

        // Check answer correctness - fixed comparison
        const isCorrect = this.dataset.correct === "true" || this.dataset.correct === "1";
        
        // Highlight all options appropriately
        allOptions.forEach(opt => {
            if (opt.dataset.correct === "true" || opt.dataset.correct === "1") {
                opt.classList.add('correct');
            }
        });

        if (isCorrect) {
            feedback.textContent = "Correct!";
            feedback.className = "feedback correct";
        } else {
            this.classList.add('incorrect');
            feedback.textContent = "Incorrect! The correct answer is highlighted in green.";
            feedback.className = "feedback incorrect";
        }

        feedback.style.display = 'block';
    });
});

                    
                    // Add event listeners for check answer buttons
                    document.querySelectorAll('.check-answer-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const questionDiv = this.closest('.question-box');
                            const questionId = questionDiv.dataset.id;
                            const selectedAnswer = questionDiv.querySelector('.answer-option.selected');
                            
                            if (!selectedAnswer) {
                                alert('Please select an answer first');
                                return;
                            }
                            
                            const answerId = selectedAnswer.dataset.id;
                            checkAnswer(questionId, answerId, questionDiv);
                        });
                    });
                } else {
                    list.innerHTML = '<p>No questions found for this subject.</p>';
                }
            });
    }
    
    // Check if answer is correct
    function checkAnswer(questionId, answerId, questionDiv) {
        fetch('question.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=check_answer&question_id=${questionId}&answer_id=${answerId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const feedback = questionDiv.querySelector('.feedback');
                feedback.style.display = 'block';
                
                if (data.is_correct) {
                    feedback.textContent = 'Correct!';
                    feedback.className = 'feedback correct';
                } else {
                    feedback.textContent = 'Incorrect!';
                    feedback.className = 'feedback incorrect';
                }
                
                // Highlight correct answer
                questionDiv.querySelectorAll('.answer-option').forEach(opt => {
                    if (opt.dataset.correct === 'true') {
                        opt.classList.add('correct');
                    } else if (opt.classList.contains('selected')) {
                        opt.classList.add('incorrect');
                    }
                });
            } else {
                alert('Error checking answer: ' + (data.error || 'Unknown error'));
            }
        });
    }
    
    // Reset selection
    function resetSelection(type) {
        const container = document.getElementById(`${type}-container`);
        const list = document.getElementById(`${type}-list`);
        
        container.querySelector('.loading').style.display = 'block';
        container.querySelector('.loading').textContent = type === 'semester' 
            ? 'Select a board first' 
            : 'Select a semester first';
        list.style.display = 'none';
        list.innerHTML = '';
    }
    </script>
</body>
</html>