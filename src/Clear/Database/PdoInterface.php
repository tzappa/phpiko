<?php 

declare(strict_types=1);

namespace Clear\Database;

use PDO;
use PDOStatement;

/**
 * PDO Interface declares all PDO methods as they are defined in PHP
 */
interface PdoInterface
{
    /**
     * Begins a transaction and turns off autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.begintransaction.php
     */
    public function beginTransaction();

    /**
     * Commits the existing transaction and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.commit.php
     */
    public function commit();

    /**
     * Gets the most recent error code.
     *
     * @return mixed
     */
    public function errorCode();

    /**
     * Gets the most recent error info.
     *
     * @return array
     */
    public function errorInfo();

    /**
     * Executes an SQL statement and returns the number of affected rows.
     *
     * @param string $statement The SQL statement to execute.
     *
     * @return int The number of rows affected.
     *
     * @see http://php.net/manual/en/pdo.exec.php
     */
    public function exec(string $statement);

    /**
     * Is a transaction currently active?
     *
     * @return bool
     *
     * @see http://php.net/manual/en/pdo.intransaction.php
     */
    public function inTransaction();

    /**
     * Returns the last inserted autoincrement sequence value.
     *
     * @param string $name The name of the sequence to check; typically needed
     * only for PostgreSQL, where it takes the form of `<table>_<column>_seq`.
     *
     * @return string
     *
     * @see http://php.net/manual/en/pdo.lastinsertid.php
     */
    public function lastInsertId(?string $name = null);

    /**
     * Prepares an SQL statement for execution.
     *
     * @param string $statement The SQL statement to prepare for execution.
     *
     * @param array $options Set these attributes on the returned
     * PDOStatement.
     *
     * @return \PDOStatement
     *
     * @see http://php.net/manual/en/pdo.prepare.php
     */
    public function prepare(string $query, array $options = []);

    /**
     * Queries the database and returns a PDOStatement.
     *
     * @param string $statement The SQL statement to prepare and execute.
     * @param mixed ...$fetch Optional fetch-related parameters.
     *
     * @return \PDOStatement|false
     *
     * @see http://php.net/manual/en/pdo.query.php
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false;

    /**
     * Quotes a value for use in an SQL statement.
     *
     * @param mixed $value The value to quote.
     * @param int $parameterType A data type hint for the database driver.
     *
     * @return string The quoted value.
     *
     * @see http://php.net/manual/en/pdo.quote.php
     */
    public function quote(string $string, int $type = PDO::PARAM_STR);

    /**
     * Rolls back the current transaction and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     *
     * @see http://php.net/manual/en/pdo.rollback.php
     */
    public function rollBack();

    /**
     * Sets a PDO attribute value.
     *
     * @param int $attribute The PDO::ATTR_* constant.
     * @param mixed $value The value for the attribute.
     *
     * @return bool TRUE on success or FALSE on failure.
     *
     * @see http://php.net/manual/en/pdo.setattribute.php
     */
    public function setAttribute(int $attribute, mixed $value);

    /**
     * Retrieve a database connection attribute.
     *
     * @param int $attribute The PDO::ATTR_* constant.
     *
     * @return mixed The value for the attribute.
     *
     * @see http://php.net/manual/en/pdo.getattribute.php
     */
    public function getAttribute(int $attribute);

    /**
     * Returns all currently available PDO drivers.
     *
     * @return array
     */
    public static function getAvailableDrivers();
}
