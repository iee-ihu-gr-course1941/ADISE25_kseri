# Card Game API

A simple REST API for a two-player card game.  
The API allows players to create a game, join it, start it, and view cards.

# Commands for the game

## 1. Create Game

**Endpoint:** `/game/create`  
**Method:** POST  

```bash
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/create
```

## 2. Join Game (Player)

**Endpoint:** `/player`  
**Method:** POST  

```bash
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/player \
  -H "Content-Type: application/json" \
  -d '{"username":"player_name","game_id":game_id}'
```
## 3. Start Game

**Endpoint:** `/game/start`  
**Method:** POST

```bash
curl -X POST https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/start \
  -H "Content-Type: application/json" \
  -d '{"game_id":game_id,"token":"player_token"}'
```
## 4. Get Player Hand

**Endpoint:** `/game/hand`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/hand?game_id=game_id&token=player_token"
```
## 4. Get Table Cards

**Endpoint:** `/game/table`  
**Method:** GET

```bash
curl "https://users.iee.ihu.gr/~it185328/ADISE25_kseri/game.php/game/table?game_id=1&token=player_token"
```





