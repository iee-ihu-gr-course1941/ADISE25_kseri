# Kseri Card Game API

A RESTful API built with PHP and MariaDB for the "Kseri" card game.
This project was developed as part of the ADISE course and is hosted on the IHU (IEE) servers.

# API Endpoints

# 1. Create Game
Initializes a new game session
**Endpoint:** `/game/create`  
**Method:** POST  

```bash
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/create
```

# 2. Join Game
Registers a player to a specific game. Returns a unique token required for all moves
**Endpoint:** `/player`  
**Method:** POST 

```bash
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/player \
  -H "Content-Type: application/json" \
  -d '{"username":"player_name", "game_id":game_id}'
```

# 3. Start Game
Shuffles deck, Deals cards to each player and to the table
**Endpoint:** `/game/start`  
**Method:** POST

```bash
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/start \
  -H "Content-Type: application/json" \
  -d '{"game_id":game_id, "token":"player_token"}'
```
# 4. Get Player Hand
Returns the current cards by the authenticated player
**Endpoint:** `/game/hand`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/hand?game_id=game_id&token=player_token"
```
# 5. Get Table Cards
Returns all cards currently on the table
**Endpoint:** `/game/table`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/table?game_id=1&token=player_token"
```

# 6. Play Card
Returns all cards currently on the table
**Endpoint:** `/game/play`  
**Method:** POST

```bash 
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/play \
  -H "Content-Type: application/json" \
  -d '{"game_id":game_id, "token":"player_token", "card_id":card_id}'
```

# 7. Get Game Status
Returns the current state of the game and the list of players
**Endpoint:** `/status/game`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/status/game?game_id=game_id"
```

# Database tables

### 1. Cards
Η βιβλιοθήκη όλων των διαθέσιμων φύλλων της τράπουλας.
* **id**: Μοναδικό αναγνωριστικό κάρτας (Primary Key).
* **suit**: Το χρώμα/σύμβολο της κάρτας (hearts, diamonds, clubs, spades).
* **rank**: Η αξία της κάρτας (2-10, J, Q, K, A).

### 2. Players
Οι πληροφορίες των παικτών που συμμετέχουν στο παιχνίδι.
* **id**: Μοναδικό αναγνωριστικό παίκτη (Primary Key).
* **username**: Το όνομα του παίκτη (Unique).
* **score**: Η τρέχουσα βαθμολογία του παίκτη.
* **token**: Μοναδικό αναγνωριστικό για την αυθεντικοποίηση.
* **game_id**: Σύνδεση με το τρέχον παιχνίδι (Foreign Key).
* **last_action**: Χρονοσφραγίδα τελευταίας δραστηριότητας.

### 3. Board
Η καρδιά του παιχνιδιού. Καταγράφει πού βρίσκεται η κάθε κάρτα.
* **game_id**: Το παιχνίδι στο οποίο ανήκει η κίνηση (Composite PRI).
* **card_id**: Η κάρτα που μετακινείται (Composite PRI).
* **location**: Η θέση της κάρτας (deck, hand, table, discard).
* **owner**: Ο παίκτης στον οποίο ανήκει η κάρτα.
* **position**: Η σειρά της κάρτας στη στοίβα.

### 4. Game
Η γενική κατάσταση της κάθε αναμέτρησης.
* **id**: Μοναδικό αναγνωριστικό παιχνιδιού (Primary Key).
* **status**: Η φάση του παιχνιδιού (initialized, started, κλπ).
* **current_player_id**: Το ID του παίκτη που έχει σειρά.
* **winner_id**: Το ID του νικητή.
* **last_change**: Χρονοσφραγίδα τελευταίας αλλαγής status.






