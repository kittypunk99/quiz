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
VALUES ('Mathematik', 1),#2
       ('Geometrie', 2),#3
       ('Algebra', 2),#4
       ('Geschichte', 1),#5
       ('Antike', 5),#6
       ('Neuzeit', 5),#7
       ('Informatik', 1),#8
       ('Programmierung', 8),#9
       ('Sicherheit', 8),#10
       ('Biologie', 1),#11
       ('Genetik', 11);#12
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
-- Zusätzliche Fragen für Kategorie 2 (Geometrie)
INSERT INTO question (category_id, text)
VALUES (3, 'Wie viele Ecken hat ein Würfel?'),                 -- q8
       (3, 'Was ist ein rechter Winkel?'),                     -- q9
       (3, 'Wie viele Grade hat ein Kreis?'),                  -- q10
       (3, 'Wie viele Seiten hat ein regelmäßiges Sechseck?'), -- q11
       (3, 'Was ist ein Prisma?');
-- q12

-- Antworten Geometrie
INSERT INTO answer (question_id, text, is_correct)
VALUES (8, '8', 1),
       (8, '6', 0),
       (8, '4', 0),
       (8, '10', 0),
       (9, '90°', 1),
       (9, '180°', 0),
       (9, '60°', 0),
       (9, 'Ein rechter Winkel ist 90°', 1),
       (10, '360°', 1),
       (10, '180°', 0),
       (10, '720°', 0),
       (10, '400°', 0),
       (11, '6', 1),
       (11, '5', 0),
       (11, '8', 0),
       (11, '7', 0),
       (12, 'Körper mit parallelen Flächen', 1),
       (12, 'Eine Pflanze', 0),
       (12, 'Ein Tier', 0),
       (12, 'Geometrische Figur', 1);

-- Zusatzfragen für 4 (Algebra)
INSERT INTO question (category_id, text)
VALUES (4, 'Was ist die Wurzel aus 16?'),                      -- q13
       (4, 'Löse x: 2x = 10'),                                 -- q14
       (4, 'Wie lautet die binomische Formel a² + 2ab + b²?'), -- q15
       (4, 'Was ergibt (x+1)(x+1)?'),                          -- q16
       (4, 'Wie lautet 3² + 4²?'); -- q17

INSERT INTO answer (question_id, text, is_correct)
VALUES (13, '4', 1),
       (13, '5', 0),
       (13, '-4', 1),
       (13, '8', 0),
       (14, 'x=5', 1),
       (14, 'x=10', 0),
       (14, 'x=2', 0),
       (14, 'x=4', 0),
       (15, '(a+b)²', 1),
       (15, 'a² + b²', 0),
       (15, 'a² - b²', 0),
       (15, '2ab', 0),
       (16, 'x² + 2x + 1', 1),
       (16, 'x² + 1', 0),
       (16, 'x² + x + 1', 0),
       (16, '2x+1', 0),
       (17, '25', 1),
       (17, '12', 0),
       (17, '16', 0),
       (17, '9', 0);

-- Zusatzfragen für 7 (Informatik)
INSERT INTO question (category_id, text)
VALUES (8, 'Was bedeutet CPU?'),           -- q18
       (8, 'Wofür steht HTML?'),           -- q19
       (8, 'Was ist ein Betriebssystem?'), -- q20
       (8, 'Was ist RAM?'),                -- q21
       (8, 'Was ist ein Compiler?'); -- q22

