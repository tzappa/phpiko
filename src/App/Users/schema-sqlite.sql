CREATE TABLE IF NOT EXISTS users -- Users of the application
(
    id                 INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    email              VARCHAR(255) UNIQUE,                                       -- Email address of the user (used for notifications/lost password/etc)
    username           VARCHAR(255) NOT NULL UNIQUE,                              -- Username of the user (used for login and display)
    password           TEXT,                                                      -- Password of the user (hashed with bcrypt or plain text for temporary passwords)
    state              VARCHAR(50) NOT NULL                                       -- State of the user (e.g. active, inactive, blocked)
                       CHECK (state IN ('active', 'inactive', 'nologin', 'blocked')),
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),       -- Date and time when the user was created
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))        -- Date and time when the user was last updated
);
-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id                 INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    user_id            INTEGER NOT NULL,                                -- Foreign key to users.id
    token              VARCHAR(255) NOT NULL UNIQUE,                   -- Unique token for password reset
    expires_at         TIMESTAMP NOT NULL,                             -- Expiration date/time of the token
    used               BOOLEAN NOT NULL DEFAULT 0,                     -- Whether the token has been used
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')), -- Creation date/time
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')), -- Last update date/time
    FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- Create index on token for faster lookups
CREATE INDEX IF NOT EXISTS password_reset_tokens_token_index ON password_reset_tokens (token);
