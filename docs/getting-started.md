# Getting Started
You want to get started with Spider? Shinny.

*Please be aware that Spider is still in development. As we march toward 1.0, things will inevetably change. We do our best to keep code stable and secure, but we are still architecting the awesome. Please [get involved!](contributing.md)

## What are Graph Databases?
[Graph databases](https://en.wikipedia.org/wiki/Graph_database) are NoSql databases (not-only-sql) that treat relationships like first-class citizens.
That means that relationships can have properties.

For instance: character:Zoe -> is_married_to -> character:Wash.
Now, we can add properties to the relationship (is_married_to): years = 3.

This may seem like a simple thing, but it gives an enormous amount of power. Now we can discover paths, follow relationships, do nested queries, and get friends of friends of friends. All blazing fast compared to SQL.

For a more thorough introduction to graph databases, check out:
  * http://phoenixlabstech.org/2014/08/19/php-graph-databases-and-the-future/
  * http://neo4j.com/developer/graph-database/
  * http://www.slideshare.net/maxdemarzi/introduction-to-graph-databases-12735789
  * http://markorodriguez.com/

## Requirements and Supported Datastores
Requires:
  * PHP 5.4
  
Currently supports:
  * OrientDB 2.*
  * Neo4j 2.*
  * Gremlin Server 3.*-incubating (querybuilder not yet supported)
  
It's easy to [add your own driver](create-driver.md)

## Install
Via Composer
``` bash
$ composer require spider/spider
```

The `master` branch contains stable code, though not necessarily ready for production.
The `develop` branch is a step ahead and may me unstable right now.

## Crawl Your Data
Spider is a collection of many different pieces including connections, drivers, querybuilders, and the like.
To get up an running fast, configure all of them from one place and use a `Spider` to do everything you need to do.

```php
Spider\Spider::setup([
     'connections' => [
         'default' => 'orient',
         'orient' => [
             'driver' => 'orientdb',
             'hostname' => 'localhost',
             'port' => 2424,
             'username' => 'root',
             'password' => "root",
             'database' => 'modern_graph'
         ]
     ]
 ];
```

Three drivers ship with Spider: `orientdb`, `neo4j`, and `gremlin` (gremlin server).
You may also specify any class that implements `Spider\Drivers\DriverInterface` if you are [creating your own driver](create-driver.md). 
Each driver has its own connection properties.

You may also have multiple connections, but you must choose one to be the default.

Now that you have Spider setup, simply: 
```php
$spider = Spider\Spider::make();
```

To get a new spider with the default connection up and running. If you want a spider with a different connection:
```php
$spider = Spider\Spider::make('another_connection');
```

With that Spider, you can jump into the QueryBuilder:
```php
$user = $spider->select()->from('users')->where('username', 'jason')->first();
echo $user->username; // 'jason'
```
See [the query builder](command-builder.md) for details.

You can also use that Spider to get other query builders and connections from your configuration:
```php
$neoConnection = $spider->connection('neo4j'); // if you have a neo4j connection set it your config
$neoQueryBuilder = $spider->querybuilder('neo4j'); // if you have a neo4j connection set it your config
```

See [configuration](configuration.md) for other configuration options.

Spider is made up of lots of different parts (connections, querybuilders, managers). 
You may use each piece individually (look through the docs for more info).