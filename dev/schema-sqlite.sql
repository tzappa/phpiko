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

CREATE TABLE acl_permissions
(
	id                 INTEGER NOT NULL PRIMARY KEY,
	object             VARCHAR(64) NOT NULL,
	operation          VARCHAR(64) NOT NULL,
	created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
	updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
	UNIQUE (object, operation)
);

CREATE TABLE acl_roles
(
	id                 INTEGER NOT NULL PRIMARY KEY,
	name               VARCHAR(64) NOT NULL UNIQUE,
	created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
	updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))
);

CREATE TABLE acl_role_permissions
(
	id                 INTEGER NOT NULL PRIMARY KEY,
	role_id            INTEGER NOT NULL,                       -- FK acl_roles.id
	permission_id      INTEGER NOT NULL,                       -- FK acl_permissions.id
	created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
	updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
	FOREIGN KEY(role_id) REFERENCES acl_roles(id) ON UPDATE CASCADE ON DELETE SET NULL,
	FOREIGN KEY(permission_id) REFERENCES acl_permissions(id) ON UPDATE CASCADE ON DELETE SET NULL,
);

CREATE TABLE acl_grants
(
	id                 INTEGER NOT NULL PRIMARY KEY,
	role_id            INTEGER NOT NULL,                       -- FK acl_roles.id
	ref_id             INTEGER NOT NULL,                       -- FK to a referrer table such as users.id
	created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
	updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
	FOREIGN KEY(role_id) REFERENCES acl_roles(id) ON UPDATE CASCADE ON DELETE SET NULL,
	FOREIGN KEY(ref_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL -- if there is a table `users` with a primary key `id`
);

--
-- Test data
--
INSERT INTO users (id, email, username, password, state) VALUES (1, 'admin@phpiko.loc', 'admin', 'admin', 'active');
INSERT INTO acl_permissions (id, object, operation) VALUES (1, 'System', 'info');
INSERT INTO acl_roles (id, name) VALUES (1, 'Admin');
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (1, 1, 1);
INSERT INTO acl_grants (id, role_id, ref_id) VALUES (1, 1, 1);
