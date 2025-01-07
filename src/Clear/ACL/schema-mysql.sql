CREATE TABLE IF NOT EXISTS acl_permissions
(
	id                 INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	object             VARCHAR(64) NOT NULL,
	operation          VARCHAR(64) NOT NULL,
	created_at         TIMESTAMP NOT NULL DEFAULT NOW(),
	updated_at         TIMESTAMP NOT NULL DEFAULT NOW()
);


CREATE TABLE IF NOT EXISTS acl_roles
(
	id                 INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	name               VARCHAR(64) NOT NULL,
	created_at         TIMESTAMP NOT NULL DEFAULT NOW(),
	updated_at         TIMESTAMP NOT NULL DEFAULT NOW()
);


CREATE TABLE IF NOT EXISTS acl_role_permissions
(
	id                 INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	role_id            INTEGER NOT NULL,                       -- FK acl_roles.id
	permission_id      INTEGER NOT NULL,                       -- FK acl_permissions.id
	created_at         TIMESTAMP NOT NULL DEFAULT NOW(),
	updated_at         TIMESTAMP NOT NULL DEFAULT NOW()
);
ALTER TABLE acl_role_permissions ADD INDEX acl_role_permissions_role_id_fk_inx(role_id ASC);
ALTER TABLE acl_role_permissions ADD CONSTRAINT acl_role_permissions_role_id_fk FOREIGN KEY (role_id) REFERENCES acl_roles (id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE acl_role_permissions ADD INDEX acl_role_permissions_permission_id_fk_inx(permission_id ASC);
ALTER TABLE acl_role_permissions ADD CONSTRAINT acl_role_permissions_permission_id_fk FOREIGN KEY (permission_id) REFERENCES acl_permissions (id) ON UPDATE CASCADE ON DELETE CASCADE;


CREATE TABLE IF NOT EXISTS acl_grants
(
	id                 INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,
	role_id            INTEGER NOT NULL,                       -- FK acl_roles.id
	ref_id             INTEGER NOT NULL,                       -- FK to a referrer table such as users.id
	created_at         TIMESTAMP NOT NULL DEFAULT NOW(),
	updated_at         TIMESTAMP NOT NULL DEFAULT NOW()
);
ALTER TABLE acl_grants ADD INDEX acl_grants_role_id_fk_inx(role_id ASC);
ALTER TABLE acl_grants ADD CONSTRAINT acl_grants_role_id_fk FOREIGN KEY (role_id) REFERENCES acl_roles (id) ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE acl_grants ADD INDEX acl_grants_ref_id_fk_inx(ref_id ASC);
-- if there is a table `users` with a primary key `id`
-- ALTER TABLE acl_grants ADD CONSTRAINT acl_grants_ref_id_fk FOREIGN KEY (ref_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE;
