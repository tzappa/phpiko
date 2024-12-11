CREATE TABLE IF NOT EXISTS users
(
    id                 INTEGER NOT NULL SERIAL,
    email              VARCHAR(255) UNIQUE,
    username           VARCHAR(255) NOT NULL UNIQUE,
	password           TEXT,
	state              VARCHAR(50) NOT NULL
                       CHECK (state IN ('active', 'inactive', 'blocked')),
	created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);