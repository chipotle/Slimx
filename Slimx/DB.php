<?php namespace Slimx;

use Slim\Slim;

/**
 * Very lightweight wrapper around PDO
 *
 * This expects configuration keys in the Slim application object:
 *
 *    dsn              DSN for PDO connection. Use @ as a placeholder for
 *                     dbname (i.e., "mysql:host=localhost;dbname=@")
 *    db_user          username for DB access (if needed)
 *    db_password      password for DB access (if needed)
 *    pdo_fetch_style  optional return style for PDO records. Default is
 *                     PDO::FETCH_OBJ.
 */
class DB
{
  private $pdo;
  private $pdoFetchStyle = \PDO::FETCH_OBJ;

  /**
   * Constructor. This sets up the PDO object.
   *
   * @param string $dbname database name to connect to (optional)
   */
  public function __construct($dbname=null)
  {
    $app = Slim::getInstance();
    $dsn = $app->config('dsn');
    if ($dbname) {
      $dsn = str_replace('@', $dbname, $dsn);
    }
    if ($app->config('pdo_fetch_style')) {
      $this->pdoFetchStyle = $app->config('pdo_fetch_style');
    }
    $this->pdo = new \PDO(
      $dsn,
      $app->config('db_user'),
      $app->config('db_password'),
      array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION)
    );
  }

  /**
   * Return the PDO object, for direct manipulation if necessary
   *
   * @return PDO object
   */
  public function getPdo()
  {
    return $this->pdo;
  }

  /**
   * Execute a query and return result set
   *
   * To use bound parameters, pass them as a second parameter. If you have
   * only one parameter it may be passed directly, otherwise use an array.
   * Named parameters must always be an associative array. This is usually
   * a less useful function than read, readSet and readHash.
   *
   * @param string $query query statement
   * @param mixed $params query parameters (single or array)
   * @return PDOStatement
   */
  public function query($query, $params=null)
  {
    if ($params && !is_array($params)) $params = array($params);
    $sth = $this->pdo->prepare($query);
    $sth->execute($params);
    return $sth;
  }

  /**
   * Execute a query and return count of affected rows
   *
   * @param string $query query statement
   * @param mixed $params query parameters (single or array)
   * @return int affected rows
   */
  public function exec($query, $params=null)
  {
    if ($params && !is_array($params)) $params = array($params);
    $sth = $this->pdo->prepare($query);
    $sth->execute($params);
    return $sth->rowCount();
  }

  /**
   * Read a single record with a query
   *
   * This will return either an object with the record fields, or if the
   * query returns a single column, a single variable.
   *
   * @param string $query
   * @param mixed $params Parameter array or single string
   * @return mixed|array returned records (or false)
   */
  public function read($query, $params=null)
  {
    $sth = $this->query($query, $params);
    if (!$sth) return false;
    if ($sth->columnCount() > 1) {
      return $sth->fetch($this->pdoFetchStyle);
    }
    return $sth->fetchColumn();
  }

  /**
   * Read a set of records with a query
   *
   * This behaves as the read() function, but will return an array of
   * record objects or an array of values for a single column query.
   *
   * @param string $query
   * @param mixed $params Parameter array or single string
   * @return object|array returned records
   */
  public function readSet($query, $params=null)
  {
    $sth = $this->query($query, $params);
    if (!$sth) return array();
    if ($sth->columnCount() > 1) {
      return $sth->fetchAll($this->pdoFetchStyle);
    }
    return $sth->fetchAll(\PDO::FETCH_COLUMN);
  }

  /**
   * Read a set of key/value hashes with a query
   *
   * This expects a query that returns two and only two columns. It will
   * return a hash-style array in which the first column returned is the
   * key and the second is the value.
   *
   * @param string $query
   * @param mixed $params Parameter array or single string
   * @return array returned records
   * @throws LengthException
   */
  public function readHash($query, $params=null)
  {
    $sth = $this->query($query, $params);
    if (!$sth) return array();
    if ($sth->columnCount() != 2) {
      throw new \LengthException('DB::readHash() expects 2 columns returned from query');
    }
    return $sth->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  /**
   * Save a record to a table.
   *
   * This calls insert() or update() as appropriate.
   *
   * @param string $table table name
   * @param array|object $data column names and values
   * @param string $key column name of primary key (default "id")
   * @return PDOStatement object
   */
  public function save($table, $data, $key='id')
  {
    $data = (array)$data;
    if (!isset($data[$key])) {
      return $this->insert($table, $data);
    }
    return $this->update($table, $data, $key);
  }

  /**
   * Insert a record into a table
   *
   * @param string $table table name
   * @param array|object $data column names and values
   * @return mixed ID of last inserted row/sequence
   */
  public function insert($table, $data)
  {
    $data = (array)$data;
    $columns = implode(', ', array_keys($data));
    $values = implode(', ', array_fill(0, count($data), '?'));
    $query = "INSERT INTO $table ($columns) VALUES ($values)";
    $this->query($query, array_values($data));
    return $this->pdo->lastInsertId();
  }

  /**
   * Update a record in a table
   *
   * @param string $table table name
   * @param array|object $data column names and values
   * @param string $key column name of primary key (default "id")
   * @return int count of records affected
   */
  public function update($table, $data, $key='id')
  {
    $data = (array)$data;
    if (!isset($data[$key])) {
      throw new \InvalidArgumentException("DB::update() called with data missing primary key ($key)");
    }
    $set = array();
    foreach (array_keys($data) as $col) {
      $set[] = "$col = ?";
    }
    $setq = implode(', ', $set);
    $query = "UPDATE $table SET $setq WHERE $key = ?";
    $params = array_values($data);
    $params[] = $data[$key];
    return $this->exec($query, $params);
  }

  /**
   * Delete a record in a table
   *
   * @param string $table table name
   * @param mixed $id key value to delete
   * @param string $key column name of primary key (default "id")
   * @return int count of affected rows
   */
  public function delete($table, $id, $key='id')
  {
    $query = "DELETE FROM $table WHERE $key = ?";
    return $this->exec($query, $id);
  }

  /**
   * Read one or more records from a table
   *
   * This returns all columns in one or more rows. It can be called in one
   * of two ways:
   *
   *    $db->get('mytable', 2);
   *    $db->get('mytable', 2, 'id');
   *
   *    $db->get('mytable', 'id >= 100 AND id <= 200');
   *    $db->get('mytable', 'id >= ? AND id <= ?', array($x, $y));
   *
   * Note that the first form *only* works with key values that are numeric;
   * if the second parameter is a string, it is assumed to be a condition.
   *
   * @param string $table table name
   * @param mixed $where key value or WHERE clause
   * @param mixed $key lookup column (default "id") or parameter array
   * @see read()
   * @see readSet()
   * @return mixed
   */
  public function get($table, $where, $key=null)
  {
    $query = "SELECT * FROM $table WHERE ";
    if (is_string($where)) {
      $query .= $where;
      return $this->readSet($query, $params);
    }
    $key = $key ?: 'id';
    $query .= "$key = ?";
    return $this->read($query, $where);
  }

  /**
   * Close the connection.
   */
  public function close()
  {
    $this->pdo = null;
  }

}
