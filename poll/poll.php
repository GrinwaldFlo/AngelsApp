<?php
/**
 * Individual poll page
 */

require_once __DIR__ . '/functions.php';

// Initialize user
$userId = getUserId();

// Get poll ID from URL
$pollId = $_GET['id'] ?? null;

if (!$pollId) {
    header('Location: index.php');
    exit;
}

// Load poll data
$poll = getPoll($pollId);

if (!$poll) {
    header('Location: index.php');
    exit;
}

// Check if polls are active
$isActive = arePollsActive();

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isActive) {
    if (isset($_POST['answer'])) {
        $answerId = $_POST['answer'];

        // Validate answer exists
        $validAnswer = false;
        foreach ($poll['answers'] as $answer) {
            if ($answer['id'] === $answerId) {
                $validAnswer = true;
                break;
            }
        }

        if ($validAnswer) {
            saveVote($pollId, $userId, $answerId);
        }

        // Redirect to same page to prevent form resubmission
        header('Location: poll.php?id=' . urlencode($pollId));
        exit;
    }

    // Handle vote removal
    if (isset($_POST['remove_vote'])) {
        removeVote($pollId, $userId);
        header('Location: poll.php?id=' . urlencode($pollId));
        exit;
    }
}

// Get user's current vote
$userVote = getUserVote($pollId, $userId);

// Get vote counts
$voteCounts = getVoteCounts($pollId);
$totalVotes = getTotalVotes($pollId);

// Calculate percentages
$percentages = [];
foreach ($poll['answers'] as $answer) {
    $count = $voteCounts[$answer['id']] ?? 0;
    $percentages[$answer['id']] = $totalVotes > 0 ? ($count / $totalVotes) * 100 : 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($poll['title']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Back button -->
                <div class="mb-4">
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left-circle"></i>
                    </a>
                </div>

                <!-- Question -->
                <div class="text-center mb-4">
                    <h2 class="question-text"><?php echo htmlspecialchars($poll['question']); ?></h2>
                </div>

                <?php if (!$userVote && !$isActive): ?>
                    <!-- Polls are inactive and user hasn't voted -->
                    <div class="alert alert-info text-center" role="alert">
                        <i class="bi bi-info-circle"></i> Les votes sont fermés.
                    </div>
                <?php endif; ?>

                <?php if (!$userVote && $isActive): ?>
                    <!-- Show voting options -->
                    <form method="POST" action="">
                        <div class="row g-3">
                            <?php foreach ($poll['answers'] as $answer): ?>
                                <div class="col-12">
                                    <button type="submit" name="answer" value="<?php echo htmlspecialchars($answer['id']); ?>" class="btn btn-answer w-100">
                                        <?php echo htmlspecialchars($answer['text']); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Show results after voting or when polls are inactive -->
                    <div id="results-container">
                        <?php if ($userVote && $isActive): ?>
                            <div class="text-center mb-3">
                                <form method="POST" action="" class="d-inline">
                                    <button type="submit" name="remove_vote" value="1" class="btn btn-outline-danger">
                                        <i class="bi bi-x-circle"></i> Change ton vote
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="results" id="results">
                            <?php foreach ($poll['answers'] as $answer): ?>
                                <?php
                                $count = $voteCounts[$answer['id']] ?? 0;
                                $percentage = $percentages[$answer['id']];
                                $isUserChoice = ($userVote === $answer['id']);
                                ?>
                                <div class="result-item mb-3 <?php echo $isUserChoice ? 'user-choice' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($answer['text']); ?></strong>
                                            <?php if ($isUserChoice): ?>
                                                <i class="bi bi-check-circle-fill text-success ms-2"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="badge bg-primary"><?php echo $count; ?></span>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 30px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%;" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?php if ($percentage > 0): ?>
                                                <?php echo number_format($percentage, 1); ?>%
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-4 text-muted">
                                <small>
                                    <i class="bi bi-people-fill"></i> 
                                    Total : <span id="total-votes"><?php echo $totalVotes; ?></span>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <?php if ($userVote || !$isActive): ?>
    <!-- Auto-refresh results every 2 seconds -->
    <script>
        function updateResults() {
            fetch('api.php?id=<?php echo urlencode($pollId); ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update total votes
                        document.getElementById('total-votes').textContent = data.totalVotes;
                        
                        // Update each answer's count and percentage
                        const resultsContainer = document.getElementById('results');
                        
                        <?php foreach ($poll['answers'] as $answer): ?>
                        {
                            const answerId = '<?php echo addslashes($answer['id']); ?>';
                            const count = data.counts[answerId] || 0;
                            const percentage = data.percentages[answerId] || 0;
                            
                            // Find the result item for this answer
                            const resultItems = resultsContainer.querySelectorAll('.result-item');
                            let targetItem = null;
                            
                            resultItems.forEach(item => {
                                const text = item.querySelector('strong').textContent;
                                if (text === '<?php echo addslashes($answer['text']); ?>') {
                                    targetItem = item;
                                }
                            });
                            
                            if (targetItem) {
                                // Update count badge
                                const badge = targetItem.querySelector('.badge');
                                badge.textContent = count;
                                
                                // Update progress bar
                                const progressBar = targetItem.querySelector('.progress-bar');
                                progressBar.style.width = percentage + '%';
                                progressBar.setAttribute('aria-valuenow', percentage);
                                
                                if (percentage > 0) {
                                    progressBar.textContent = percentage.toFixed(1) + '%';
                                } else {
                                    progressBar.textContent = '';
                                }
                            }
                        }
                        <?php endforeach; ?>
                    }
                })
                .catch(error => console.error('Error updating results:', error));
        }
        
        // Update results every 2 seconds
        setInterval(updateResults, 2000);
    </script>
    <?php endif; ?>
</body>
</html>
