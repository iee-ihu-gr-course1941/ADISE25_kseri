<?php

function createGame() {
    global $mysqli;

    $mysqli->begin_transaction();

    // Insert a new game
    $query = "INSERT INTO game (status) VALUES ('initialized')";
    if (!$mysqli->query($query)) {
        $mysqli->rollback();
        return ['error' => 'Failed to create game: ' . $mysqli->error];
    }

    $game_id = $mysqli->insert_id;

    // Initialize board
    $query = "INSERT INTO board (game_id, card_id, location)
              SELECT $game_id, id, 'deck' FROM cards";
    if (!$mysqli->query($query)) {
        $mysqli->rollback();
        return ['error' => 'Failed to initialize board: ' . $mysqli->error];
    }

    $mysqli->commit();

    return [
        'success' => true,
        'game_id' => $game_id,
        'status' => 'initialized'
    ];
}


// Shuffle deck, fill table, player hands and pick a player to start
function startGame($game_id) {
    global $mysqli;

    $mysqli->query("CALL CLEAN_BOARD($game_id)");

    // Check that exactly 2 players joined this game
    $stmt = $mysqli->prepare("SELECT id, username FROM players WHERE game_id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 2) {
        return ['error' => 'Game cannot start: 2 players are required.'];
    }

    $players = [];
    while ($row = $result->fetch_assoc()) {
        $players[] = $row['username'];
    }

    // Shuffle deck
    $mysqli->begin_transaction();
    try {
        // Assign a random position to each card in deck
        $mysqli->query("SET @pos := 0");
        $shuffleQuery = "UPDATE board 
                         SET position = (@pos := @pos + 1)
                         WHERE game_id = $game_id AND location = 'deck'
                         ORDER BY RAND()";
        if (!$mysqli->query($shuffleQuery)) {
            throw new Exception("Failed to shuffle deck: " . $mysqli->error);
        }

        // Give 6 cards to each player
        foreach ($players as $player) {
            $dealQuery = "UPDATE board
                          SET location='hand', owner='$player', position=NULL
                          WHERE game_id=$game_id AND location='deck'
                          ORDER BY position
                          LIMIT 6";
            if (!$mysqli->query($dealQuery)) {
                throw new Exception("Failed to deal cards to $player: " . $mysqli->error);
            }
        }

        // Give 4 cards to the table (NO new transaction here)
        $mysqli->query("
            SET @pos := (
                SELECT COALESCE(MAX(position), 0)
                FROM board
                WHERE game_id = $game_id AND location = 'table'
            )
        ");

        $dealTable = "
            UPDATE board
            SET location='table',
                owner=NULL,
                position = (@pos := @pos + 1)
            WHERE game_id = $game_id
            AND location = 'deck'
            ORDER BY position
            LIMIT 4
        ";

        if (!$mysqli->query($dealTable)) {
            throw new Exception("Failed to deal cards to table: " . $mysqli->error);
        }

        // Pick random current player
        $pickPlayerQuery = "UPDATE game
                            SET status='started',
                                current_player_id = (
                                    SELECT id FROM players WHERE game_id=$game_id ORDER BY RAND() LIMIT 1
                                )
                            WHERE id=$game_id AND status='initialized'";
        if (!$mysqli->query($pickPlayerQuery)) {
            throw new Exception("Failed to set current player: " . $mysqli->error);
        }

        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['error' => $e->getMessage()];
    }

    return ['success' => true, 'message' => 'Game started', 'players' => $players];
}

function playCard($game_id, $player_id, $card_id) {
    global $mysqli;

    $mysqli->begin_transaction();

try {
    // 1. Get played card details
    $playerName = getPlayerUsernameById($player_id);

    $stmt = $mysqli->prepare("
        SELECT c.rank, c.suit
        FROM board b
        JOIN cards c ON b.card_id = c.id
        WHERE b.card_id = ?
          AND b.owner = ?
          AND b.location = 'hand'
    ");
    $stmt->bind_param('is', $card_id, $playerName);
    $stmt->execute();
    $playedCard = $stmt->get_result()->fetch_assoc();

    if (!$playedCard) {
        throw new Exception("Card does not belong to player");
    }

    // 2. Get top table card BEFORE playing
    $stmt = $mysqli->prepare("
        SELECT c.rank, c.suit
        FROM board b
        JOIN cards c ON b.card_id = c.id
        WHERE b.game_id = ?
          AND b.location = 'table'
        ORDER BY b.position DESC
        LIMIT 1
    ");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $topTableCard = $stmt->get_result()->fetch_assoc();

    // Get count of table cards
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) as cnt
        FROM board
        WHERE game_id = ? AND location = 'table'
    ");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $countResult = $stmt->get_result()->fetch_assoc();
    $tableCount = (int) $countResult['cnt'];

    // Move card to table
    $mysqli->query("
        SET @pos := (
            SELECT COALESCE(MAX(position), 0)
            FROM board
            WHERE game_id = $game_id AND location = 'table'
        )
    ");

    $stmt = $mysqli->prepare("
        UPDATE board
        SET location = 'table',
            owner = NULL,
            position = (@pos := @pos + 1)
        WHERE card_id = ?
          AND game_id = ?
    ");
    $stmt->bind_param('ii', $card_id, $game_id);
    $stmt->execute();

    // Capture logic
    $captured = false;
    $xeri = false;

    if ($topTableCard && $playedCard['rank'] === $topTableCard['rank']) {
        
        $stmt = $mysqli->prepare("
            UPDATE board
            SET location = 'discard',
                owner = ?
            WHERE game_id = ?
                AND location = 'table'
        ");
        $stmt->bind_param('si', $playerName, $game_id);
        $stmt->execute();
        $captured = true;

        // Xeri check
        if ($tableCount === 1) 
            $xeri = true;
    }

    $mysqli->commit();

    return [
            'success' => true,
            'played_card' => $playedCard,
            'captured' => $captured,
            'xeri' => $xeri,
            'player' => $playerName,
        ];

} catch (Exception $e) {
    $mysqli->rollback();
    return [
            'success' => false,
            'error' => $e->getMessage()
        ];
}
}


function getHand($player_id, $game_id) {
    global $mysqli;

    // Get username from player_id
    $stmt = $mysqli->prepare("SELECT username FROM players WHERE id = ?");
    $stmt->bind_param('i', $player_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        return [];
    }
    $username = $row['username'];

    $query = "
        SELECT b.card_id, c.suit, c.rank
        FROM board b
        JOIN cards c ON b.card_id = c.id
        WHERE b.owner = ? AND b.location = 'hand' AND b.game_id = ?
        ORDER BY b.position";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('si', $username, $game_id); 
    $stmt->execute();
    $result = $stmt->get_result();

    $hand = [];
    while ($row = $result->fetch_assoc()) {
        $hand[] = $row;
    }
    return $hand;
}


function getTable($game_id) {
    global $mysqli;

    $query = "
        SELECT b.card_id, c.suit, c.rank
        FROM board b
        JOIN cards c ON b.card_id = c.id
        WHERE b.location = 'table' AND b.game_id = ?
        ORDER BY b.position";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i',$game_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $table = [];
    while ($row = $result->fetch_assoc()) {
        $table[] = $row;
    }
    return $table;
}

?>
