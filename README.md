# Query builder
Simple and fast query builder, no more queries strings in your code

## Features
- Implement [PDO](http://php.net/manual/en/book.pdo.php)
- Simple fetch result
- Easy usage
- Easy installation via composer
- Support SQL transaction
- Support Stored Procedures
- Support alias for tables
- Super **where** method **_:\\/_**

## Future features
- Evit autocommit in transaction
- Support subqueries in joins methods
- **_Put your features here_** ;)

### Installation

```bash
composer install sairoko/query-builder
```

##### Or add dependency in *`composer.json`* file

```js
{
	...
	"require": {
		"sairoko/query-builder": "1.1"
	},
	...
}
```


### *SELECT* Example

The method `assemble` always returned your current *select* SQL string

```PHP
// Set database connection
require './vendor/autoload.php';

$config = array(
	'driver' => 'mysql',
	'host' => '127.0.0.1',
	'port' => '3306',
	'database' => 'DATABASENAME',
	'user' => 'root',
	'root' => 'root'
);

$database = new \Sairoko\QB($config);

// You can use only query builder without connection
$database = new \Sairoko\QB;

// Simple select *
$database->from('table');
// Produce SELECT * FROM table;
$database->table('tablename');
// You can use alias 'table'

// Select fields to fetch
$database->select(['field1', 'field2'])->from('table');
// Produce SELECT field1,field2 FROM table
$database->select('field1');
// Support string as parameter

// Select with where condition
$database->from('table')->where('field2', 'foo');
// Produce SELECT * FROM table WHERE field2 = 'foo';

// Fetch all rows result
$database->from('table')->all();
// Select fields with 'all' method
$database->from('table')->all(['field1', 'field2']);
// Fetch result for SELECT field1,field2 FROM table;

//Fetch first row
$database->from('table')->row();
// Select fields with 'row' method
$database->from('table')->row(['field1','field2']);
// Fetch first row for SELECT field1,field2 FROM table;

// Get SQL string
$query = $database->select(['field1', 'field2'])->from('table')->assemble();
echo $query;
```

### Powerful *WHERE*

Where method return `QB` object

```PHP
// Instance
$database = new \Sairoko\QB;

$database->from('table')->where('field1', 10);
// Produce SELECT * FROM table WHERE field1 = 10;
$database->from('table')->where('field1', 'foo');
// Produce SELECT * FROM table WHERE field1 = 'foo';
// Autodetection for numeric values, in string add quotes

$database->from('table')->where('field1 <> ?', 15);
// Use '?' as wildcard

$database->from('table')->whereIn('field1', [1,'foo',3]);
// Produce SELECT * FROM table WHERE field1 IN(1,'foo',3);

$database->from('table')->whereNotIn('field1', [1,2,3]);
// Produce SELECT * FROM table WHERE field1 NOT IN(1,2,3);

$database->from('table')->whereBetween('field1', [10, 100]);
// Produce SELECT * FROM table WHERE field1 BETWEEN 10 AND 100;

$database->from('table')->whereIsNotNull('field1');
// Produce SELECT * FROM table WHERE field1 IS NOT NULL;

$database->from('table')->whereIsNull('field1');
// Produce SELECT * FROM table WHERE field1 IS NULL;

// Recursive mode you can call any times
$database->from('table')->where('field1', 10)->orWhere('field2 < ?', 1);
// Produce SELECT * FROM table WHERE field1 = 10 OR field2 < 1;

// Support grouped where
$database->from('table')->where('field1', 10)->where(function($q) {
	$q->where('field2', 20);
	$q->orWhere('field3 <> ?', 15);
});
// Produce SELECT * FROM table WHERE field1 = 10 AND (field2 = 20 OR field3 <> 15);

/** 
 * Methods availabes with 'or' prefix
 * orWhere
 * orWhereBetween
 * orWhereNotNull
 */
```

### *JOIN* Examples

```PHP
// Instance
$database = new \Sairoko\QB;

// Inner Join
$database->from('table1')->innerJoin('table2', 'table1.id = table2.id');
// Use alias
$database->select(['a.id','b.id'])->from(['a' => 'table1'])
->innerJoin(['b' => 'table2'], 'a.id = b.id');
// Produce SELECT a.id,b.id FROM table1 AS a INNER JOIN table2 AS b ON a.id = b.id;

// Left Join
$database->from('table1')->leftJoin('table2', 'table1.id = table2.id');
// Use alias
$database->select(['a.id','b.id'])->from(['a' => 'table1'])
->leftJoin(['b' => 'table2'], 'a.id = b.id');
// Produce SELECT a.id,b.id FROM table1 AS a LEFT JOIN table2 AS b ON a.id = b.id;

// Right Join
$database->from('table1')->rightJoin('table2', 'table1.id = table2.id');
// Use alias
$database->select(['a.id','b.id'])->from(['a' => 'table1'])
->rightJoin(['b' => 'table2'], 'a.id = b.id');
// Produce SELECT a.id,b.id FROM table1 AS a RIGHT JOIN table2 AS b ON a.id = b.id;

// Alias methods
$database->select(['a.id','b.id'])->from(['a' => 'table1'])
->join(['b' => 'table2'], 'a.id = b.id');
// Produce inner join by default

$database->select(['a.id','b.id'])->from(['a' => 'table1'])
->join(['b' => 'table2'], 'a.id = b.id', 'left' || 'right');
// Pass the third parameter for select type join
```

