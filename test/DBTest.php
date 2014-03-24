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

	public function testGetPdo()
	{
		$pdo = $this->db->getPdo();
		$this->assertInstanceOf('PDO', $pdo);
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
	{
		$row = $this->db->read('SELECT * FROM box WHERE id = ?', 1);
		$return = (object)['id' => 1, 'name' => 'bob', 'fiddlybit' =>
			'banana', 'fiddlynum' => 42];
		$this->assertEquals($row, $return);
		$row = $this->db->read('SELECT * FROM box WHERE id = ?', 99);
		$this->assertFalse($row);
	}

	public function testReadSet()
	{
		$rows = $this->db->readSet('SELECT * FROM box');
		$return = [
			(object)['id' => 1, 'name' => 'bob', 'fiddlybit' =>
				'banana', 'fiddlynum' => 42],
			(object)['id' => 2, 'name' => 'agatha', 'fiddlybit' =>
				'foobar', 'fiddlynum' => 99],
			(object)['id' => 3, 'name' => 'coyote', 'fiddlybit' =>
				'nota bene', 'fiddlynum' => 1],
		];
		$this->assertEquals($rows, $return);

		$rows = $this->db->readSet('SELECT * FROM box WHERE id > 9');
		$return = [];
		$this->assertEquals($rows, $return);
	}

	public function testReadHash()
	{
		$rows = $this->db->readHash('SELECT id, name FROM box');
		$return = [1 => 'bob', 2 => 'agatha', 3 => 'coyote'];
		$this->assertEquals($rows, $return);
	}

	/**
	 * @expectedException LengthException
	 * @expectedExceptionMessage DB::readHash() expects 2 columns returned from query
	 */
	public function testReadHashException()
	{
		$rows = $this->db->readHash('SELECT id, name, fiddlybit FROM box');
	}

	public function testInsert()
	{
		$data = ['name' => 'georgia', 'fiddlybit' => 'kumquat',
			'fiddlynum' => 78];
		$id = $this->db->insert('box', $data);
		$row = $this->db->read('SELECT * FROM box WHERE id = ?', $id);
		$this->assertEquals($row->name, 'georgia');
	}

	public function testUpdate()
	{
		$data = ['name' => 'georgia', 'fiddlybit' => 'kumquat',
			'fiddlynum' => 78, 'id' => 2];
		$count = $this->db->update('box', $data);
		$this->assertEquals($count, 1);
		$row = $this->db->read('SELECT * FROM box WHERE id = ?', 2);
		$this->assertEquals($row->name, 'georgia');
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage DB::update() called with data missing primary key (id)
	 */
	public function testUpdateException()
	{
		$data = ['name' => 'georgia', 'fiddlybit' => 'kumquat',
			'fiddlynum' => 78];
		$count = $this->db->update('box', $data);
	}

	public function testSave()
	{
		$data = ['name' => 'georgia', 'fiddlybit' => 'kumquat',
			'fiddlynum' => 78];
		$id = $this->db->save('box', $data);
		$row = $this->db->read('SELECT * FROM box WHERE id = ?', $id);
		$this->assertEquals($row->name, 'georgia');

		$data = ['name' => 'hazel', 'fiddlybit' => 'rutabaga',
			'fiddlynum' => 444, 'id' => 2];
		$count = $this->db->save('box', $data);
		$this->assertEquals($count, 1);
		$row = $this->db->read('SELECT * FROM box WHERE id = ?', 2);
		$this->assertEquals($row->name, 'hazel');
	}

	public function testDelete()
	{
		$count = $this->db->delete('box', 2);
		$this->assertEquals($count, 1);
		$row = $this->db->read('SELECT * FROM box WHERE id = ?', 2);
		$this->assertFalse($row);
	}

	public function testGet()
	{
		$get = $this->db->get('box', 1);
		$return = (object)['id' => 1, 'name' => 'bob', 'fiddlybit' =>
			'banana', 'fiddlynum' => 42];
		$this->assertEquals($get, $return);

		$get = $this->db->get('box', 'id > 1');
		$return = [
			(object)['id' => 2, 'name' => 'agatha', 'fiddlybit' =>
				'foobar', 'fiddlynum' => 99],
			(object)['id' => 3, 'name' => 'coyote', 'fiddlybit' =>
				'nota bene', 'fiddlynum' => 1],
		];
		$this->assertEquals($get, $return);
		$get = $this->db->get('box', 'id >= ?', 2);
		$this->assertEquals($get, $return);
	}

	public function testClose()
	{
		$this->db->close();
		$this->assertNull($this->db->getPdo());
	}

}
