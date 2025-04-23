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

CREATE TABLE result
(
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT     NOT NULL,
    question_id INT     NOT NULL,
    answer_id   INT     NOT NULL,
    is_correct  BOOLEAN NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES question (id) ON DELETE CASCADE,
    FOREIGN KEY (answer_id) REFERENCES answer (id) ON DELETE CASCADE
);

CREATE TABLE results
(
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT           NOT NULL,
    correct_answers INT           NOT NULL,
    total_questions INT           NOT NULL,
    percentage      DECIMAL(5, 2) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user (id)
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
VALUES ('admin', '$2y$10$FiqctkmNl0IMFmNdrP.zO.kroSepVvWLm68tscA562sPS/yYVeGVK', true);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (1, 'Hauptkategorie', null);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (2, 'tiere', 1);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (3, 'fische', 2);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (4, 'autos', 1);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (5, 'katzen', 2);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (6, 'haus', 1);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (7, 'pfoten', 5);
INSERT INTO quiz_db.category (id, name, parent_id) VALUES (8, 'man', 2);

INSERT INTO quiz_db.question (id, category_id, text) VALUES (1, 2, 'test-tiere');
INSERT INTO quiz_db.question (id, category_id, text) VALUES (2, 3, 'test-fische?');
INSERT INTO quiz_db.question (id, category_id, text) VALUES (3, 4, 'test-autos');
INSERT INTO quiz_db.question (id, category_id, text) VALUES (4, 3, 'aua');
INSERT INTO quiz_db.question (id, category_id, text) VALUES (5, 2, 'asaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');


INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (1, 1, 'ja', 1);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (2, 1, 'nein', 0);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (3, 2, 'ja', 1);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (4, 2, 'nein', 0);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (5, 3, 'ja', 1);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (6, 3, 'nein', 0);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (7, 4, 'asdf', 1);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (8, 4, 'ghfdgsdfafd', 0);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (9, 5, 'b', 1);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (10, 1, 'c', 0);
INSERT INTO quiz_db.answer (id, question_id, text, is_correct) VALUES (11, 5, 'g', 0);
