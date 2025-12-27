-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 27 Δεκ 2025 στις 13:51:56
-- Έκδοση διακομιστή: 10.4.32-MariaDB
-- Έκδοση PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `adise25_db`
--

DELIMITER $$
--
-- Διαδικασίες
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CLEAN_BOARD` (IN `p_game_id` INT)   BEGIN
    UPDATE board
    SET
        location = 'deck',
        owner = NULL,
        position = NULL
    WHERE game_id = p_game_id;

    UPDATE players
    SET score = 0
    WHERE game_id = p_game_id;

    UPDATE game
    SET
        current_player_id = NULL,
        winner_id = NULL,
        status = 'initialized'
    WHERE id = p_game_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `board`
--

CREATE TABLE `board` (
  `game_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `location` enum('deck','hand','table','discard') NOT NULL,
  `owner` varchar(20) DEFAULT NULL,
  `position` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `board`
--

INSERT INTO `board` (`game_id`, `card_id`, `location`, `owner`, `position`) VALUES
(5, 1, 'deck', NULL, 36),
(5, 2, 'discard', 'test', 6),
(5, 3, 'discard', 'test', 1),
(5, 4, 'deck', NULL, 22),
(5, 5, 'deck', NULL, 25),
(5, 6, 'discard', 'test', 4),
(5, 7, 'discard', 'test', 1),
(5, 8, 'deck', NULL, 30),
(5, 9, 'deck', NULL, 40),
(5, 10, 'discard', 'test', 2),
(5, 11, 'deck', NULL, 23),
(5, 12, 'deck', NULL, 20),
(5, 13, 'deck', NULL, 28),
(5, 14, 'deck', NULL, 38),
(5, 15, 'discard', 'test', 5),
(5, 16, 'deck', NULL, 46),
(5, 17, 'discard', 'test', 3),
(5, 18, 'deck', NULL, 32),
(5, 19, 'deck', NULL, 27),
(5, 20, 'deck', NULL, 45),
(5, 21, 'deck', NULL, 29),
(5, 22, 'deck', NULL, 17),
(5, 23, 'deck', NULL, 49),
(5, 24, 'deck', NULL, 41),
(5, 25, 'deck', NULL, 50),
(5, 26, 'deck', NULL, 26),
(5, 27, 'deck', NULL, 35),
(5, 28, 'deck', NULL, 51),
(5, 29, 'deck', NULL, 39),
(5, 30, 'deck', NULL, 44),
(5, 31, 'deck', NULL, 52),
(5, 32, 'deck', NULL, 19),
(5, 33, 'deck', NULL, 43),
(5, 34, 'hand', 'test2', NULL),
(5, 35, 'discard', 'test', 2),
(5, 36, 'deck', NULL, 37),
(5, 37, 'hand', 'test', NULL),
(5, 38, 'deck', NULL, 42),
(5, 39, 'deck', NULL, 47),
(5, 40, 'discard', 'test', 1),
(5, 41, 'deck', NULL, 18),
(5, 42, 'hand', 'test', NULL),
(5, 43, 'deck', NULL, 21),
(5, 44, 'deck', NULL, 33),
(5, 45, 'hand', 'test2', NULL),
(5, 46, 'hand', 'test', NULL),
(5, 47, 'deck', NULL, 31),
(5, 48, 'hand', 'test', NULL),
(5, 49, 'hand', 'test2', NULL),
(5, 50, 'deck', NULL, 34),
(5, 51, 'hand', 'test', NULL),
(5, 52, 'hand', 'test2', NULL);

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `suit` enum('hearts','diamonds','clubs','spades') NOT NULL,
  `rank` enum('2','3','4','5','6','7','8','9','10','J','Q','K','A') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `cards`
--

