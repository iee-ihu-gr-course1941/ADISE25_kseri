Cards Game REST API

This project implements a RESTful Web API for a simple two-player card game, using PHP and MariaDB/MySQL. It provides endpoints for player management, game creation and progression, card dealing, moves, scoring, and board state retrieval.

Game Flow

1.Create a Game
Endpoint: /game/create
Method: POST
Description: Creates a new game instance, initializes the board with a deck of cards.
Example:
    curl -X POST http://localhost/xeri/game.php/game/create


2.Add Players
Endpoint: /player
Method: POST
Description: Connects or registers a player to a game. Requires username and game_id. Returns a unique token.
Example:
    curl -X POST http://localhost/xeri/game.php/player \
    -H "Content-Type: application/json" \
    -d '{"username":"Alice","game_id":4}'

3. Start Game
Endpoint: /game/start
Method: POST
Description: Starts the game, deals 6 cards to each player, and 4 cards on the table. Randomly selects the first player. Requires game_id and a valid player token.
Example:
curl -X POST http://localhost/xeri/game.php/game/start \
-H "Content-Type: application/json" \
-d '{"game_id":4,"token":"unique_player_token"}'

4. View Player Hand
Endpoint: /game/hand
Method: GET
Description: Retrieves the current player's hand. Requires game_id and token.
Example:
curl -X GET http://localhost/xeri/game.php/game/hand \
-H "Content-Type: application/json" \
-d '{"game_id":4,"token":"unique_player_token"}'


5. View Table Cards
Endpoint: /game/table
Method: GET
Description: Returns the current cards on the table. Requires game_id.
Example:

curl -X GET http://localhost/xeri/game.php/game/table \
-H "Content-Type: application/json" \
-d '{"game_id":4,"token":"unique_player_token"}'


