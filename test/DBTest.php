<?php
require 'test/MockSlim.php';
require 'Slimx/DB.php';

class DBTest extends PHPUnit_Framework_TestCase
{
	protected $sql = array(
		'DROP TABLE IF EXISTS box;',
		'CREATE TABLE "box" ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" TEXT UNIQUE, "fiddlybit" TEXT, "fiddlynum" INTEGER DEFAULT 42);',
		'INSERT INTO "box" VALUES (1, "bob", "banana", 42);',
		'INSERT INTO "box" VALUES (2, "agatha", "foobar", 99);',
		'INSERT INTO "box" VALUES (3, "coyote", "nota bene", 1);',
	);

	protected $db;

	protected function setUp()
	{
		$this->db = new \Slimx\DB();
		foreach ($this->sql as $s) {
			$this->db->exec($s);
		}
	}

	public function testQuery()
	{
		$sth = $this->db->query('UPDATE box set name = "foo" WHERE id = 1');
		$this->assertInstanceOf('PDOStatement', $sth);
	}

	public function testExec()
	{
		$rows = $this->db->exec('UPDATE box SET name = ? WHERE id = 2', 'ernestine');
		$this->assertEquals(1, $rows);
		$rows = $this->db->exec('UPDATE box SET name = ? WHERE id = ?',
			array('ernestine', 99));
		$this->assertEquals(0, $rows);
		$rows = $this->db->exec('UPDATE box set fiddlynum = 23');
		$this->assertEquals(3, $rows);
	}

	public function testRead()
	{}

	public function testReadSet()
	{}

	public function testReadHash()
	{}

}
