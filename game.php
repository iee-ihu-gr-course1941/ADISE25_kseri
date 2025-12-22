<?php
require_once "lib/dbconnect.php";
require_once "lib/users.php";
require_once "lib/board.php";

// CORS headers
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

        if ($request[1] === 'hand') {
            $player_id = getPlayerByToken($token);
            echo json_encode(getHand($player_id, $game_id), JSON_PRETTY_PRINT);
            exit;
        }

        if ($request[1] === 'table') {
            echo json_encode(getTable($game_id), JSON_PRETTY_PRINT);
            exit;
        }
    }
}

// Default: unknown route
http_response_code(404);
echo json_encode(['error' => 'Invalid endpoint']);
?>