INSERT INTO answer (question_id, text, is_correct)
VALUES (18, 'Central Processing Unit', 1),
       (18, 'Central Program Unit', 0),
       (18, 'Main Memory', 0),
       (18, 'Prozessor', 1),
       (19, 'HyperText Markup Language', 1),
       (19, 'HighText Main Language', 0),
       (19, 'Markup Language', 1),
       (19, 'Textcode', 0),
       (20, 'Software zur Steuerung des Computers', 1),
       (20, 'Browser', 0),
       (20, 'Textverarbeitung', 0),
       (20, 'Hardware', 0),
       (21, 'Arbeitsspeicher', 1),
       (21, 'Langzeitspeicher', 0),
       (21, 'ROM', 0),
       (21, 'Random Access Memory', 1),
       (22, 'Übersetzt Code in Maschinensprache', 1),
       (22, 'Zeichnet Videos', 0),
       (22, 'Sendet E-Mails', 0),
       (22, 'Texteditor', 0);

-- Zusatzfragen für 1 (Mathematik, als übergeordnete Kategorie)
INSERT INTO question (category_id, text)
VALUES (2, 'Was ist 5 * 6?'),            -- q23
       (2, 'Wie lautet Pi (gerundet)?'), -- q24
       (2, 'Was ist 100 / 4?'),          -- q25
       (2, 'Was ergibt 2³?'),            -- q26
       (2, 'Was ist 9²?'); -- q27

INSERT INTO answer (question_id, text, is_correct)
VALUES (23, '30', 1),
       (23, '25', 0),
       (23, '20', 0),
       (23, '35', 0),
       (24, '3.14', 1),
       (24, '4.13', 0),
       (24, '2.17', 0),
       (24, '3.1415', 1),
       (25, '25', 1),
       (25, '40', 0),
       (25, '20', 0),
       (25, '30', 0),
       (26, '8', 1),
       (26, '6', 0),
       (26, '4', 0),
       (26, '9', 0),
       (27, '81', 1),
       (27, '72', 0),
       (27, '64', 0),
       (27, '91', 0);
-- Zusatzfragen für Kategorie 5 (Antike)
INSERT INTO question (category_id, text)
VALUES (6, 'Welcher Krieg endete 146 v. Chr.?'),      -- q28
       (6, 'Welches Bauwerk stammt aus der Antike?'), -- q29
       (6, 'Wer war Sokrates?'),                      -- q30
       (6, 'Was war das Römische Reich?'),            -- q31
       (6, 'Was ist die Akropolis?'); -- q32

INSERT INTO answer (question_id, text, is_correct)
VALUES (28, 'Dritter Punischer Krieg', 1),
       (28, 'Trojanischer Krieg', 0),
       (28, 'Hundertjähriger Krieg', 0),
       (28, 'Zweiter Weltkrieg', 0),
       (29, 'Kolosseum', 1),
       (29, 'Eiffelturm', 0),
       (29, 'Big Ben', 0),
       (29, 'Pyramiden von Gizeh', 1),
       (30, 'Griechischer Philosoph', 1),
       (30, 'Kaiser', 0),
       (30, 'Schriftsteller', 0),
       (30, 'Künstler', 0),
       (31, 'Antikes Großreich in Europa', 1),
       (31, 'Kleinstaat', 0),
       (31, 'Moderne Demokratie', 0),
       (31, 'Philosophenschule', 0),
       (32, 'Tempelanlage in Athen', 1),
       (32, 'Statue', 0),
       (32, 'Schriftrolle', 0),
       (32, 'Buch', 0);

-- Kategorie 6 (Neuzeit)
INSERT INTO question (category_id, text)
VALUES (7, 'Wann war die Französische Revolution?'),   -- q33
       (7, 'Wer war Napoleon Bonaparte?'),             -- q34
       (7, 'Was geschah 1989 in Deutschland?'),        -- q35
       (7, 'Wann war der erste Mensch auf dem Mond?'), -- q36
       (7, 'Was war die industrielle Revolution?'); -- q37

