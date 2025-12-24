<?php

// Connect a player (create if new)
function connectPlayer() {
    global $mysqli;

    // GET JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $game_id = $input['game_id'] ?? null;

    if (empty($username)) {
        return ['error' => 'Username is required.'];
    }
    if (!$game_id) {
        return ['error' => 'game_id is required.'];
    }

    // Check if game exists
    $query = "SELECT id FROM game WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) return ['error' => 'Database error: ' . $mysqli->error];
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        return ['error' => 'Game not found.'];
    }

    // Check if user exists
    $query = "SELECT id, token, game_id FROM players WHERE username = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) return ['error' => 'Database error: ' . $mysqli->error];
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // If player already assigned to a game, return error
        if ($row['game_id'] && $row['game_id'] != $game_id) {
            return ['error' => 'Player is already in another game'];
        }
        // Assign to this game if not already
        if (!$row['game_id']) {
            $stmt2 = $mysqli->prepare("UPDATE players SET game_id = ? WHERE id = ?");
            $stmt2->bind_param('ii', $game_id, $row['id']);
            $stmt2->execute();
        }

        return ['success' => true, 'token' => $row['token']];
    }

    // Count players already in this game
    $stmt = $mysqli->prepare("SELECT COUNT(*) as player_count FROM players WHERE game_id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $countRow = $result->fetch_assoc();

    if ($countRow['player_count'] >= 2) {
        return ['error' => 'This game already has 2 players.'];
    }

    // Generate new token
    $token = bin2hex(random_bytes(16));

    // Insert new player
    $query = "INSERT INTO players (username, token, score, game_id) VALUES (?, ?, 0, ?)";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) return ['error' => 'Database error: ' . $mysqli->error];
    $stmt->bind_param('ssi', $username, $token, $game_id);

    if ($stmt->execute()) {
        return ['success' => true, 'token' => $token];
    } else {
        return ['error' => 'Failed to connect player: ' . $mysqli->error];
    }   
}

// Authenticate player by token
function authenticatePlayer($token) {
    global $mysqli;

    $query = "SELECT id FROM players WHERE token = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) return false;
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

// Get player token by username
function getPlayerTokenByUsername($username) {
    global $mysqli;

    $query = "SELECT token FROM players WHERE username = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) return null;
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['token'];
    }

    return null;
}

// Get player info by token
function getPlayerByToken($token) {
    global $mysqli;

    $query = "SELECT id FROM players WHERE token = ?";
    $stmt = $mysqli->prepare($query);
    if (!$stmt) return null;
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['id'];
    }

    return null;
}

// Get owner name by player id
function getPlayerUsernameById($player_id) {
    global $mysqli;

    $query = "SELECT username FROM players WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        throw new Exception("Invalid player ID");
    }

    return $row['username'];
}

// Get current player by his id
function getCurrentPlayerId($game_id) {
    global $mysqli;

    $query = "
        SELECT current_player_id
        FROM game
        WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    
    $row = $stmt->get_result()->fetch_assoc();
    return (int) $row['current_player_id'];
}
?>