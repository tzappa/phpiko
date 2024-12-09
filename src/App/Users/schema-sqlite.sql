-- DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users -- Users of the application (e.g. readers)
(
    id                 INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    username           VARCHAR(255) NOT NULL UNIQUE,                              -- Username of the user (used for login and display)
    password           VARCHAR(100),                                              -- Password of the user (hashed with bcrypt or plain text for temporary passwords)
    state              VARCHAR(50) NOT NULL DEFAULT 'unknown',                    -- State of the user (e.g. active, inactive, blocked)
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'localtime')), -- Date and time when the user was created
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'localtime'))  -- Date and time when the user was last updated
);