INSERT INTO answer (question_id, text, is_correct)
VALUES (33, '1789', 1),
       (33, '1815', 0),
       (33, '1914', 0),
       (33, '1945', 0),
       (34, 'Französischer Kaiser', 1),
       (34, 'Deutscher König', 0),
       (34, 'US-Präsident', 0),
       (34, 'Diktator', 0),
       (35, 'Mauerfall', 1),
       (35, 'Einführung des Euro', 0),
       (35, 'Wiedervereinigung', 1),
       (35, 'Olympia', 0),
       (36, '1969', 1),
       (36, '1979', 0),
       (36, '1955', 0),
       (36, '1945', 0),
       (37, 'Technologischer Wandel im 18./19. Jh.', 1),
       (37, 'Kalter Krieg', 0),
       (37, 'Kolonialismus', 0),
       (37, 'Computerzeitalter', 0);

-- Kategorie 10 (Sicherheit)
INSERT INTO question (category_id, text)
VALUES (10, 'Was ist 2FA?'),                     -- q38
       (10, 'Was macht ein Antivirenprogramm?'), -- q39
       (10, 'Was ist Phishing?'),                -- q40
       (10, 'Wofür steht HTTPS?'),               -- q41
       (10, 'Was ist ein sicheres WLAN-Passwort?'); -- q42

INSERT INTO answer (question_id, text, is_correct)
VALUES (38, 'Zwei-Faktor-Authentifizierung', 1),
       (38, 'Datenschutzgesetz', 0),
       (38, 'Backup-Methode', 0),
       (38, 'Sicherheitsupdate', 0),
       (39, 'Findet Schadsoftware', 1),
       (39, 'Sperrt Internet', 0),
       (39, 'Löscht E-Mails', 0),
       (39, 'Prüft Programme', 1),
       (40, 'Fälschung von Nachrichten zur Täuschung', 1),
       (40, 'Spionage-Software', 0),
       (40, 'Datenbackup', 0),
       (40, 'Netzwerkangriff', 0),
       (41, 'HyperText Transfer Protocol Secure', 1),
       (41, 'Sichere Internetverbindung', 1),
       (41, 'Virusabwehr', 0),
       (41, 'Zahlungsmethode', 0),
       (42, 'Langes Passwort mit Zahlen, Sonderzeichen', 1),
       (42, '12345678', 0),
       (42, 'abc123', 0),
       (42, 'Sicheres-Passwort!2024', 1);

-- Kategorie 9 (Programmierung)
INSERT INTO question (category_id, text)
VALUES (9, 'Was ist eine Variable?'), -- q43
       (9, 'Was macht ein Loop?'),    -- q44
       (9, 'Was bedeutet "if"?'),     -- q45
       (9, 'Was ist eine Funktion?'), -- q46
       (9, 'Was bedeutet Debugging?'); -- q47

INSERT INTO answer (question_id, text, is_correct)
VALUES (43, 'Speichert einen Wert', 1),
       (43, 'Programmschleife', 0),
       (43, 'Compiler', 0),
       (43, 'Zwischenspeicher', 1),
       (44, 'Wiederholt Code', 1),
       (44, 'Beendet Code', 0),
       (44, 'Lädt Bibliotheken', 0),
       (44, 'Springt zum Anfang', 0),
       (45, 'Prüft eine Bedingung', 1),
       (45, 'Startet das Programm', 0),
       (45, 'Schreibt etwas aus', 0),
       (45, 'Beendet Programm', 0),
       (46, 'Ein Codeblock mit Parameter(n)', 1),
       (46, 'Ein Datentyp', 0),
       (46, 'Eine Schleife', 0),
       (46, 'Ein Objekt', 0),
       (47, 'Fehlersuche', 1),
       (47, 'Code kompilieren', 0),
       (47, 'Datei löschen', 0),
       (47, 'Code speichern', 0);

-- Kategorie 12 (Genetik)
INSERT INTO question (category_id, text)
VALUES (12, 'Was ist ein Gen?'),      -- q48
       (12, 'Was sind Chromosomen?'), -- q49
       (12, 'Was macht DNA?'),        -- q50
       (12, 'Was ist RNA?'),          -- q51
       (12, 'Was ist ein Allel?'); -- q52