### *GROUP BY & ORDER BY* Example

```PHP
// Instance
$database = new \Sairoko\QB;

$database->from('table')->groupBy('field1');
// Produce SELECT * FROM table GROUP BY field1;

$database->from('table')->orderBy('field', 'ASC');
// Produce SELECT * FROM table ORDER BY field ASC;
```

### *LIMIT & OFFSET* Example

```PHP
// Instance
$database = new \Sairoko\QB;

$database->from('table')->limit(1);
// Produce SELECT * FROM table LIMIT 1;

$database->from('table')->limit(10)->offset(2);
// Produce SELECT * FROM table LIMIT 10 OFFSET 2;
```

### *INSERT* Examples

```PHP
// Instance
$database = new \Sairoko\QB($config);

$lastInsertId = $database->insert('tablename', [
	'field1' => 'foo',
	'field2' => 'bar',
	'field3' => 'baz'
]);
// Produce INSERT INTO tablename(field1,field2,field3) VALUES('foo','bar','baz');
// This method return last insert id

// Set table name before insert
$database->table('tablename')->insert([
	'field' => 'foo',
	...
]);
// This option is not supported in batch

// Support insert batch
$database->insertBatch('tablename', [
	[
		'field1' => 'foo',
		'field2' => 'bar'
	],
	[
		'field1' => 'foo2',
		'field2' => 'bar2'
	],
	...
]);
// Produce INSERT INTO tablename(field1,field2) VALUES ('foo','bar'),('foo2','bar2');

// Support update on duplicate key
$database->insertBatch('tablename', [
	[
		'id' => 1,
		'field1' => 'foo',
		'field2' => 'bar'
	],
	[
		'id' => 1,
		'field1' => 'foo2',
		'field2' => 'bar2'
	],
	...
], 'id = id+1');
// Produce INSERT INTO tablename(id,field1,field2) VALUES (1,'foo','bar'),(1,'foo2','bar2') ON DUPLICATE KEY UPDATE id = id+1;

// Get only insert query string
$sql = $database->insertQuery('tablename', [
	'field1' => 100
]);

echo $sql;

$sql = $database->insertBatchQuery('tablename', [
	[
		'field' => 1
	],
	...
]);

echo $sql;
```

### *UPDATE* Examples

```PHP
// Instance
$database = new \Sairoko\QB($config);

// Associative array with fields and new values
$dataUpdate = ['field1' => 'foo', ...];

$database->update('tablename', $dataUpdate);
// Produce UPDATE tablename SET field1 = 'foo';

$database->update('tablename', $dataUpdate, 'field2 = 1');
// Produce UPDATE tablename SET field1 = 'foo' WHERE field2 = 1;

$database->where('field2 IN(?)',[1,2,'foo'])->orWhere('field1', 10)->update('tablename', $dataUpdate);
// Produce UPDATE tablename SET field1 = 'foo' WHERE field2 IN(1,2,'foo') OR field1 = 10;
// You can use method where any times

// Set table name before update
$datbase->table('tablename')->update([
	'field1' => 'bar'
]);
// and use where condition
$database->table('tablename')->where('field2', 1)->update([
	'field1' => 'bar',
	...
]);


// Get only update query string
$sql = $database->where('field1', 1)->updateQuery('tablename', $dataUpdate);
echo $sql;
```

### *DELETE* Examples

```PHP
// Instance
$database = new \Sairoko\QB($config);

$database->delete('tablename');
// Produce DELETE FROM tablename;

$database->delete('tablename', 'field1 = 1');
// Produce DELETE FROM tablename WHERE field1 = 1;

$database->where('field1', 1)->delete('tablename');
// Use where method

// Set table name before delete
$database->table('tablename')->delete();
// and use where condition
$database->table('tablename')->where('field1', 10)->delete();

// Get only delete query string
$sql = $database->where('field1', 10)->deleteQuery('tablename');
echo $sql;
```

### *CALL PROCEDURE* Example

This method use for complete PDO resources

More info visit [PDO documentation](http://php.net/manual/en/pdo.prepared-statements.php#pdo.prepared-statements)

```PHP
// Instance
$database = new \Sairoko\QB($config);

$status = $database->call('procedure_name', [1, 2]);
// Execute CALL procedure_name(1,2);
// Return true or false

$result = $database->call('sp_test', ['foo', 'bar'], true);
// Fetch rows result
```

### *Transaction* Example

```php
// Instance
$database = new \Sairoko\QB($config);

$database->transaction(function($q){
	$q->table('tablename')->insert(['field1' => 'foo']);
});
```

### Get PDO instance

```PHP
// Instance
$database = new \Sairoko\QB($config);

$pdo = $database->db();
// db method return PDO instance
```

## Enjoy!
