CREATE TABLE mfa (
    id                 SERIAL PRIMARY KEY,
    user_id            INTEGER NOT NULL,
    type               VARCHAR(100) NOT NULL,  -- MFA type - email, sms, totp, IP, etc.
    secret             TEXT NOT NULL,          -- Any secret data for MFA - can be an email address, phone number, secret key, IP address, etc.
    options            TEXT,                   -- Any options for the MFA - can be a JSON structure like {algorithm, digits, time_step, used_codes etc.}
    last_step          INTEGER,                -- The step of the MFA - 1, 2, 3, etc. for HOTP, or the time in Unix time(UTC zone) divided by time step.int floor(time / time_step)
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, -- Last time the MFA was used (on each use there is an update of the last_step)
    revoked_at         TIMESTAMP               -- NULL if not revoked, otherwise the time the MFA was revoked
);

CREATE TABLE mfa_codes (
    id                 SERIAL PRIMARY KEY,
    user_id            INTEGER NOT NULL,
    mfa_id             INTEGER NOT NULL,
    token              TEXT NOT NULL,          -- secret (stored on the user's device, on a cookie for example)
    code               VARCHAR(30),            -- the code that is sent to the user via email, SMS, etc.
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at         TIMESTAMP NOT NULL,     -- when the code expires
    used_at            TIMESTAMP,              -- when the code is entered (used)
    active_until       TIMESTAMP NOT NULL,     -- when the secret (stored on the user's device) expires
    deactivated_at     TIMESTAMP NULL,         -- when the session is deactivated
    ip                 VARCHAR(30) NULL,       -- the IP address of the user who requested the code
    user_agent         VARCHAR(255) NULL       -- the user agent of the user who requested the code
);
