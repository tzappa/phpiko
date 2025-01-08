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
	-- FOREIGN KEY(ref_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL -- if there is a table `users` with a primary key `id`
);