INSERT INTO `cards` (`id`, `suit`, `rank`) VALUES
(1, 'hearts', '2'),
(2, 'hearts', '3'),
(3, 'hearts', '4'),
(4, 'hearts', '5'),
(5, 'hearts', '6'),
(6, 'hearts', '7'),
(7, 'hearts', '8'),
(8, 'hearts', '9'),
(9, 'hearts', '10'),
(10, 'hearts', 'J'),
(11, 'hearts', 'Q'),
(12, 'hearts', 'K'),
(13, 'hearts', 'A'),
(14, 'diamonds', '2'),
(15, 'diamonds', '3'),
(16, 'diamonds', '4'),
(17, 'diamonds', '5'),
(18, 'diamonds', '6'),
(19, 'diamonds', '7'),
(20, 'diamonds', '8'),
(21, 'diamonds', '9'),
(22, 'diamonds', '10'),
(23, 'diamonds', 'J'),
(24, 'diamonds', 'Q'),
(25, 'diamonds', 'K'),
(26, 'diamonds', 'A'),
(27, 'clubs', '2'),
(28, 'clubs', '3'),
(29, 'clubs', '4'),
(30, 'clubs', '5'),
(31, 'clubs', '6'),
(32, 'clubs', '7'),
(33, 'clubs', '8'),
(34, 'clubs', '9'),
(35, 'clubs', '10'),
(36, 'clubs', 'J'),
(37, 'clubs', 'Q'),
(38, 'clubs', 'K'),
(39, 'clubs', 'A'),
(40, 'spades', '2'),
(41, 'spades', '3'),
(42, 'spades', '4'),
(43, 'spades', '5'),
(44, 'spades', '6'),
(45, 'spades', '7'),
(46, 'spades', '8'),
(47, 'spades', '9'),
(48, 'spades', '10'),
(49, 'spades', 'J'),
(50, 'spades', 'Q'),
(51, 'spades', 'K'),
(52, 'spades', 'A');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `game`
--

CREATE TABLE `game` (
  `id` int(11) NOT NULL,
  `status` enum('initialized','started','ended','aborted') NOT NULL DEFAULT 'initialized',
  `current_player_id` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `last_change` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `game`
--

INSERT INTO `game` (`id`, `status`, `current_player_id`, `winner_id`, `last_change`) VALUES
(5, 'started', 4, NULL, '2025-12-24 11:01:45');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `players`
--

CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `token` varchar(255) NOT NULL,
  `last_action` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `game_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `players`
--

INSERT INTO `players` (`id`, `username`, `score`, `token`, `last_action`, `game_id`) VALUES
(3, 'test', 0, 'f22e8136b6e6aea40550c0325852a389', '2025-12-22 16:24:27', 5),
(4, 'test2', 0, '1d00c213ced8425c97fdac9ac7d8486e', '2025-12-22 16:25:01', 5);

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `board`
--
ALTER TABLE `board`
  ADD PRIMARY KEY (`game_id`,`card_id`),
  ADD KEY `card_id` (`card_id`),
  ADD KEY `owner` (`owner`);

--
-- Ευρετήρια για πίνακα `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_card` (`suit`,`rank`);

--
-- Ευρετήρια για πίνακα `game`
--
ALTER TABLE `game`
  ADD PRIMARY KEY (`id`),
  ADD KEY `current_player_id` (`current_player_id`),
  ADD KEY `winner_id` (`winner_id`);

--
-- Ευρετήρια για πίνακα `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `game_id` (`game_id`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT για πίνακα `game`
--
ALTER TABLE `game`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT για πίνακα `players`
--
ALTER TABLE `players`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `board`
--
ALTER TABLE `board`
  ADD CONSTRAINT `board_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`),
  ADD CONSTRAINT `board_ibfk_2` FOREIGN KEY (`card_id`) REFERENCES `cards` (`id`),
  ADD CONSTRAINT `board_ibfk_3` FOREIGN KEY (`owner`) REFERENCES `players` (`username`);

--
-- Περιορισμοί για πίνακα `game`
--
ALTER TABLE `game`
  ADD CONSTRAINT `game_ibfk_1` FOREIGN KEY (`current_player_id`) REFERENCES `players` (`id`),
  ADD CONSTRAINT `game_ibfk_2` FOREIGN KEY (`winner_id`) REFERENCES `players` (`id`);

--
-- Περιορισμοί για πίνακα `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `game` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
