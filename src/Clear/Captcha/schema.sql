CREATE TABLE IF NOT EXISTS captcha_used_codes
(
    id                 VARCHAR(128) NOT NULL PRIMARY KEY,
    release_time       TIMESTAMP NOT NULL
);
