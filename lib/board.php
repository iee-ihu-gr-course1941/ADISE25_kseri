<?php

function createGame() {
    global $mysqli;

    $mysqli->begin_transaction();

    // Check for active games before inserting a new one
    $check = $mysqli->query("SELECT id FROM game WHERE status IN ('initialized', 'started')");
    if ($check->num_rows > 0) {
        $mysqli->rollback();
        return ['error' => 'A game is already in progress. Please finish it or abort it first.'];
    }
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

    // Check if 
    $stmt = $mysqli->prepare("SELECT status FROM game WHERE id = ? FOR UPDATE");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $gameRes = $stmt->get_result();
    $game = $gameRes->fetch_assoc();

    if (!$game) {
        return ['error' => 'Game not found.'];
    }

    if ($game['status'] !== 'initialized') {
            return ['error' => 'Game already started.'];
    } 

    // Check that exactly 2 players joined this game
    $stmt = $mysqli->prepare("SELECT id, username FROM players WHERE game_id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 2) {
        return ['error' => 'Game cannot start: 2 players are required.'];
    }

    if (!$mysqli->query("CALL CLEAN_BOARD($game_id)")) {
            throw new Exception("Failed to clean board");
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

        // Give 4 cards to the table
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

        // Give 6 cards to each player
        dealCards($game_id, 6);

        // Pick random current player
        $pickPlayerQuery = "UPDATE game
                            SET status='started',
                                current_player_id = (
                                    SELECT id
                                    FROM players
                                    WHERE game_id=$game_id ORDER BY RAND() LIMIT 1
                                )
                            WHERE id=$game_id AND status='initialized'";
        if (!$mysqli->query($pickPlayerQuery)) {
            throw new Exception("Failed to set current player: " . $mysqli->error);
        }

        if ($mysqli->affected_rows === 0) {
            throw new Exception("Game already started.");
        }

        $mysqli->commit();
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['error' => $e->getMessage()];
    }

    return ['success' => true, 'message' => 'Game started', 'players' => $players];
}

