<?php
namespace Spider\Test\Integration;

use Codeception\Specify;
use Michaels\Manager\IocManager;
use Spider\Spider;
use Spider\Test\Fixtures\OrientFixture;
use Spider\Test\Stubs\IocContainerStub;

class SpiderTest extends \PHPUnit_Framework_TestCase
{
    use Specify;

    protected $fullConfig;
    protected $connections;
    protected $integrations;
    protected $options;

    public function setup()
    {
        $this->connections = [
            'default' => 'orient',
            'orient' => [
                'driver' => 'Spider\Drivers\OrientDB\Driver',
                'hostname' => 'localhost',
                'port' => 2424,
                'username' => 'root',
                'password' => "root",
                'database' => 'modern_graph'
            ],
            'neo' => [
                'driver' => 'neo4j',
                'hostname' => 'localhost',
                'port' => 7474,
                'username' => "neo4j",
                'password' => "j4oen",
            ]
        ];

        $this->integrations = [
//            'events' => 'EventDispatcher',
//            'logger' => 'logging',
        ];

        $this->options = [
            'errors' => [
                'not_supported' => 'warning',
            ],
            'logging' => false,
        ];

        $this->fullConfig['connections'] = $this->connections;
        $this->fullConfig['integrations'] = $this->integrations;

        $this->fullConfig = array_merge($this->options, $this->fullConfig);
    }

    public function testConfigure()
    {
        $this->specify("it globally sets up via static `setup`", function () {
            Spider::setup($this->fullConfig);
            $actual = Spider::getSetup();

            $this->assertEquals($this->fullConfig, $actual, "failed to setup global configuration");
        });

        $this->specify("it sets default configuration", function () {
            $spider = new Spider();
            $actual = $spider->getDefaults();

            $this->assertEquals(Spider::getDefaults(), $actual, "failed to set defaults");
        });

        $this->specify("it configures an instance via constructor", function () {
            $spider = new Spider($this->fullConfig);
            $actual = $spider->getConfig();

            $this->assertEquals($this->fullConfig, $actual, "failed to setup global configuration");
        });

        $this->specify("it configures an instance via `configure`", function () {
            $spider = new Spider();
            $spider->configure($this->fullConfig);
            $actual = $spider->getConfig();

            $this->assertEquals($this->fullConfig, $actual, "failed to setup global configuration");
        });

        $this->specify("it merges with default configuration", function () {
            $config = [
                'logging' => 3,
                'errors' => [
                    'not_supported' => 'fail'
                ]
            ];
            $config['connections'] = $this->fullConfig['connections'];

            $spider = new Spider($config);
            $actual = $spider->getConfig();

            $expected = Spider::getDefaults();
            $expected['logging'] = 3;
            $expected['connections'] = $config['connections'];
            $expected['errors']['not_supported'] = 'fail';

            $this->assertEquals($expected, $actual, "failed to set defaults");
        });
    }

    public function testInstantiation()
    {
        $this->specify("it creates from static factory: default connection", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make();

            $this->assertInstanceOf('Spider\Spider', $spider, "failed to return a Spider");
            $this->assertInstanceOf('Spider\Commands\Query', $spider, "failed to return a Query Builder");
            $this->assertInstanceOf('Spider\Connections\ConnectionInterface', $spider->getConnection(), "invalid connection");
            $this->assertInstanceOf('Spider\Drivers\OrientDB\Driver', $spider->getDriver(), "failed to set driver");
            $this->assertEquals($this->fullConfig, $spider->getConfig(), "failed to setup configuration");
        });

