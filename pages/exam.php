<?php

session_start();
include('config.php');

if (!isset($_SESSION['username'])) {
    header('Location: ./auth/signin.php');
    exit();
}

$use = $_SESSION['username'];

$sql = "SELECT * FROM students WHERE name = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $use);
$stmt->execute();
$res = $stmt->get_result();
$row2 = $res->fetch_assoc();

$name = $row2['name'] ?? 'Guest';
$sid = $row2['registration_code'] ?? '';
$img = $row2['image'] ?? 'default.png';



// Handle AJAX answer submission first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_answer') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_POST['question_id']) || !isset($_POST['selected_option'])) {
            throw new Exception('Missing parameters');
        }

        $question_id = (int)$_POST['question_id'];
        $selected_option = $con->real_escape_string($_POST['selected_option']);

        // Validate selected option
        if (!in_array($selected_option, ['a', 'b', 'c', 'd'])) {
            throw new Exception('Invalid option selected');
        }

        // Check if answer is correct
        $check_query = "SELECT correct_answer FROM mock_question WHERE question_id = ?";
        $stmt = $con->prepare($check_query);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $con->error);
        }

        $stmt->bind_param('i', $question_id);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Question not found');
        }

        $row = $result->fetch_assoc();
        $is_correct = ($selected_option == $row['correct_answer']) ? 1 : 0;

        // Store answer
        $insert_query = "INSERT INTO mock_answer (question_id, selected_option, is_correct, session_id) VALUES (?, ?, ?, ?)";
        $stmt = $con->prepare($insert_query);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $con->error);
        }

        $stmt->bind_param('isis', $question_id, $selected_option, $is_correct, $_SESSION['username']);
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        echo json_encode([
            'success' => true,
            'correct' => $is_correct,
            'correct_answer' => $row['correct_answer']
        ]);
        exit;

    } catch (Exception $e) {
        error_log('Error in exam.php: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Normal page load - fetch questions
$questions_query = "SELECT * FROM mock_question LIMIT 10";
$questions_result = $con->query($questions_query);
if (!$questions_result) {
    die("Error fetching questions: " . $con->error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Working Mock Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .question {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .question.correct {
            border-left: 5px solid #4CAF50;
            background-color: #e8f5e9;
        }
        .question.incorrect {
            border-left: 5px solid #f44336;
            background-color: #ffebee;
        }
        .options {
            margin: 10px 0;
        }
        .option {
            display: block;
            margin: 8px 0;
            padding: 8px;
            cursor: pointer;
            border-radius: 4px;
        }
        .option:hover {
            background-color: #f0f0f0;
        }
        .feedback {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .correct-feedback {
            color: #2e7d32;
            background-color: #e8f5e9;
        }
        .incorrect-feedback {
            color: #c62828;
            background-color: #ffebee;
        }
        #score-display {
            margin: 20px 0;
            padding: 15px;
            background-color: #e3f2fd;
            border-radius: 5px;
            font-size: 1.2em;
            text-align: center;
        }
        .loading {
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Mock Test with Instant Feedback</h1>
    
    <div id="questions-container">
        <?php 
        $question_number = 0;
        while ($row = $questions_result->fetch_assoc()): 
            $question_number++;
        ?>
        <div class="question" id="question-<?php echo $row['question_id']; ?>">
            <h3>Question <?php echo $question_number; ?></h3>
            <p><?php echo $row['question_text']; ?></p>
            
            <div class="options">
                <?php foreach (['a', 'b', 'c', 'd'] as $option): ?>
                <label class="option">
                    <input type="radio" 
                           name="question_<?php echo $row['question_id']; ?>" 
                           value="<?php echo $option; ?>"
                           onchange="checkAnswer(<?php echo $row['question_id']; ?>, '<?php echo $option; ?>')">
                    <?php echo strtoupper($option); ?>) <?php echo $row['option_' . $option]; ?>
                </label>
                <?php endforeach; ?>
            </div>
            
            <div class="feedback" id="feedback-<?php echo $row['question_id']; ?>"></div>
        </div>
        <?php endwhile; ?>
    </div>
    
    <div id="score-display">
        <h3>Score: <span id="correct-count">0</span> / <span id="total-questions"><?php echo $question_number; ?></span></h3>
    </div>
    
    <script>
        let correctCount = 0;
        const totalQuestions = <?php echo $question_number; ?>;
        
        function checkAnswer(questionId, selectedOption) {
            const questionElement = document.getElementById(`question-${questionId}`);
            const feedbackElement = document.getElementById(`feedback-${questionId}`);
            
            // Disable all radio buttons for this question
            const radioButtons = questionElement.querySelectorAll('input[type="radio"]');
            radioButtons.forEach(btn => {
                btn.disabled = true;
            });
            
            // Show loading state
            feedbackElement.innerHTML = '<span class="loading">Checking your answer...</span>';
            feedbackElement.className = 'feedback';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'check_answer');
            formData.append('question_id', questionId);
            formData.append('selected_option', selectedOption);
            
            // Send answer to server
            fetch('exam.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    if (data.correct) {
                        questionElement.classList.add('correct');
                        questionElement.classList.remove('incorrect');
                        feedbackElement.innerHTML = '✓ Correct! Well done!';
                        feedbackElement.className = 'feedback correct-feedback';
                        correctCount++;
                    } else {
                        questionElement.classList.add('incorrect');
                        questionElement.classList.remove('correct');
                        feedbackElement.innerHTML = `✗ Incorrect. The correct answer is ${data.correct_answer.toUpperCase()}`;
                        feedbackElement.className = 'feedback incorrect-feedback';
                    }
                    
                    // Update score display
                    document.getElementById('correct-count').textContent = correctCount;
                } else {
                    throw new Error(data.error || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                feedbackElement.innerHTML = 'Error: ' + error.message;
                feedbackElement.className = 'feedback incorrect-feedback';
                
                // Re-enable radio buttons if there was an error
                radioButtons.forEach(btn => {
                    btn.disabled = false;
                });
                
                // Uncheck the selected radio button
                const selectedRadio = questionElement.querySelector(`input[value="${selectedOption}"]`);
                if (selectedRadio) {
                    selectedRadio.checked = false;
                }
            });
        }
    </script>
</body>
</html>