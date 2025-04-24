# Access Control List (ACL) System Documentation

This document explains the Access Control List implementation.

## Overview

Our ACL system provides a flexible permission management structure that controls what actions different users can perform within the application. The system is built around several key components that work together to provide granular access control.

## Core Components

### Resources and Actions

- **Resources**: Represent entities in the system that can be accessed or manipulated (e.g., users, articles, comments)
- **Actions**: Operations that can be performed on resources (e.g., view, create, edit, delete)

### Roles and Permissions

- **Permissions**: Define the ability to perform specific actions on specific resources
- **Roles**: Collections of permissions that can be assigned to users
- **User Roles**: Link between users and their assigned roles

## Hierarchical Structure

The ACL diagram shows a hierarchical relationship:
1. Users are assigned one or more roles
2. Roles contain collections of permissions
3. Permissions define allowed actions on resources

## Database Structure

The system uses the following tables:
- `acl_roles` - Defines available roles
- `acl_permissions` - Stores individual permissions
- `acl_role_permissions` - Maps permissions to roles
- `acl_user_roles` - Maps users to roles

See [ACL Diagram](./ACL-diagram.jpg) and corresponding [Database Schema](./schema-sqlite.sql).

## Permission Evaluation Process

1. When a user attempts an action on a resource:
   - The system identifies the user's roles
   - For each role, it checks if there is a permission granting the requested action
   - If any role has the required permission, access is granted
   - Otherwise, access is denied

## Administration

Administrators can:
- Create/modify roles
- Define permissions
- Assign roles to users
- Review and audit permission assignments

## Best Practices

- Follow the principle of least privilege
- Regularly audit role assignments
- Create specialized roles rather than giving excessive permissions
- Consider permission inheritance for hierarchical resource structures

## Implementation Example

```php
// Check if user has permission to perform an action on a resource
function hasPermission($userId, $resourceId, $action) {
    // Get user roles
    $userRoles = getUserRoles($userId);

    // Check each role for the requested permission
    foreach ($userRoles as $role) {
        if (roleHasPermission($role, $resourceId, $action)) {
            return true;
        }
    }

    return false;
}
```
