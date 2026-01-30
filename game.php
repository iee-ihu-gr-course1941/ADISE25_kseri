<?php
require_once "lib/dbconnect.php";
require_once "lib/users.php";
require_once "lib/board.php";

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

if ($method === 'GET') {
    $input = $_GET;
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

// player endpoint
if ($request[0] === 'player') {
    if ($method === 'POST') {
        $username = $input['username'] ?? null;
        $game_id = $input['game_id'] ?? null;

        if (!$username) {
            http_response_code(400);
            echo json_encode(['error' => 'Username is required'], JSON_PRETTY_PRINT);
            exit;
        }
        if (!$game_id) {
            http_response_code(400);
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }

        $response = connectPlayer();
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}

// game endpoint
if ($request[0] === 'game') {
    // POST requests
    // POST game/create
    if ($method === 'POST' && isset($request[1]) && $request[1] === 'create') {
        $response = createGame();
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    // POST game/start
    if ($method === 'POST' && isset($request[1]) && $request[1] === 'start') {
        $game_id = $input['game_id'] ?? null;
        $token = $input['token'] ?? null;
        
        if (!$game_id) {
            http_response_code(400);
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }
        if (!$token || !authenticatePlayer($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or missing token'], JSON_PRETTY_PRINT);
            exit;
        }

        $response = startGame($game_id);
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    // POST game/restart
    if ($method === 'POST' && isset($request[1]) && $request[1] === 'restart') {
        $game_id = $input['game_id'] ?? null;
        $token = $input['token'] ?? null;

        if (!$game_id) {
            http_response_code(400);
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }

        if (!$token || !authenticatePlayer($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token for this game'], JSON_PRETTY_PRINT);
            exit;
        }

        $response = restartGame($game_id);
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    // PUT requests
    // PUT game/play
    if ($method === 'PUT' && isset($request[1]) && $request[1] === 'play') {
        $token = $input['token'] ?? null;
        $game_id = $input['game_id'] ?? null;
        $card_id = $input['card_id'] ?? null;
        
        if (!$token || !authenticatePlayer($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or missing token'], JSON_PRETTY_PRINT);
            exit;
        }

        if (!$game_id) {
            http_response_code(400);
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }

        if (!$card_id) {
            http_response_code(400);
            echo json_encode(['error' => 'card_id is required'], JSON_PRETTY_PRINT);
            exit;
        }

        $player_id = getPlayerByToken($token);
        $response = playCard($game_id, $player_id, $card_id);
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;

    }

    // GET requests
    if ($method === 'GET' && isset($request[1])) {
        $game_id = $input['game_id'] ?? null;
        $token = $input['token'] ?? null;

        if (!$game_id || !$token) {
            http_response_code(400);
            echo json_encode(['error' => 'game_id and token are required'], JSON_PRETTY_PRINT);
            exit;
        }

        if (!authenticatePlayer($token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid token'], JSON_PRETTY_PRINT);
            exit;
        }
        // GET game/hand
        if ($request[1] === 'hand') {
            $player_id = getPlayerByToken($token);
            echo json_encode(getHand($player_id, $game_id), JSON_PRETTY_PRINT);
            exit;
        }
        // GET game/table
        if ($request[1] === 'table') {
            echo json_encode(getTable($game_id), JSON_PRETTY_PRINT);
            exit;
        }
    }
}

// GET status/game
if ($method === 'GET' && $request[0] === 'status' && isset($request[1]) && $request[1] === 'game') {
    $game_id = $input['game_id'] ?? null;
    global $mysqli;

    if (!$game_id) {
        http_response_code(400);
        echo json_encode(['error' => 'game_id required'], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get game status
    $stmt = $mysqli->prepare("SELECT * FROM game WHERE id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();
    
    if (!$game) { 
        http_response_code(404);
        echo json_encode(['error' => 'Game not found'], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get players in the game
    $stmt = $mysqli->prepare("SELECT id, username, score, last_action FROM players WHERE game_id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $players = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'game' => $game,
        'players' => $players
    ], JSON_PRETTY_PRINT);
    exit;
}

// Response for invalid endpoints
http_response_code(404);
echo json_encode(['error' => 'Invalid endpoint'], JSON_PRETTY_PRINT);
?>