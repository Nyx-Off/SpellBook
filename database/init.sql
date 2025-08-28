-- database/init.sql
-- Script d'initialisation pour la base de données Spellbook

CREATE TABLE IF NOT EXISTS spells (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    color VARCHAR(7) DEFAULT '#8B4513',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#654321',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS spell_tags (
    spell_id INT,
    tag_id INT,
    PRIMARY KEY(spell_id, tag_id),
    FOREIGN KEY (spell_id) REFERENCES spells(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Insertion de quelques tags par défaut
INSERT INTO tags (name, color) VALUES 
('Feu', '#FF4500'),
('Eau', '#1E90FF'),
('Terre', '#8B4513'),
('Air', '#87CEEB'),
('Lumière', '#FFD700'),
('Ténèbres', '#4B0082'),
('Guérison', '#32CD32'),
('Illusion', '#9370DB'),
('Invocation', '#DC143C'),
('Enchantement', '#FF69B4');

-- Insertion de quelques sorts d'exemple
INSERT INTO spells (name, description, image_url, color) VALUES 
('Boule de Feu', 'Lance une sphère enflammée qui explose au contact', NULL, '#FF4500'),
('Guérison Mineure', 'Restaure une petite quantité de points de vie', NULL, '#32CD32'),
('Invisibilité', 'Rend la cible invisible pendant quelques minutes', NULL, '#9370DB');

-- Liaison des sorts avec leurs tags
INSERT INTO spell_tags (spell_id, tag_id) VALUES 
(1, 1), -- Boule de Feu - Feu
(2, 7), -- Guérison Mineure - Guérison  
(3, 8); -- Invisibilité - Illusion