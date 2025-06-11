<?php
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function generateToken() {
    return bin2hex(random_bytes(16));
}

function hasVoted($user_id, $election_id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND election_id = ?");
    $stmt->bind_param("ii", $user_id, $election_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags($data));
}
?>