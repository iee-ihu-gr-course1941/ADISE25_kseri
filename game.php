<?php
require_once "lib/dbconnect.php";
require_once "lib/users.php";
require_once "lib/board.php";

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$resource = $request[0] ?? null;

if ($resource === 'player') {
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

if ($resource === 'game') {

    // POST game/create
    if ($method === 'POST' && isset($request[1]) && $request[1] === 'create') {
        $response = createGame();
        echo json_encode($response, JSON_PRETTY_PRINT);
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
}


// Default: unknown route
http_response_code(404);
echo json_encode(['error' => 'Invalid endpoint'], JSON_PRETTY_PRINT);
