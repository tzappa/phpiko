<?php

declare(strict_types=1);

namespace Tests\Clear\Database;

use Clear\Database\PdoExt;
use Clear\Database\PdoStatementExt;
use Clear\Database\PDOInterface;
use Clear\Database\Event\{
    AfterConnect,
    AfterExec,
    AfterExecute,
    AfterQuery,
    BeforeExec,
    BeforeExecute,
    BeforeQuery
};
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use PDO;

/**
 * Tests for PdoExt database class
 */
#[CoversClass(PdoExt::class)]
#[CoversClass(PdoStatementExt::class)]
#[CoversClass(AfterConnect::class)]
#[CoversClass(AfterExec::class)]
#[CoversClass(AfterExecute::class)]
#[CoversClass(AfterQuery::class)]
#[CoversClass(BeforeExec::class)]
#[CoversClass(BeforeQuery::class)]
#[CoversClass(BeforeExecute::class)]
class PdoExtTest extends TestCase
{
    public function testCreate(): void
    {
        $this->assertNotEmpty(new PdoExt('sqlite::memory:'));
    }

    public function testPdoImplementsPdoInterface(): void
    {
        $this->assertTrue(new PdoExt('sqlite::memory:') instanceof PdoInterface);
    }

    public function testMyPdoExtendsPdo(): void
    {
        $this->assertTrue(new PdoExt('sqlite::memory:') instanceof PDO);
    }

    public function testInsertUpdateDelete(): void
    {
        $db = new PdoExt('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $res = $db->exec('INSERT INTO test (id) VALUES (1)');
        $this->assertEquals(1, $res);

        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($res);
        $this->assertEquals(1, $res['id']);

        $res = $db->exec('UPDATE test SET id = 2 WHERE id = 1');
        $this->assertEquals(1, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($res);
        $this->assertEquals(2, $res['id']);

        $res = $db->exec('DELETE FROM test WHERE id = 2');
        $this->assertEquals(1, $res);
        $sth = $db->prepare('SELECT * FROM test');
        $sth->execute();
        $res = $sth->fetch(PDO::FETCH_ASSOC);
        $this->assertEmpty($res);
    }

    public function testExecEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $db = new PdoExt('sqlite::memory:');
        $db->setEventDispatcher($dispatcher);
        $dispatcher->expects($this->exactly(2))->method('dispatch');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
    }
    public function testExecuteEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $db = new PdoExt('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $db->setEventDispatcher($dispatcher);

        $sth = $db->prepare('SELECT * FROM test');
        $dispatcher->expects($this->exactly(2))->method('dispatch');
        $sth->execute();
    }

    public function testQueryEvents(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $db = new PdoExt('sqlite::memory:');
        $db->exec('CREATE TABLE test (id INTEGER NOT NULL)');
        $db->setEventDispatcher($dispatcher);

        $dispatcher->expects($this->exactly(2))->method('dispatch');
        $db->query('SELECT * FROM test');
    }
}
