CREATE TABLE IF NOT EXISTS users -- Users of the application (e.g. readers)
(
    id                 INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    email              VARCHAR(255) UNIQUE,                                       -- Email address of the user (used for notifications/lost password/etc)
    username           VARCHAR(255) NOT NULL UNIQUE,                              -- Username of the user (used for login and display)
    password           TEXT,                                                      -- Password of the user (hashed with bcrypt or plain text for temporary passwords)
    state              VARCHAR(50) NOT NULL                                       -- State of the user (e.g. active, inactive, blocked)
                       CHECK (state IN ('active', 'inactive', 'blocked')),
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),       -- Date and time when the user was created
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))        -- Date and time when the user was last updated
);
