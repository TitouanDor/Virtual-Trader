 -- Création de la base de données
CREATE DATABASE IF NOT EXISTS virtual_trader;

-- Table des utilisateurs/joueurs
CREATE TABLE IF NOT EXISTS joueur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    mdp VARCHAR(255) NOT NULL,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL,
    argent DECIMAL(10, 2) DEFAULT 10000.00
);

-- Table des actions
CREATE TABLE IF NOT EXISTS actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix DECIMAL(10, 2) NOT NULL,
    dividende DECIMAL(10, 2) DEFAULT 0.00,
    date_dividende TINYINT DEFAULT NULL -- Mois de distribution du dividende (1-12)
);

-- Table des portefeuilles (actions possédées par les joueurs)
CREATE TABLE IF NOT EXISTS portefeuille (
    id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    stock_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    purchase_price DECIMAL(10, 2) NOT NULL,
    purchase_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (player_id) REFERENCES joueur(id) ON DELETE CASCADE,
    FOREIGN KEY (stock_id) REFERENCES actions(id) ON DELETE CASCADE

);

-- Table des abonnements/suivis (followers)
CREATE TABLE IF NOT EXISTS followers (
    follower_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    followed_user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES joueur(id) ON DELETE CASCADE,
    FOREIGN KEY (followed_user_id) REFERENCES joueur(id) ON DELETE CASCADE
);

-- Table de l'historique des prix
CREATE TABLE IF NOT EXISTS historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_id INT NOT NULL,
    player_id INT NOT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    nature VARCHAR(10) NOT NULL,
    game_month INT NOT NULL,
    game_year INT NOT NULL,
    real_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (stock_id) REFERENCES actions(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES joueur(id) ON DELETE CASCADE

);

-- Table de l'état du jeu
CREATE TABLE IF NOT EXISTS game_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    current_month INT NOT NULL DEFAULT 1,
    current_year INT NOT NULL DEFAULT 1,
    last_update DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table du cours du marché
CREATE TABLE IF NOT EXISTS cours_marche (
    stock_id INT NOT NULL,
    game_month INT NOT NULL,
    game_year INT NOT NULL,
    valeur_action DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (stock_id) REFERENCES actions(id) ON DELETE CASCADE
    
);


-- Insertion des données initiales pour l'état du jeu
INSERT INTO game_state (current_month, current_year) VALUES (1, 1);

-- Insertion de quelques actions fictives
INSERT INTO actions (id, nom, description, prix, dividende, date_dividende) VALUES
(0, 'Apple Inc.', 'Entreprise technologique spécialisée dans l''électronique grand public', 150.00, 0.82, 2),
(1, 'Alphabet Inc.', 'Entreprise spécialisée dans les services et produits liés à Internet', 2800.00, 0.00, NULL),
(2, 'Amazon.com Inc.', 'Entreprise de commerce électronique et de services cloud', 3200.00, 0.00, NULL),
(3, 'Microsoft Corporation', 'Entreprise informatique multinationale', 280.00, 0.56, 3),
(4, 'Tesla, Inc.', 'Constructeur automobile de véhicules électriques', 700.00, 0.00, NULL),
(5, 'Meta Platforms, Inc.', 'Entreprise propriétaire de Facebook, Instagram et WhatsApp', 330.00, 0.00, NULL),
(6, 'Netflix, Inc.', 'Service de streaming vidéo', 530.00, 0.00, NULL),
(7, 'The Walt Disney Company', 'Entreprise de médias et de divertissement', 175.00, 0.88, 7),
(8, 'NVIDIA Corporation', 'Concepteur de processeurs graphiques', 220.00, 0.16, 3),
(9, 'JPMorgan Chase & Co.', 'Banque d''investissement et services financiers', 155.00, 1.00, 1);