INSERT INTO answer (question_id, text, is_correct)
VALUES (48, 'Erbanlage', 1),
       (48, 'Eiweiß', 0),
       (48, 'Zelle', 0),
       (48, 'Vererbbares Segment', 1),
       (49, 'Strukturierte DNA-Pakete', 1),
       (49, 'Proteine', 0),
       (49, 'Blutzellen', 0),
       (49, 'Mitochondrien', 0),
       (50, 'Speichert genetische Information', 1),
       (50, 'Blutbestandteil', 0),
       (50, 'Kohlenhydrat', 0),
       (50, 'Strukturprotein', 0),
       (51, 'Überträgt genetische Info zur Proteinbildung', 1),
       (51, 'Ein Zelltyp', 0),
       (51, 'DNA-Typ', 0),
       (51, 'Fettmolekül', 0),
       (52, 'Varianten eines Gens', 1),
       (52, 'Virus', 0),
       (52, 'Ein Bakterium', 0),
       (52, 'Zellkern', 0);

-- Kategorie 11 (Biologie)
INSERT INTO question (category_id, text)
VALUES (11, 'Was ist eine Zelle?'), -- q53
       (11, 'Was macht das Herz?'), -- q54
       (11, 'Was ist ein Enzym?'),  -- q55
       (11, 'Was ist Osmose?'),     -- q56
       (11, 'Was ist ein Nervensystem?'); -- q57

INSERT INTO answer (question_id, text, is_correct)
VALUES (53, 'Grundbaustein des Lebens', 1),
       (53, 'Atom', 0),
       (53, 'Eiweiß', 0),
       (53, 'Gewebeart', 0),
       (54, 'Pumpt Blut', 1),
       (54, 'Verdaut Nahrung', 0),
       (54, 'Filtert Luft', 0),
       (54, 'Kontrolliert Gehirn', 0),
       (55, 'Biologischer Katalysator', 1),
       (55, 'Zellorganell', 0),
       (55, 'Vitamin', 0),
       (55, 'Hormon', 0),
       (56, 'Wasserbewegung durch Membran', 1),
       (56, 'Lichtaufnahme', 0),
       (56, 'Muskelbewegung', 0),
       (56, 'Atmung', 0),
       (57, 'Steuerung von Reizen', 1),
       (57, 'Knochenaufbau', 0),
       (57, 'Blutzirkulation', 0),
       (57, 'Zellteilung', 0);

-- Kategorie 4 (Geschichte, Überkategorie)
INSERT INTO question (category_id, text)
VALUES (5, 'Was bedeutet Renaissance?'), -- q58
       (5, 'Was ist eine Monarchie?'),   -- q59
       (5, 'Was war der Kalte Krieg?'),  -- q60
       (5, 'Wer war Karl der Große?'),   -- q61
       (5, 'Was ist die Aufklärung?'); -- q62

INSERT INTO answer (question_id, text, is_correct)
VALUES (58, 'Wiedergeburt der Antike', 1),
       (58, 'Revolution', 0),
       (58, 'Krieg', 0),
       (58, 'Eroberung', 0),
       (59, 'Staatsform mit König', 1),
       (59, 'Demokratie', 0),
       (59, 'Diktatur', 0),
       (59, 'Theokratie', 0),
       (60, 'Konflikt zwischen Ost & West', 1),
       (60, 'Atomkrieg', 0),
       (60, 'Weltkrieg', 0),
       (60, 'Revolution', 0),
       (61, 'Fränkischer Kaiser', 1),
       (61, 'Römischer Diktator', 0),
       (61, 'Englischer König', 0),
       (61, 'Philosoph', 0),
       (62, 'Bewegung für Vernunft & Wissenschaft', 1),
       (62, 'Glaubenskrieg', 0),
(62, 'Kunststil', 0),
       (62, 'Krieg', 0);
