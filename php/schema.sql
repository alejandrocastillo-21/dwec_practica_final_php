-- Base de datos del tablón de anuncios
CREATE DATABASE IF NOT EXISTS practica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE practica;

CREATE TABLE users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(120) NOT NULL UNIQUE,
    -- Aquí se guarda el HASH generado con password_hash(), nunca la contraseña real.
    -- 255 caracteres porque el algoritmo por defecto puede cambiar y producir hashes más largos.
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,        -- autor: base de los permisos por propiedad
    title      VARCHAR(150) NOT NULL,
    category   VARCHAR(50)  NOT NULL,        -- texto libre, así el buscador puede usar LIKE directamente
    body       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- ON DELETE CASCADE: si se borra un usuario, se borran también sus anuncios
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Relación 1:N -> un post tiene muchos comentarios; cada comentario, un solo post
CREATE TABLE comments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Al borrar un post se borran automáticamente todos sus comentarios
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);