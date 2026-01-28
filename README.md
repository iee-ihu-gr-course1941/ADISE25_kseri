## Kseri Card Game API

A RESTful API built with PHP and MariaDB for the "Kseri" card game.
This project was developed as part of the ADISE course and is hosted on the IHU (IEE) servers.

### Author
- **Name:** Vasilis Petsalakis
- **Student ID:** it185328
- **Course:** ADISE
- **Institution:** International Hellenic University (IHU)
- **Academic Year:** 2025–2026

## API Endpoints

### 1. Create Game

Initializes a new game session

**Endpoint:** `/game/create`  
**Method:** POST  

```bash
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/create
```

### 2. Join Game

Registers a player to a specific game. Returns a unique token required for all moves

**Endpoint:** `/player`  
**Method:** POST 

```bash
curl -X POST "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/player" -H "Content-Type: application/json" -d "{\"username\":\"username\", \"game_id\":id}"
```

### 3. Start Game

Shuffles deck, Deals cards to each player and to the table

**Endpoint:** `/game/start`  
**Method:** POST

```bash
curl -X POST "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/start" -H "Content-Type: application/json" -d "{\"game_id\":game_id,\"token\":\"token\"}"
```
### 4. Get Player Hand

Returns the current cards by the authenticated player

**Endpoint:** `/game/hand`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/hand?game_id=id&token=token"
```
### 5. Get Table Cards

Returns all cards currently on the table

**Endpoint:** `/game/table`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/table?game_id=id&token=token"
```

### 6. Play Card

Moves a card from player's hand to the table

**Endpoint:** `/game/play`  
**Method:** PUT

```bash 
curl -X PUT "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/play" -H "Content-Type: application/json" -d "{\"game_id\":id, \"token\":\"token\", \"card_id\":id}"
```

### 7. Get Game Status

Returns the current state of the game and the list of players

**Endpoint:** `/status/game`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/status/game?game_id=id"
```

## Deadlock / Game-Over Handling

* The game ends automatically when all cards in the deck and players’ hands are exhausted.
* Since Kseri allows all moves to be played, no invalid moves exist, and no deadlock detection is required.
* The API enforces the end-of-game check after each move, and the winner is calculated automatically.

## Database tables

### 1. Cards
Contains all available cards for the game
* **id**: Unique card id - PK
* **suit**: Card symbol (hearts, diamonds, clubs, spades)
* **rank**: Card rank (2-10, J, Q, K, A)

### 2. Players
Gives information for the players
* **id**: Unique player id - PK
* **username**: Player's name
* **score**: Current player's score
* **token**: Unique token to authenticate
* **game_id**: The game this player joined (FK)
* **last_action**: Timestamp of the last action the player did

### 3. Board
The board that holds information about the game
* **game_id**: The game that takes place
* **card_id**: The card the is being moved
* **location**: Card location (deck, hand, table, discard)
* **owner**: The owner of the card
* **position**: The position of the card in the stack

### 4. Game
A general status for the game
* **id**: Unique game id (PK).
* **status**: Game status (initialized, started, ended)
* **current_player_id**: The current player's turn
* **winner_id**: Winner's id
* **last_change**: Timestamp of the last change






