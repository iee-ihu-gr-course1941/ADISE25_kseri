<?php
require_once "lib/dbconnect.php";
require_once "lib/users.php";
require_once "lib/board.php";

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (empty($contentType)) {
    header('Content-Type: application/json');
    $_SERVER['CONTENT_TYPE'] = 'application/json';
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($input == null) {
    $input = [];
}

if ($request[0] === 'player') {
    if ($method === 'POST') {
        // connect or create player
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

        // Call connectPlayer() from users.php
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

    $game_id = $input['game_id'] ?? null;
    $token = $input['token'] ?? null;

    if (!$game_id || !$token) {
        echo json_encode(['error' => 'game_id and token are required'], JSON_PRETTY_PRINT);
        exit;
    }

    if (!authenticatePlayer($token)) {
        echo json_encode(['error' => 'Invalid token'], JSON_PRETTY_PRINT);
        exit;
    }

    // POST game/start
    if ($method === 'POST' && isset($request[1]) && $request[1] === 'start') {
        $game_id = $input['game_id'] ?? null;
        if (!$game_id) {
            echo json_encode(['error' => 'game_id is required'], JSON_PRETTY_PRINT);
            exit;
        }
        // Validate token
        $token = $input['token'] ?? null;
        if (!$token || !authenticatePlayer($token)) {
            echo json_encode(['error' => 'Invalid or missing token'], JSON_PRETTY_PRINT);
            exit;
        }

        $response = startGame($game_id);
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    if ($method === 'GET' && $request[1] === 'hand') {
        // Get current player's hand
        $player_id = getPlayerByToken($token);
        echo json_encode(getHand($player_id, $game_id), JSON_PRETTY_PRINT);
        exit;
    }

    if ($method === 'GET' && $request[1] === 'table') {
        // Get table cards
        echo json_encode(getTable($game_id), JSON_PRETTY_PRINT);
        exit;
    }
}


// Default: unknown route
http_response_code(404);
echo json_encode(['error' => 'Invalid endpoint'], JSON_PRETTY_PRINT);

?>