<?php

declare(strict_types=1);

namespace Tests\Clear\ACL;

use PDO;

/**
 * PDO Sqlite3 memory database as a Data Provider
 */
trait DbTrait
{
    /**
     * @var \PDO
     */
    private $db;

    private $rolesCount;
    private $permissionsCount;
    private $rolePermissionsCount;

    private $user1 = 1;
    private $user2 = 7;
    private $user3 = 3;

    private function setUpDb(): void
    {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $schema = "
CREATE TABLE admins
(
    id                 INTEGER NOT NULL PRIMARY KEY,
    email              VARCHAR(255) NOT NULL UNIQUE,
    pass               VARCHAR(255),
    state              INTEGER NOT NULL DEFAULT 1,
    real_name          VARCHAR(128),
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))
);
CREATE TABLE acl_permissions
(
    id                 INTEGER NOT NULL PRIMARY KEY,
    object             VARCHAR(64) NOT NULL,
    operation          VARCHAR(64) NOT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))
);

CREATE TABLE acl_roles
(
    id                 INTEGER NOT NULL PRIMARY KEY,
    name               VARCHAR(64) NOT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))
);

CREATE TABLE acl_role_permissions
(
    id                 INTEGER NOT NULL PRIMARY KEY,
    role_id            INTEGER NOT NULL,
    permission_id      INTEGER NOT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))
);

CREATE TABLE acl_grants
(
    id                 INTEGER NOT NULL PRIMARY KEY,
    role_id            INTEGER NOT NULL,
    ref_id             INTEGER NOT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC')),
    updated_at         TIMESTAMP NOT NULL DEFAULT (datetime('now', 'UTC'))
)
        ";

        $data = "
INSERT INTO admins (id, email, pass, state) VALUES ({$this->user1}, 'blocked@example.com', '', 3);
INSERT INTO admins (id, email, pass, state) VALUES ({$this->user2}, 'tzappa@example.com', '', 1);
INSERT INTO admins (id, email, pass, state) VALUES ({$this->user3}, 'tzappa@gmail.com', null, 1);
INSERT INTO acl_roles (id, name) VALUES (1, 'Manage Users');
INSERT INTO acl_roles (id, name) VALUES (2, 'Statistics');
INSERT INTO acl_roles (id, name) VALUES (3, 'Login As');
INSERT INTO acl_roles (id, name) VALUES (4, 'Manage Roles');
INSERT INTO acl_permissions (id, object, operation) VALUES (1,  'Users', 'list');
INSERT INTO acl_permissions (id, object, operation) VALUES (2,  'Users', 'change-state');
INSERT INTO acl_permissions (id, object, operation) VALUES (3,  'Users', 'create');
INSERT INTO acl_permissions (id, object, operation) VALUES (4,  'Users', 'edit');
INSERT INTO acl_permissions (id, object, operation) VALUES (5,  'Users', 'delete');
INSERT INTO acl_permissions (id, object, operation) VALUES (6,  'Users', 'assign-role');
INSERT INTO acl_permissions (id, object, operation) VALUES (7,  'Roles', 'list');
INSERT INTO acl_permissions (id, object, operation) VALUES (8,  'Roles', 'manage');
INSERT INTO acl_permissions (id, object, operation) VALUES (9,  'Users', 'login-as');
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (1,  1, 1);  -- 'Manage Users' can List Users
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (2,  1, 2);  -- 'Manage Users' can Change State for Users
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (3,  1, 3);  -- 'Manage Users' can Create Users
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (4,  1, 4);  -- 'Manage Users' can Edit Users
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (5,  1, 5);  -- 'Manage Users' can Delete Users
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (6,  1, 6);  -- 'Manage Users' can Assign user to a role
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (7,  1, 7);  -- 'Manage Users' can List Roles
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (8,  2, 1);  -- 'Statistics' can List Users
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (9,  2, 7);  -- 'Statistics' can List Roles
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (10, 3, 9);  -- 'Login As' role to Login as user
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (11, 4, 7);  -- 'Manage Roles' can List Roles
INSERT INTO acl_role_permissions (id, role_id, permission_id) VALUES (12, 4, 8);  -- 'Manage Roles' can edit role, add/remove permissions for a role
INSERT INTO acl_grants (id, ref_id, role_id) VALUES (1, {$this->user3}, 1); -- user 3 can 'Manage Users'
INSERT INTO acl_grants (id, ref_id, role_id) VALUES (2, {$this->user3}, 2); -- user 3 can 'Statistics'
INSERT INTO acl_grants (id, ref_id, role_id) VALUES (3, {$this->user1}, 2); -- user 1 can use 'Statistics'
INSERT INTO acl_grants (id, ref_id, role_id) VALUES (4, {$this->user3}, 3); -- user 3 can 'Login as'
INSERT INTO acl_grants (id, ref_id, role_id) VALUES (5, {$this->user3}, 4); -- user 3 can 'Manage Roles'
        ";
        $db->exec($schema);
        $db->exec($data);

        $this->rolesCount = preg_match_all('/INSERT INTO acl_roles /', $data);
        $this->permissionsCount = preg_match_all('/INSERT INTO acl_permissions /', $data);
        $this->rolePermissionsCount = preg_match_all('/INSERT INTO acl_role_permissions /', $data);

        $this->db = $db;
    }
}
