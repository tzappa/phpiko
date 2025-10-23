<?php

declare(strict_types=1);

namespace Tests\Clear\Database\Event;

use Clear\Database\Event\AfterExec;
use Clear\Database\Event\PdoEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AfterExec::class)]
class AfterExecTest extends TestCase
{
    public function testConstructorSetsEventType(): void
    {
        $queryString = 'SELECT * FROM users';
        $result = 5;
        $event = new AfterExec($queryString, $result);

        $this->assertEquals('AfterExec', $event->getEventType());
    }

    public function testConstructorSetsQueryString(): void
    {
        $queryString = 'SELECT * FROM users WHERE id = ?';
        $result = 3;
        $event = new AfterExec($queryString, $result);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testConstructorSetsResult(): void
    {
        $queryString = 'SELECT * FROM users';
        $result = 10;
        $event = new AfterExec($queryString, $result);

        $this->assertEquals($result, $event->getResult());
    }

    public function testGetQueryStringReturnsCorrectValue(): void
    {
        $queryString = 'INSERT INTO users (name, email) VALUES (?, ?)';
        $result = 1;
        $event = new AfterExec($queryString, $result);

        $this->assertEquals($queryString, $event->getQueryString());
    }

    public function testGetResultReturnsCorrectValue(): void
    {
        $queryString = 'UPDATE users SET name = ? WHERE id = ?';
        $result = 2;
        $event = new AfterExec($queryString, $result);

        $this->assertEquals($result, $event->getResult());
    }

    public function testExtendsPdoEvent(): void
    {
        $event = new AfterExec('SELECT 1', 1);

        $this->assertInstanceOf(PdoEvent::class, $event);
    }

    public function testWithFalseResult(): void
    {
        $queryString = 'INVALID SQL QUERY';
        $event = new AfterExec($queryString, false);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertFalse($event->getResult());
        $this->assertEquals('AfterExec', $event->getEventType());
    }

    public function testWithZeroResult(): void
    {
        $queryString = 'UPDATE users SET name = ? WHERE id = ?';
        $event = new AfterExec($queryString, 0);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals(0, $event->getResult());
    }

    public function testWithLargeResult(): void
    {
        $queryString = 'INSERT INTO users (name) VALUES (?)';
        $result = 1000000;
        $event = new AfterExec($queryString, $result);

        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals($result, $event->getResult());
    }

    public function testDifferentQueryTypes(): void
    {
        $queries = [
            'SELECT * FROM users',
            'INSERT INTO users (name) VALUES (?)',
            'UPDATE users SET name = ? WHERE id = ?',
            'DELETE FROM users WHERE id = ?'
        ];

        foreach ($queries as $index => $query) {
            $event = new AfterExec($query, $index + 1);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals('AfterExec', $event->getEventType());
            $this->assertEquals($index + 1, $event->getResult());
        }
    }

    public function testEmptyQueryString(): void
    {
        $event = new AfterExec('', 0);

        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals('AfterExec', $event->getEventType());
        $this->assertEquals(0, $event->getResult());
    }

    public function testComplexQueryString(): void
    {
        $complexQuery = 'SELECT u.*, p.title FROM users u LEFT JOIN posts p ON u.id = p.user_id WHERE u.active = 1 ORDER BY u.created_at DESC LIMIT 10';
        $result = 5;
        $event = new AfterExec($complexQuery, $result);

        $this->assertEquals($complexQuery, $event->getQueryString());
        $this->assertEquals($result, $event->getResult());
    }

    public function testWithDifferentResultTypes(): void
    {
        $queryString = 'SELECT COUNT(*) FROM users';

        $testResults = [
            0,
            1,
            100,
            999999,
            false
        ];

        foreach ($testResults as $result) {
            $event = new AfterExec($queryString, $result);
            $this->assertEquals($queryString, $event->getQueryString());
            $this->assertEquals($result, $event->getResult());
        }
    }

    public function testSqliteSpecificQueries(): void
    {
        $queries = [
            'PRAGMA table_info(users)' => 0,
            'PRAGMA foreign_keys = ON' => 0,
            'VACUUM' => 0,
            'ANALYZE' => 0,
            'REINDEX' => 0
        ];

        foreach ($queries as $query => $expectedResult) {
            $event = new AfterExec($query, $expectedResult);
            $this->assertEquals($query, $event->getQueryString());
            $this->assertEquals($expectedResult, $event->getResult());
        }
    }

    public function testMultiStatementQuery(): void
    {
        $multiQuery = 'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT); INSERT INTO users (name) VALUES ("John"); SELECT * FROM users;';
        $result = 1;
        $event = new AfterExec($multiQuery, $result);

        $this->assertEquals($multiQuery, $event->getQueryString());
        $this->assertEquals($result, $event->getResult());
    }

    public function testAllPropertiesAreReadonly(): void
    {
        $queryString = 'SELECT 1';
        $result = 42;
        $event = new AfterExec($queryString, $result);

        // Verify all properties cannot be changed after construction
        $this->assertEquals($queryString, $event->getQueryString());
        $this->assertEquals($result, $event->getResult());
    }

    public function testResultCanBeIntOrFalse(): void
    {
        // Test with integer result
        $event1 = new AfterExec('SELECT 1', 5);
        $this->assertIsInt($event1->getResult());
        $this->assertEquals(5, $event1->getResult());

        // Test with false result
        $event2 = new AfterExec('INVALID QUERY', false);
        $this->assertFalse($event2->getResult());
    }
}