function restartGame($game_id) {
    global $mysqli;

    $stmt = $mysqli->prepare("SELECT status FROM game WHERE id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();

    if (!$game) {
        return ['error' => 'Game not found.'];
    }

    if ($game['status'] === 'started') {
        return ['error' => 'Game is in progress. Cannot restart a started game.'];
    }

    if (!$mysqli->query("CALL CLEAN_BOARD($game_id)")) {
        return ['error' => 'Failed to clean board: '];
    }

    return startGame($game_id);
}


function playCard($game_id, $player_id, $card_id) {
    global $mysqli;

    $mysqli->begin_transaction();

    try {
        // Check game status
        $stmt = $mysqli->prepare("SELECT status FROM game WHERE id = ?");
        $stmt->bind_param('i', $game_id);
        $stmt->execute();
        $gameStatus = $stmt->get_result()->fetch_assoc();

        if (!$gameStatus) {
            throw new Exception("Game not found");
        }

        if ($gameStatus['status'] === 'ended') {
            throw new Exception("Game has ended");
        }

        // Get played card details
        $playerName = getPlayerUsernameById($player_id);
        $current_player = getCurrentPlayerId($game_id);

        if ($player_id !== $current_player) {
            throw new Exception("Wait for your turn");
        }

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

        // Get top table card BEFORE playing
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
        $stmt = $mysqli->prepare("
            SELECT COALESCE(MAX(position), 0) AS max_pos
            FROM board
            WHERE game_id = ? AND location = 'table'
            ");
        $stmt->bind_param('i', $game_id);
        $stmt->execute();
        $maxPos = (int)$stmt->get_result()->fetch_assoc()['max_pos'];

        $stmt = $mysqli->prepare("
            UPDATE board
            SET location = 'table',
                owner = NULL,
                position = ?
            WHERE card_id = ? AND game_id = ?
            ");
        $newPos = $maxPos + 1;
        $stmt->bind_param('iii', $newPos, $card_id, $game_id);
        $stmt->execute();

        // Mark player as having acted
        touchPlayer($player_id);


        // Capture logic
        $captured = false;
        $xeri = false;

        if ($topTableCard && ($playedCard['rank'] === $topTableCard['rank'] || $playedCard['rank'] === 'J')) {
            // Get all cards being captured
            $stmt = $mysqli->prepare("
                SELECT c.rank, c.suit 
                FROM board b 
                JOIN cards c ON b.card_id = c.id 
                WHERE b.game_id = ? AND b.location = 'table'
            ");
            $stmt->bind_param('i', $game_id);
            $stmt->execute();
            $cardsBeingCaptured = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Move captured cards to player's discard pile
            // Get the current max position in discard 
            $posRes = $mysqli->query(
                "SELECT COALESCE(MAX(position), 0) as maxp
                 FROM board
                 WHERE game_id = $game_id AND location = 'discard'");
            $nextDiscardPos = (int)$posRes->fetch_assoc()['maxp'] + 1;

            $stmt = $mysqli->prepare("
                UPDATE board
                SET location = 'discard', owner = ?, position = ? 
                WHERE game_id = ? AND location = 'table'
            ");
            $stmt->bind_param('sii', $playerName, $nextDiscardPos, $game_id);
            $stmt->execute();

            $captured = true;
            
            // Xeri check
            if ($tableCount === 1 && $playedCard['rank'] !== 'J') {
                $xeri = true;
            }
            // Update score
            updateScore($game_id, $player_id, $xeri, $cardsBeingCaptured);
        }

        // Change the current player
        $stmt = $mysqli->prepare("
            UPDATE game g
            SET current_player_id = (
                SELECT p.id
                FROM players p
                WHERE p.game_id = g.id
                AND p.id != g.current_player_id
                LIMIT 1
            )
            WHERE g.id = ?");
        $stmt->bind_param('i', $game_id);
        $stmt->execute();
        
        $gameOver = checkGameOver($game_id);
        $winner_id = null;

        if ($gameOver) {
            $winner_id = determineWinner($game_id);

            $stmt = $mysqli->prepare("
                UPDATE game
                SET status = 'ended',
                    winner_id = ?
                WHERE id = ?");
            $stmt->bind_param('ii', $winner_id, $game_id);
            $stmt->execute();
        }

        dealCards($game_id, 6);

        $mysqli->commit();

        return [
                'success' => true,
                'played_card' => $playedCard,
                'captured' => $captured,
                'xeri' => $xeri,
                'player' => $playerName,
                'game_over' => $gameOver,
                'winner_id' => $winner_id ?? null
            ];

    } catch (Exception $e) {
        $mysqli->rollback();
        return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
}

function determineWinner($game_id) {
    global $mysqli;
    
    // Find who made the LAST capture
    $stmt = $mysqli->prepare("
        SELECT owner, card_id
        FROM board 
        WHERE game_id = ? AND location = 'discard' 
        ORDER BY position DESC, card_id DESC LIMIT 1
    ");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $lastCapturerRow = $stmt->get_result()->fetch_assoc();
    $lastCapturer = $lastCapturerRow['owner'] ?? null;
    
    // If there are leftover cards on the table, give them to the last capturer
    if ($lastCapturer) {
        // Fetch leftover cards to calculate points
        $stmt = $mysqli->prepare(
            "SELECT c.rank, c.suit
             FROM board b JOIN cards c ON b.card_id = c.id
             WHERE b.game_id = ? AND b.location = 'table'");
        $stmt->bind_param('i', $game_id);
        $stmt->execute();
        $leftoverCards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (count($leftoverCards) > 0) {
            // Update the score for those leftover cards
            $stmt = $mysqli->prepare(
                "SELECT id
                 FROM players
                 WHERE username = ? AND game_id = ?");
            $stmt->bind_param('si', $lastCapturer, $game_id);
            $stmt->execute();
            $lcp_id = $stmt->get_result()->fetch_assoc()['id'];
            
            updateScore($game_id, $lcp_id, false, $leftoverCards);

            // Move them to discard
            $stmt = $mysqli->prepare(
                "UPDATE board 
                 SET location = 'discard', owner = ?
                 WHERE game_id = ? AND location = 'table'");
            $stmt->bind_param('si', $lastCapturer, $game_id);
            $stmt->execute();
        }
    }
    
    // Find player with MOST cards for the +3 bonus
    $stmt = $mysqli->prepare("
        SELECT b.owner, COUNT(*) as card_count
        FROM board b
        WHERE b.game_id = ? AND b.location = 'discard'
        GROUP BY b.owner
        ORDER BY card_count DESC LIMIT 1
    ");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && $result['owner']) {
        $stmt = $mysqli->prepare(
            "UPDATE players
             SET score = score + 3
             WHERE username = ? AND game_id = ?");
        $stmt->bind_param('si', $result['owner'], $game_id);
        $stmt->execute();
    }

    // Return the ID of the player with the highest final score
    $stmt = $mysqli->prepare(
        "SELECT id 
         FROM players
         WHERE game_id = ? ORDER BY score DESC, id ASC LIMIT 1");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['id'];
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

function checkGameOver($game_id) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("
        SELECT 
            SUM(CASE WHEN location = 'deck' THEN 1 ELSE 0 END) as deck_count,
            SUM(CASE WHEN location = 'hand' THEN 1 ELSE 0 END) as hand_count
        FROM board 
        WHERE game_id = ?
    ");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $deckCount = (int)($result['deck_count']);
    $handCount = (int)($result['hand_count'] ?? 0);
    
    return ($deckCount === 0 && $handCount === 0);
}

function updateScore($game_id, $player_id, $xeri, $capturedCards) {
    global $mysqli;
    
    $pointsToAdd = 0;

    foreach ($capturedCards as $card) {
        // 2 of Spades = 1 point
        if ($card['rank'] === '2' && $card['suit'] === 'spades') {
            $pointsToAdd += 1;
        }

        // 10 of Diamonds = 1 point
        if ($card['rank'] === '10' && $card['suit'] === 'diamonds') {
            $pointsToAdd += 1;
        }
        
        // J, Q, K = 1 point each
        if (in_array($card['rank'], ['J', 'Q', 'K'])) {
            $pointsToAdd += 1;
        }
        
        // Other 10s = 1 point each
        if ($card['rank'] === '10' && $card['suit'] !== 'diamonds') {
            $pointsToAdd += 1;
        }
    }

    if ($xeri) {
        $pointsToAdd += 10;
    }

    // Update score in players table
    $stmt = $mysqli->prepare(
        "UPDATE players
         SET score = score + ?
         WHERE id = ? AND game_id = ?");
    $stmt->bind_param('iii', $pointsToAdd, $player_id, $game_id);
    $stmt->execute();
}

function deleteGameAndPlayers($game_id) {
    global $mysqli;

    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("SELECT status FROM game WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $game_id);
        $stmt->execute();
        $game = $stmt->get_result()->fetch_assoc();

        if (!$game) {
            throw new Exception("Game not found.");
        }

        $stmt = $mysqli->prepare("UPDATE game SET winner_id = NULL, current_player_id = NULL WHERE id = ?");
        $stmt->bind_param('i', $game_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to clear winner/current player: ");
        }

        // Delete board rows
        $stmt = $mysqli->prepare("DELETE FROM board WHERE game_id = ?");
        $stmt->bind_param('i', $game_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete board rows: " . $stmt->error);
        }

        // Delete players
        $stmt = $mysqli->prepare("DELETE FROM players WHERE game_id = ?");
        $stmt->bind_param('i', $game_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete players: ");
        }

        // Delete game
        $stmt = $mysqli->prepare("DELETE FROM game WHERE id = ?");
        $stmt->bind_param('i', $game_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete game: ");
        }

        $mysqli->commit();
        return ['success' => true, 'message' => 'Game and players deleted.'];
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}


function dealCards($game_id, $cards_per_player = 6) {
    global $mysqli;

    // Are there any cards currently in hands?
    $stmt = $mysqli->prepare(
        "SELECT COUNT(*) as hand_count
         FROM board
         WHERE game_id = ? AND location = 'hand'");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['hand_count'] > 0) {
        return false; 
    }

    // Are there enough cards in the deck?
    $stmt = $mysqli->prepare(
        "SELECT COUNT(*) as deck_count
         FROM board
         WHERE game_id = ? AND location = 'deck'");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()['deck_count'] < 12) {
        return false; 
    }

    // Get the two players in this game
    $stmt = $mysqli->prepare(
        "SELECT username
         FROM players
         WHERE game_id = ?");
    $stmt->bind_param('i', $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $players = [];
    while ($row = $result->fetch_assoc()) {
        $players[] = $row['username'];
    }

    if (count($players) !== 2) return false;

    foreach ($players as $player) {
        $dealQuery = "UPDATE board
                      SET location='hand', owner=?, position=NULL
                      WHERE game_id=? AND location='deck'
                      ORDER BY position ASC
                      LIMIT ?";
        $stmt = $mysqli->prepare($dealQuery);
        $stmt->bind_param('sii', $player, $game_id, $cards_per_player);
        $stmt->execute();
    }
    return true;
}
?>
