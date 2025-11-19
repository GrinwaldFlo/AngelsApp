<?php
/**
 * API endpoint for AJAX results updates
 */

require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// Get poll ID from URL
$pollId = $_GET['id'] ?? null;

if (!$pollId) {
    echo json_encode(['success' => false, 'error' => 'Poll ID required']);
    exit;
}

// Load poll data
$poll = getPoll($pollId);

if (!$poll) {
    echo json_encode(['success' => false, 'error' => 'Poll not found']);
    exit;
}

// Get vote counts
$voteCounts = getVoteCounts($pollId);
$totalVotes = getTotalVotes($pollId);

// Calculate percentages
$percentages = [];
foreach ($poll['answers'] as $answer) {
    $count = $voteCounts[$answer['id']] ?? 0;
    $percentages[$answer['id']] = $totalVotes > 0 ? ($count / $totalVotes) * 100 : 0;
}

// Return JSON response
echo json_encode([
    'success' => true,
    'pollId' => $pollId,
    'totalVotes' => $totalVotes,
    'counts' => $voteCounts,
    'percentages' => $percentages
]);
