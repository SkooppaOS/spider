<?php
namespace Spider\Test\Integration;

use Codeception\Specify;
use Spider\Spider;
use Spider\Test\Fixtures\OrientFixture;

class SpiderTest extends \PHPUnit_Framework_TestCase
{
    use Specify;

    protected $fullConfig;

    public function setup()
    {
        $this->fullConfig = [
            'connections' => [
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
            ],
            'components' => [
                'events' => 'Events',
                'logger' => 'logging',
                'cache' => 'cache'
            ],

            // Optional
            'errors' => [
                'not_supported' => 'warning',
                'all' => 'warning'
            ],
            'logging' => false,
        ];
    }

    public function getSpiderConfigForTesting($spider)
    {
        $actual = $spider->getConfig();
        unset($actual['connections']['cache']);
        return $actual;
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
            $actual = $this->getSpiderConfigForTesting($spider);

            $this->assertEquals($this->fullConfig, $actual, "failed to setup global configuration");
        });

        $this->specify("it configures an instance via `configure`", function () {
            $spider = new Spider();
            $spider->configure($this->fullConfig);
            $actual = $this->getSpiderConfigForTesting($spider);

            $this->assertEquals($this->fullConfig, $actual, "failed to setup global configuration");
        });

        $this->specify("it merges with default configuration", function () {
            $spider = new Spider([
                'logging' => 3,
                'errors' => [
                    'all' => 'fail'
                ]
            ]);
            $actual = $spider->getConfig();

            $expected = Spider::getDefaults();
            $expected['logging'] = 3;
            $expected['connections'] = [];
            $expected['errors']['all'] = 'fail';

            $this->assertEquals($expected, $actual, "failed to set defaults");
        });
    }

    public function testInstantiation()
    {
        $this->specify("it creates from static factory: default connection", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make();

            $this->assertInstanceOf('Spider\Spider', $spider, "failed to return a Spider");
            $this->assertEquals($this->fullConfig, $this->getSpiderConfigForTesting($spider), "failed to setup configuration");

            // With the default connection
            $this->assertInstanceOf(
                'Spider\Connections\ConnectionInterface',
                $connection = $spider->getConnection(),
                "failed to set a valid connection"
            );

            $this->assertInstanceOf(
                'Spider\Drivers\OrientDB\Driver',
                $connection->getDriver(),
                'failed to set the correct connection'
            );
        });

        $this->specify("it creates from static factory: specific connection", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make('orient');

            $this->assertInstanceOf('Spider\Spider', $spider, "failed to return a Spider");
            $this->assertEquals($this->fullConfig, $this->getSpiderConfigForTesting($spider), "failed to setup configuration");

            // With the default connection
            $this->assertInstanceOf(
                'Spider\Connections\ConnectionInterface',
                $connection = $spider->getConnection(),
                "failed to set a valid connection"
            );

            $this->assertInstanceOf(
                'Spider\Drivers\OrientDB\Driver',
                $connection->getDriver(),
                'failed to set the correct connection'
            );
        });

        $this->specify("it instantiates a new instance", function () {
            $spider = new Spider($this->fullConfig);
            $actual = $this->getSpiderConfigForTesting($spider);

            $this->assertEquals($this->fullConfig, $actual, "failed to setup global configuration");
        });
    }

    public function testConnectionSetup()
    {
        $this->specify("it sets up orient db connection correctly", function () {
            Spider::setup($this->fullConfig);
            $spider = Spider::make('orient');

            $this->assertInstanceOf('Spider\Spider', $spider, "failed to return a Spider");
            $this->assertInstanceOf('Spider\Commands\Query', $spider, "failed to return a Query Builder");
            $this->assertInstanceOf('Spider\Connections\ConnectionInterface', $spider->getConnection(), "invalid connection");
            $this->assertInstanceOf('Spider\Drivers\OrientDB\Driver', $spider->getDriver(), "failed to set driver");
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
        $spider = Spider::make('orient');

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
}
