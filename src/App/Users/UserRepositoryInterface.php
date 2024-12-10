<?php

declare(strict_types=1);

namespace App\Users;

/**
 * User provider interface describes methods to fetch and store
 * user data in some type of storage - database, file, etc.
 */
interface UserRepositoryInterface
{
    /**
     * Locates user by a given key value and returns an array.
     * If there are some users matching the filter only one will
     * be returned
     *
     * @var string $key
     * @var mixed $value
     * @var array|null
     */
    public function find(string $key, $value): array|null;

    /**
     * Saves a new user in the data storage.
     *
     * @param array $user
     * @return array|null New user data
     */
    public function add(array $user): array;

    /**
     * Updates all the data in the storage.
     * Updates to the user's password is done separately @see updatePassword()
     *
     * @param array $user
     * @return array|null Updated user data or null if user not found
     */
    public function update(array $user): array|null;

    /**
     * Updates the user's password.
     *
     * @param array $user
     * @return array|null Updated user data or null if user not found
     */
    public function updatePassword(array $user, string $newPassword): array|null;

    /**
     * Returns the count of users matching the given filter
     *
     * @param array $filter Filter criteria
     * @return int
     */
    public function count(array $filter = []): int;

    /**
     * Returns list of users matching the given filter in particular order.
     *
     * @param mixed $filter Filter criteria
     * @param mixed $order Return the users in specific order.
     *        examples:
     *            'id' - sorts ascending by ID field
     *            '-id' - negative sorting by ID field
     *            ['state', '-created_at'] - ascending state, then descending by created_at
     * @param int $limit Limit result to this number of users.
     * @param int $offset The offset
     *
     *
     * @return array List of users
     */
    public function filter(array $filter = [], array|string $order = '', int $limit = 0, int $offset = 0): array;

    /**
     * Deletes a user.
     *
     * @param array $user
     * @return bool Success or failure
     */
    public function delete(array $user): bool;
}
