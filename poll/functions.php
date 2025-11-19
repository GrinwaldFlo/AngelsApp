<?php
/**
 * Core utility functions for the polling application
 */

// Constants
define('CONFIG_FILE', __DIR__ . '/config.json');
define('DATA_DIR', __DIR__ . '/data');
define('COOKIE_NAME', 'poll_user_id');
define('COOKIE_DURATION', 30 * 24 * 60 * 60); // 30 days

/**
 * Get or create user GUID from cookie
 * 
 * @return string User GUID
 */
function getUserId(): string {
    if (isset($_COOKIE[COOKIE_NAME]) && !empty($_COOKIE[COOKIE_NAME])) {
        return $_COOKIE[COOKIE_NAME];
    }
    
    // Generate new GUID
    $guid = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    // Set cookie for 30 days
    setcookie(COOKIE_NAME, $guid, time() + COOKIE_DURATION, '/');
    
    return $guid;
}

/**
 * Load configuration from JSON file
 * 
 * @return array|null Configuration array or null on error
 */
function loadConfig(): ?array {
    if (!file_exists(CONFIG_FILE)) {
        return null;
    }
    
    $content = file_get_contents(CONFIG_FILE);
    if ($content === false) {
        return null;
    }
    
    $config = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $config;
}

/**
 * Get a specific poll by ID
 * 
 * @param string $pollId Poll ID to find
 * @return array|null Poll data or null if not found
 */
function getPoll(string $pollId): ?array {
    $config = loadConfig();
    if (!$config || !isset($config['polls'])) {
        return null;
    }
    
    foreach ($config['polls'] as $poll) {
        if ($poll['id'] === $pollId) {
            return $poll;
        }
    }
    
    return null;
}

/**
 * Check if polls are active
 * 
 * @return bool True if polls are active
 */
function arePollsActive(): bool {
    $config = loadConfig();
    return $config && isset($config['active']) && $config['active'] === true;
}

/**
 * Get user's vote for a specific poll
 * 
 * @param string $pollId Poll ID
 * @param string $userId User GUID
 * @return string|null Answer ID or null if user hasn't voted
 */
function getUserVote(string $pollId, string $userId): ?string {
    $pollDir = DATA_DIR . '/' . $pollId;
    
    if (!is_dir($pollDir)) {
        return null;
    }
    
    $pattern = $pollDir . '/' . $userId . '_*.txt';
    $files = glob($pattern);
    
    if (!$files || count($files) === 0) {
        return null;
    }
    
    // Extract answer ID from filename
    $filename = basename($files[0]);
    if (preg_match('/' . preg_quote($userId, '/') . '_(.+)\.txt$/', $filename, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Save user's vote for a poll
 * 
 * @param string $pollId Poll ID
 * @param string $userId User GUID
 * @param string $answerId Answer ID
 * @return bool True on success
 */
function saveVote(string $pollId, string $userId, string $answerId): bool {
    $pollDir = DATA_DIR . '/' . $pollId;
    
    // Create poll directory if it doesn't exist
    if (!is_dir($pollDir)) {
        if (!mkdir($pollDir, 0755, true)) {
            return false;
        }
    }
    
    // Remove previous vote if exists
    removeVote($pollId, $userId);
    
    // Create new vote file
    $filename = $pollDir . '/' . $userId . '_' . $answerId . '.txt';
    $file = fopen($filename, 'w');
    
    if (!$file) {
        return false;
    }
    
    // Lock file for writing
    if (flock($file, LOCK_EX)) {
        fwrite($file, date('Y-m-d H:i:s'));
        flock($file, LOCK_UN);
    }
    
    fclose($file);
    return true;
}

/**
 * Remove user's vote for a poll
 * 
 * @param string $pollId Poll ID
 * @param string $userId User GUID
 * @return bool True on success
 */
function removeVote(string $pollId, string $userId): bool {
    $pollDir = DATA_DIR . '/' . $pollId;
    
    if (!is_dir($pollDir)) {
        return true; // Nothing to remove
    }
    
    $pattern = $pollDir . '/' . $userId . '_*.txt';
    $files = glob($pattern);
    
    if (!$files) {
        return true;
    }
    
    foreach ($files as $file) {
        unlink($file);
    }
    
    return true;
}

/**
 * Get vote counts for a poll
 * 
 * @param string $pollId Poll ID
 * @return array Array of answer ID => count
 */
function getVoteCounts(string $pollId): array {
    $pollDir = DATA_DIR . '/' . $pollId;
    $counts = [];
    
    if (!is_dir($pollDir)) {
        return $counts;
    }
    
    $files = glob($pollDir . '/*.txt');
    
    if (!$files) {
        return $counts;
    }
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Extract answer ID from filename (format: USERID_ANSWERID.txt)
        if (preg_match('/_(.+)\.txt$/', $filename, $matches)) {
            $answerId = $matches[1];
            
            if (!isset($counts[$answerId])) {
                $counts[$answerId] = 0;
            }
            $counts[$answerId]++;
        }
    }
    
    return $counts;
}

/**
 * Get total vote count for a poll
 * 
 * @param string $pollId Poll ID
 * @return int Total number of votes
 */
function getTotalVotes(string $pollId): int {
    $counts = getVoteCounts($pollId);
    return array_sum($counts);
}
