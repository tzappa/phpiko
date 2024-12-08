<?php

declare(strict_types=1);

namespace Tests\Clear\Database;

use Clear\Database\Pdo;
use Clear\Database\PdoStatement;
use Clear\Database\PdoInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PDO database class
 */
#[CoversClass(Pdo::class)]
#[CoversClass(PdoStatement::class)]
class PdoTest extends TestCase
{

    public function testCreate()
    {
        $this->assertNotEmpty(new Pdo('sqlite::memory:'));
    }

    public function testPdoImplementsPdoInterface()
    {
        $this->assertTrue(new Pdo('sqlite::memory:') instanceof PdoInterface);
    }

    public function testMyPdoExtendsPdo()
    {
        $this->assertTrue(new Pdo('sqlite::memory:') instanceof \PDO);
    }

    public function testSetAndGetStateMethodsExists()
    {
        $db = new Pdo('sqlite::memory:');
        $this->assertTrue(method_exists($db, 'setState'));
        $this->assertTrue(method_exists($db, 'getState'));
    }

    public function testSetStateReturnsSelf()
    {
        $db = new Pdo('sqlite::memory:');
        $this->assertSame($db, $db->setState(Pdo::STATE_READ_ONLY));
    }

    public function testSetAndGetStateMethods()
    {
        $db = new Pdo('sqlite::memory:');
        $this->assertSame(Pdo::STATE_READ_ONLY, $db->setState(Pdo::STATE_READ_ONLY)->getState());
        $this->assertSame(Pdo::STATE_READ_WRITE, $db->setState(Pdo::STATE_READ_WRITE)->getState());
        $this->assertSame(Pdo::STATE_UNAVAILABLE, $db->setState(Pdo::STATE_UNAVAILABLE)->getState());
    }


    public function testInsertUpdateDeleteInReadWriteMode()
    {
        $db = new Pdo('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(1, $res);

        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(Pdo::FETCH_ASSOC);
        $this->assertNotEmpty($res);
        $this->assertEquals(1, $res['id']);

        $res = $db->exec('UPDATE test SET id = 2 WHERE id = 1');
        $this->assertEquals(1, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(Pdo::FETCH_ASSOC);
        $this->assertNotEmpty($res);
        $this->assertEquals(2, $res['id']);

        $res = $db->exec('DELETE FROM test WHERE id = 2');
        $this->assertEquals(1, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(Pdo::FETCH_ASSOC);
        $this->assertEmpty($res);
    }

    public function testInsertInReadOnlyModeFails()
    {
        $db = new Pdo('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $db->setState(Pdo::STATE_READ_ONLY);
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(false, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(Pdo::FETCH_ASSOC);
        $this->assertEmpty($res);
    }

    public function testUpdateAndDeleteInReadOnlyModeFails()
    {
        $db = new Pdo('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(1, $res);

        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(Pdo::FETCH_ASSOC);
        $this->assertNotEmpty($res);

        $db->setState(Pdo::STATE_READ_ONLY);
        $res = $db->exec('UPDATE test SET id = 2 WHERE id = 1');
        $this->assertEquals(false, $res);

        $res = $db->exec('DELETE FROM test');
        $this->assertEquals(false, $res);
    }

    public function testSelectInUnavailableModeFails()
    {
        $db = new Pdo('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(1, $res);

        $db->setState(Pdo::STATE_UNAVAILABLE);
        $sth = $db->prepare('SELECT * FROM test');
        $res = $sth->execute();
        $this->assertEquals(false, $res);
        $res = $sth->fetch(Pdo::FETCH_ASSOC);
        $this->assertEmpty($res);
    }
}