        $this->specify("it creates from static factory: specific connection", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make('neo');

            $this->assertInstanceOf('Spider\Spider', $spider, "failed to return a Spider");
            $this->assertInstanceOf('Spider\Commands\Query', $spider, "failed to return a Query Builder");
            $this->assertInstanceOf('Spider\Connections\ConnectionInterface', $spider->getConnection(), "invalid connection");
            $this->assertInstanceOf('Spider\Drivers\Neo4J\Driver', $spider->getDriver(), "failed to set driver");
            $this->assertEquals($this->fullConfig, $spider->getConfig(), "failed to setup configuration");
        });

        $this->specify("it instantiates a new instance", function () {
            $spider = new Spider($this->fullConfig);
            $actual = $spider->getConfig();

            $this->assertEquals($this->fullConfig, $actual, "failed to setup global configuration");
        });
    }

    // This only tests that Spider sets up Query correctly.
    // Query methods are tested elsewhere
    public function testBasicQueryBuilder()
    {
        $fixture = new OrientFixture();
        $fixture->unload();
        $fixture->load();

        Spider::setup($this->fullConfig);
        $spider = Spider::make(); // orientdb by default

        $response = $spider->select()->all();

        $this->assertTrue(is_array($response), "failed to return an array");
        $this->assertCount(6, $response, "failed to return six records");
        $this->assertInstanceOf('Spider\Base\Collection', $response[0], "failed to return collections");

        $fixture->unload();
    }

    public function testFactoryBuilding()
    {
        $this->specify("it builds a new default connection", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make();

            $connection = $spider->connection();

            $this->assertInstanceOf('Spider\Connections\ConnectionInterface', $connection, "failed to return a connection");
            $this->assertEquals('Spider\Drivers\OrientDB\Driver', $connection->getDriverName(), "failed to return the correct connection");
        });

        $this->specify("it builds a new specific connection", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make();

            $connection = $spider->connection('neo');

            $this->assertInstanceOf('Spider\Connections\ConnectionInterface', $connection, "failed to return a connection");
            $this->assertEquals('Spider\Drivers\Neo4J\Driver', $connection->getDriverName(), "failed to return the correct connection");
        });

        $this->specify("it builds a new default query builder", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make();

            $query = $spider->querybuilder();

            $this->assertInstanceOf('Spider\Commands\Query', $query, "failed to return a query builder");
            $this->assertEquals('Spider\Drivers\OrientDB\Driver', $query->getConnection()->getDriverName(), "failed to return with correct connection");
        });

        $this->specify("it builds a new specific query builder", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make();

            $query = $spider->querybuilder('neo');

            $this->assertInstanceOf('Spider\Commands\Query', $query, "failed to return a query builder");
            $this->assertEquals('Spider\Drivers\Neo4J\Driver', $query->getConnection()->getDriverName(), "failed to return with correct connection");
        });
    }

    public function testExceptions()
    {
        $this->specify("it throws an exception if `make()ing with a non-string alias", function () {
            Spider::setup([
                'connections' => [
                    'orient' => []
                ]
            ]);
            Spider::make([]);
        }, ['throws' => 'InvalidArgumentException']);

        $this->specify("it throws an exception without a default connection", function () {
            Spider::setup([
                'connections' => [
                    'orient' => []
                ]
            ]);
            Spider::make();
        }, ['throws' => 'Spider\Exceptions\ConnectionNotFoundException']);

        $this->specify("it throws an exception without connection configuration", function () {
            Spider::make();
        }, ['throws' => 'Spider\Exceptions\ConnectionNotFoundException']);

        $this->specify("it throws an exception without a valid connection", function () {
            Spider::setup([
                'connections' => [
                    'default' => 'notexistant',
                    'does_exist' => [
                        'driver' => 'nope'
                    ]
                ]
            ]);
            Spider::make();
        }, ['throws' => 'Spider\Exceptions\ConnectionNotFoundException']);
    }

    public function testSwapIocContainer()
    {
        Spider::setup($this->fullConfig, new IocContainerStub());

        $spider = Spider::make();

        $this->assertInstanceOf('Spider\Test\Stubs\IocContainerStub', $spider->getDI(), "failed to swap ioc container");
    }

    /* This is also tested in the michaels/data-manager package */
    /* The standard IoC container has a lot more functionality. See michaels/data-manager */
    /* Tested here to ensure compatibility */
    public function testIocContainer()
    {
        $this->specify("it uses a string-based factory", function () {
            $config['connections'] = $this->connections;
            $config['integrations'] = [
                'test' => '\stdClass'
            ];
            Spider::setup($config);
            $spider = Spider::make();

            $actual = $spider->getDI()->fetch('test');

            $this->assertInstanceOf('\stdClass', $actual, "failed to produce from a string");
        });

        $this->specify("it uses a closure-based factory", function () {
            $config['connections'] = $this->connections;
            $config['integrations'] = [
                'test' => function () {
                    return new \stdClass();
                }
            ];
            Spider::setup($config);
            $spider = Spider::make();

            $actual = $spider->getDI()->fetch('test');

            $this->assertInstanceOf('\stdClass', $actual, "failed to produce from a string");
        });

        $this->specify("it uses an object-based factory", function () {
            $config['connections'] = $this->connections;
            $config['integrations'] = [
                'test' => new \stdClass(),
            ];
            Spider::setup($config);
            $spider = Spider::make();

            $actual = $spider->getDI()->fetch('test');

            $this->assertInstanceOf('\stdClass', $actual, "failed to produce from a string");
        });

        $this->specify("it uses an instance of the ioc container as a factory", function () {
            $di = new IocManager([
                'test' => new \stdClass(),
            ]);

            $config['connections'] = $this->connections;
            $config['integrations'] = [
                'test' => $di
            ];

            Spider::setup($config);
            $spider = Spider::make();

            $actual = $spider->getDI()->fetch('test');

            $this->assertInstanceOf('\stdClass', $actual, "failed to produce from a string");
        });
    }
}
