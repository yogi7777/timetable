-- Tabelle für User
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id)
);

-- Tabelle für Abteilungen/Kostenstellen
CREATE TABLE departments (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    cost_center VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
);

-- Tabelle für Timer-Daten
CREATE TABLE timers (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    duration INT DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE user_settings (
    user_id INT PRIMARY KEY,
    button_order JSON NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
INSERT INTO users (username, password, is_admin) VALUES ('admin', '$2y$10$rECL1.HAWawvqMU/x/WDouCezduDkAyrvIRHE2P0XqeJHHIcAHCja', 1);