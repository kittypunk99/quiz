DROP DATABASE IF EXISTS quiz_db;
CREATE DATABASE quiz_db CHARACTER SET UTF8MB4 COLLATE utf8mb4_unicode_ci;
USE quiz_db;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
CREATE TABLE user
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    is_admin   BOOLEAN   DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE category
(
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(255) NOT NULL UNIQUE,
    parent_id INT DEFAULT NULL,
    FOREIGN KEY (parent_id) REFERENCES category (id) ON DELETE SET NULL
);
CREATE TABLE question
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT  NOT NULL,
    text        TEXT NOT NULL,
    FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE
);
CREATE TABLE answer
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT     NOT NULL,
    text        TEXT    NOT NULL,
    is_correct  BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE
);
CREATE TABLE leaderboard
(
    user_id         INT PRIMARY KEY,
    correct_answers INT           NOT NULL,
    total_questions INT           NOT NULL,
    percentage      DECIMAL(5, 2) NOT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
);
DELIMITER $$
CREATE TRIGGER prevent_delete_main_category
    BEFORE DELETE
    ON category
    FOR EACH ROW
BEGIN
    IF OLD.parent_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hauptkategorie kann nicht gelöscht werden';
    END IF;
END$$
CREATE TRIGGER prevent_questions_in_main_category
    BEFORE insert
    on question
    FOR EACH ROW
BEGIN
    IF NEW.category_id = 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fragen können nicht in der Hauptkategorie erstellt werden';
    END IF;
end$$
DELIMITER ;
INSERT INTO user (username, password, is_admin)
VALUES ('admin', '$2y$10$FiqctkmNl0IMFmNdrP.zO.kroSepVvWLm68tscA562sPS/yYVeGVK', true),
       ('alice', '$2y$10$eBnmuoMIxvPb2MRNzAK9WuQhKAXzvsmFE5AyoTLduFMjAQk.ZTY4S', false), -- pass: alice123
       ('bob', '$2y$10$If.4XpCGykI4akIu8DafWuB13X4Tpp0zq.MqsNiQydz1YEVNYaYx2', false),   -- pass: bobpass
       ('carol', '$2y$10$ptqMtCPuF3ANXMynTt3RVuBgh1Z39K5h1wlWfXumw3uEVu6Z5llS6', false), -- pass: carol!
       ('dave', '$2y$10$JmXKgmSKvF13TbSV7T/NiufJFX/27zjW.f8FbhzIXKFV6fOcPPlDO', false),  -- pass: dave1234
       ('eve', '$2y$10$UCdOdY6HQfn/64Rv9pXYEutMsD5K4eMNbcqKwlQ7lFJkGjzySRs7K', false); -- pass: 123eve
INSERT INTO quiz_db.category (id, name, parent_id)
VALUES (1, 'Hauptkategorie', null);
INSERT INTO category (name, parent_id)
VALUES ('Mathematik', 1),
       ('Geometrie', 2),
       ('Algebra', 2),
       ('Geschichte', 1),
       ('Antike', 5),
       ('Neuzeit', 5),
       ('Informatik', 1),
       ('Programmierung', 8),
       ('Sicherheit', 8),
       ('Biologie', 1),
       ('Genetik', 12);
INSERT INTO question (category_id, text)
VALUES (4, 'Was ist das Ergebnis von 3 + 4 * 2?'),        -- Algebra
       (6, 'Wer war der erste römische Kaiser?'),         -- Antike
       (7, 'Wann begann der Zweite Weltkrieg?'),          -- Neuzeit
       (9, 'Was gibt printf("Hello, World!") in C aus?'), -- Programmierung
       (10, 'Was ist ein sicheres Passwort?'),            -- Sicherheit
       (12, 'Welche Basenpaare gibt es in der DNA?'),     -- Genetik
       (11, 'Was ist Photosynthese?'); -- Biologie
INSERT INTO answer (question_id, text, is_correct)
VALUES (1, '11', 1),
       (1, '14', 0),
       (1, '10', 0),
       (1, '7', 1),
       (2, 'Julius Caesar', 0),
       (2, 'Augustus', 1),
       (2, 'Nero', 0),
       (2, 'Trajan', 0),
       (3, '1939', 1),
       (3, '1945', 0),
       (3, '1914', 0),
       (3, '1923', 0),
       (4, 'Hello, World!', 1),
       (4, 'hello world', 0),
       (4, 'Fehler', 0),
       (4, 'Hello World', 1),
       (5, '1234', 0),
       (5, 'Langes Passwort mit Sonderzeichen', 1),
       (5, 'password123', 0),
       (5, 'Sicher!2024', 1),
       (6, 'A-T und G-C', 1),
       (6, 'A-G und T-C', 0),
       (6, 'T-A und C-G', 1),
       (6, 'U-G und A-T', 0),
       (7, 'Zucker zu Energie und Sauerstoff', 1),
       (7, 'Sauerstoff zu Zucker', 0),
       (7, 'Sonne zu Nahrung', 1),
       (7, 'Zucker aus Blut', 0);
