CREATE TABLE IF NOT EXISTS captcha_used_codes (
    id                 VARCHAR(128) NOT NULL PRIMARY KEY,
    release_time       TIMESTAMP NOT NULL
);

CREATE TABLE IF NOT EXISTS counters -- multipurpose counter - eg. for posts views, comments, likes, etc.
(
    id                 VARCHAR(255) NOT NULL PRIMARY KEY, -- eg. post_2424_views - combination of the name of the object, ID and the action
    current            INTEGER NOT NULL DEFAULT 0,
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'localtime'))
);

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
