CREATE TABLE IF NOT EXISTS users
(
    id                 SERIAL NOT NULL PRIMARY KEY,
    email              VARCHAR(255) UNIQUE,
    username           VARCHAR(255) NOT NULL UNIQUE,
	password           TEXT,
	state              VARCHAR(50) NOT NULL
                       CHECK (state IN ('active', 'inactive', 'nologin', 'blocked')),
	created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens
(
    id                 SERIAL NOT NULL PRIMARY KEY,
    user_id            INTEGER NOT NULL,                                          -- Foreign key to users.id
    token              VARCHAR(255) NOT NULL UNIQUE,                              -- Unique token for password reset
    expires_at         TIMESTAMP NOT NULL,                                        -- Expiration date/time of the token
    used               BOOLEAN NOT NULL DEFAULT FALSE,                            -- Whether the token has been used
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,              -- Creation date/time
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,              -- Last update date/time
    FOREIGN KEY(user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
);
-- Create index on token for faster lookups
CREATE INDEX password_reset_tokens_token_index ON password_reset_tokens(token);

-- Email verification tokens table
CREATE TABLE IF NOT EXISTS email_verification_tokens
(
    id                 SERIAL NOT NULL PRIMARY KEY,
    email              VARCHAR(255) NOT NULL,                                     -- Email address to verify
    token              VARCHAR(255) NOT NULL UNIQUE,                              -- Unique token for email verification
    expires_at         TIMESTAMP NOT NULL,                                        -- Expiration date/time of the token
    used               BOOLEAN NOT NULL DEFAULT FALSE,                            -- Whether the token has been used
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,              -- Creation date/time
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP               -- Last update date/time
);
-- Create index on token for faster lookups
CREATE INDEX email_verification_tokens_token_index ON email_verification_tokens(token);
-- Create index on email for faster lookups
CREATE INDEX email_verification_tokens_email_index ON email_verification_tokens(email);
