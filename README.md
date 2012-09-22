# SlimX - Extensions for Slim

This is a *very* small set of extensions for the [Slim Framework](http://www.slimframework.com), a Sinatra-like "microframework" for PHP.

A couple years ago I started my own PHP microframework, Flagpole; it became usable, but never really advanced much, and I shelved it when it became clear there were other better projects of a similar nature like Slim.

Actually starting a project in Slim, though, showed me two things that I missed from Flagpole: a lightweight database access wrapper and a "native PHP" templating system that at *least* supported the concept of layouts like early Rails apps did. It appears a lot of people using Slim are using [Twig](http://twig.sensiolabs.org) for templating; I think Twig is a terrific template system, but sometimes you don't need that level of abstraction, or you're working with co-workers who only know pure PHP. And often you don't need an ORM, but you'd still like to have some convenience wrappers for database access.

## \Slimx\View

This class extends `\Slim\View` to have two extra concepts: *layouts* and *the base tag.*

### Layouts

This is a simple version of template inheritance. A template's layout is just a "frame" in which the content is placed, appropriately enough in the variable `$content`. A layout might look like this:

	<!DOCTYPE html>
	<html lang="en">
	<head>
	  <title>Site<?php if (isset($_title)) echo " | $_title" ?></title>
	  <link rel="stylesheet" href="<?= $_base ?>/css/style.css">
	</head>
	<body>
	  <div class="container">
	    <?= $content ?>
	  </div>
	</body>
	</html>

You can specify a default layout file for a site by passing it as an argument to the constructor:

	new \Slimx\View('_mylayout.php');

By default, the file will be named `_layout.php`. Layouts are stored in the template directory you've configured (`templates` by default). Layouts will have access to all the variables the template does.

### Base variable

You might have noticed `$_base` in the template above. This is always automatically set to the `SCRIPT_NAME` Slim environment. Why? Because the first time I tried to set up a URL like

	$app->get('/item/create', function() { ... });

...my CSS files stopped working, because they're all relative to the *virtual* directory that path segment implies, i.e., `/item/css/`. If you know your app is always going to be deployed at the same base all the time, in both development and production, you don't need this, but you might not always know it.

## \Slim\DB

This expects you to have set up to four keys in your Slim application config space (`$app->config()`), one of which is always required.

* `dsn`: a valid PDO-style DSN, i.e., `mysql:host=localhost;dbname=mydb`. You may use the "@" key as a placeholder for the database name, which is useful if you need to access more than one database. (My first Slim app actually needed to!)
* `db_user`: The database username, if needed.
* `db_password`: The database password, if needed.
* `pdo_fetch_style`: a PDO constant to specify the fetch style, if you don't like Slimx's default choice of `PDO::FETCH_OBJ`.

### Constructor

The required first argument is your Slim application object. The optional second argument is a database name for the PDO object to instantiate.

	$db = new \Slimx\DB($app, 'mydb');

### DB::pdo()

Returns the PDO object itself, if you need it.

### DB::query($query, $params)

Executes SQL query, with an optional parameter *or* array.

	$db->query('UPDATE mytable SET foo = ? WHERE id = 1', 'banana');

If you only need to pass one parameter, it doesn't need to be an array; multiple parameters do need to be an array. Use an associative array for named parameters (`array(':foo' => 'banana', ':bar' => 'apricot')`).

This will return a `PDOStatement` object.

### DB::read($query, $params)

Executes the given query, as above, but returns a single value directly. If the query returns more than one value, you're only getting the first one back. (If you want more than one value, use `readSet` below or roll your own.)

	$res = $db->read('SELECT * FROM mytable WHERE id = ?', 2);

* For a *single-column result,* you get the single return value.
* For a *multiple-column result,* you'll get an object (or whatever you've requested if you've changed the fetch style).

### DB::readSet($query, $params)

Executes the given query, as above, but returns the result values directly.

	$res = $db->readset('SELECT id, name, other_id FROM mytable');

* For a *single-column result,* you get a (non-associative) array.
* For a *multiple-column result,* you'll get an array of objects (or whatever you've requested if you've changed the fetch style).

### DB::readHash($query, $params)

A special version of `readSet` designed for queries that return only two columns: this will return an associative array where the first column is the key and the second column is the value.

	$res = $db->readHash('SELECT id, name FROM mytable');

Returns (for example):

	array(1001 => 'banana', 1002 => 'apricot', 1003 => 'carambola')

This will throw a `LengthException` if the query returns more or less than two columns.

### DB::insert($table, $data)

Inserts the data given in `$data` -- which should be an associative array or an object of similar functionality, like the ones returned by PDO -- into a specified table.

	$data = array('id'=>1, 'name'=>'banana', 'other_id'=>42);
	$db->insert('mytable', $data);

### DB:update($table, $data, $key)

The equivalent of the above, but for updating existing records. `$key` should be the column name of the primary key used for the updating. (Yes, this only supports single-column keys, sorry; if you need more than one, use `query` to do it.) The default name of the primary key is (surprise) `id`. The `$data` array/object must contain a field for the primary key, or the program will poop, by which I mean throw an `InvalidArgumentException`.

	$data = array('id'=>1, 'name'=>'banana', 'other_id'=>42);
	$db->update('mytable', $data);

### DB:save($table, $data, $key)

Combines the last two in one quasi-clever operation: if the primary key exists in the database, it's an UPDATE, otherwise it's an INSERT.

### DB:get($table, where, $key)

This is a convenience-maybe function that will return all columns in one or more rows of a single table. It can be called in one of two forms:

	$db->get('mytable', 2);
	$db->get('mytable', 'id >= 100 AND id <= 200');

In the first form, it will retrieve the record from `mytable` whose primary key is 2, and return it as an object (or whatever your PDO fetch style is set to). In the second form, it will retrieve all the records from `mytable` matching that WHERE clause and return an array of objects (or whatevers).

In both cases, you can specify an optional third argument giving the primary key name if that name isn't `id`.

Note that the function differentiates between the first and second forms by testing to see whether the second argument is a string. So, you can't use the first form of this function if you have a table whose primary keys are strings. (I've never seen such a table, but strange things are out there.)