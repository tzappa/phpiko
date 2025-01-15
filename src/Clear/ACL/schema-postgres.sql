CREATE TABLE acl_permissions
(
	id                 SERIAL NOT NULL PRIMARY KEY,
	object             VARCHAR(64) NOT NULL,
	operation          VARCHAR(64) NOT NULL,
	created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE (object, operation)
);


CREATE TABLE acl_roles
(
	id                 SERIAL NOT NULL PRIMARY KEY,
	name               VARCHAR(64) NOT NULL UNIQUE,
	created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE acl_role_permissions
(
	id                 SERIAL NOT NULL PRIMARY KEY,
	role_id            INTEGER NOT NULL,
	permission_id      INTEGER NOT NULL,
	created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE acl_role_permissions ADD CONSTRAINT acl_role_permissions_role_id_fk FOREIGN KEY (role_id) REFERENCES acl_roles (id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE acl_role_permissions ADD CONSTRAINT acl_role_permissions_permission_id_fk FOREIGN KEY (permission_id) REFERENCES acl_permissions (id) ON UPDATE CASCADE ON DELETE CASCADE;
COMMENT ON COLUMN acl_role_permissions.role_id IS 'FK acl_roles.id';
COMMENT ON COLUMN acl_role_permissions.permission_id IS 'FK acl_permissions.id';

CREATE TABLE acl_grants
(
	id                 SERIAL NOT NULL PRIMARY KEY,
	role_id            INTEGER NOT NULL,
	ref_id             INTEGER NOT NULL,
	created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE acl_grants ADD CONSTRAINT acl_grants_role_id_fk FOREIGN KEY (role_id) REFERENCES acl_roles (id) ON UPDATE CASCADE ON DELETE CASCADE;
COMMENT ON COLUMN acl_grants.role_id IS 'FK acl_roles.id';
COMMENT ON COLUMN acl_grants.ref_id IS 'FK to a referrer table such as users.id';
-- if there is a table `users` with a primary key `id`
-- ALTER TABLE acl_grants ADD CONSTRAINT acl_grants_ref_id_fk FOREIGN KEY (ref_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE;
