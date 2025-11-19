<?php
/**
 * Main page - Poll list
 */

require_once __DIR__ . '/functions.php';

// Initialize user
$userId = getUserId();

// Load configuration
$config = loadConfig();

if (!$config || !isset($config['polls'])) {
    die('Configuration error: Unable to load polls.');
}

$polls = $config['polls'];
$isActive = arePollsActive();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polls</title>
    
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
                <div class="text-center mb-4">
                    <h1 class="display-4">
                        <i class="bi bi-clipboard-check"></i> Polls
                    </h1>
                    <?php if (!$isActive): ?>
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="bi bi-info-circle"></i> Polls are currently inactive. You can view results but cannot submit new votes.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="poll-list">
                    <?php foreach ($polls as $poll): ?>
                        <div class="card poll-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <i class="bi bi-question-circle"></i> <?php echo htmlspecialchars($poll['title']); ?>
                                        </h5>
                                        <?php
                                        $userVote = getUserVote($poll['id'], $userId);
                                        $totalVotes = getTotalVotes($poll['id']);
                                        ?>
                                        <small class="text-muted">
                                            <?php if ($userVote): ?>
                                                <i class="bi bi-check-circle-fill text-success"></i> You voted
                                            <?php else: ?>
                                                <i class="bi bi-circle"></i> Not voted yet
                                            <?php endif; ?>
                                            <span class="ms-2">
                                                <i class="bi bi-people-fill"></i> <?php echo $totalVotes; ?> vote<?php echo $totalVotes !== 1 ? 's' : ''; ?>
                                            </span>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="poll.php?id=<?php echo urlencode($poll['id']); ?>" class="btn btn-primary btn-lg">
                                            <i class="bi bi-arrow-right-circle"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
