<?php
require_once "lib/dbconnect.php";
require_once "lib/users.php";
require_once "lib/board.php";

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_GET;
}
// POST player
if ($request[0] === 'player') {
    if ($method === 'POST') {
        $username = $input['username'] ?? null;
        $game_id = $input['game_id'] ?? null;

        if (!$username) {
            echo json_encode(['error' => 'Username is required'], JSON_PRETTY_PRINT);
            exit;
        }
        if (!$game_id) {
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }

        $response = connectPlayer();
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}

if ($request[0] === 'game') {

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
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }
        if (!$token || !authenticatePlayer($token)) {
            echo json_encode(['error' => 'Invalid or missing token'], JSON_PRETTY_PRINT);
            exit;
        }

        $response = startGame($game_id);
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    // POST game/play
    if ($method === 'POST' && isset($request[1]) && $request[1] === 'play') {
        $token = $input['token'] ?? null;
        $game_id = $input['game_id'] ?? null;
        $card_id = $input['card_id'] ?? null;
        
        if (!$token || !authenticatePlayer($token)) {
            echo json_encode(['error' => 'Invalid or missing token'], JSON_PRETTY_PRINT);
            exit;
        }

        if (!$game_id) {
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }

        if (!$card_id) {
            echo json_encode(['error' => 'card_id is required'], JSON_PRETTY_PRINT);
            exit;
        }

        $player_id = getPlayerByToken($token);
        $response = playCard($game_id, $player_id, $card_id);
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;

    }
        

    // GET endpoints
    if ($method === 'GET' && isset($request[1])) {
        $game_id = $input['game_id'] ?? null;
        $token = $input['token'] ?? null;

        if (!$game_id || !$token) {
            echo json_encode(['error' => 'game_id and token are required']);
            exit;
        }

        if (!authenticatePlayer($token)) {
            echo json_encode(['error' => 'Invalid token']);
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
    
    if (!$game_id) {
        http_response_code(400);
        echo json_encode(['error' => 'game_id required']);
        exit;
    }
    
    // Get game status
    $stmt = $mysqli->prepare("SELECT * FROM game WHERE id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();
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

// Default response for invalid endpoints
http_response_code(404);
echo json_encode(['error' => 'Invalid endpoint']);
?>