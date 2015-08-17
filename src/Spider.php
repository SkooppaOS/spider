<?php
namespace Spider;

use Michaels\Manager\IocManager;
use Michaels\Manager\Manager as BaseManager;
use Spider\Commands\Query;
use Spider\Connections\Manager as ConnectionManager;
use Spider\Exceptions\ConnectionNotFoundException;

class Spider extends Query
{
    /**
     * Global setup configuration
     * [
     *      // Connection Manifest
     *      'connection' => [
     *          'default' => $alias,
     *          'connection_one' => [
     *              driver => $driver class or alias
     *              // Other credentials
     *          ]
     *      ],
     *
     *      // Extensible and replaceable components
     *      components => [
     *          'cache' => factory
     *          'logger' => factory
     *          'events' => factory
     *      ]
     *
     *      // General configuration
     *      'errors' => [
     *          'all' => 'fatal|quiet|silent'
     *          'not_supported' => 'fatal|quiet|silent'
     *      ]
     * ]
     * @var array
     */
    protected static $setup = [];

    /** @var array Defaults for global setup configuration, minus connections */
    protected static $defaults = [
        'components' => [
            'events' => false,
            'cache' => false,
            'logger' => false,
        ],
        'errors' => [
            'all' => 'silent',
            'not_supported' => 'silent'
        ],
        'logging' => false,
    ];

    /** @var  BaseManager Configuration for a specific instance */
    protected $config;

    /** @var  IoCManager Dependency Injection Manager */
    protected $di;

    /* Static Factory and Global Configuration */
    /**
     * Setup global configuration
     * @param array $setup
     */
    public static function setup(array $setup = [])
    {
        static::$setup = $setup;
    }

    public static function getSetup()
    {
        return static::$setup;
    }

    public static function make($connection = null)
    {
        return new static(static::$setup, $connection);
    }

    public static function getDefaults()
    {
        return self::$defaults;
    }

    /* Instance Public API: Initialization */
    /**
     * Builds new Spider Instance which extends QueryBuilder
     * Holds active connection based on configuration
     *
     * @param array $config
     * @param null $connection alias of connection to set
     */
    public function __construct(array $config = [], $connection = null)
    {
        // Setup dependencies
        $this->connections = new ConnectionManager();
        $this->di = new IocManager();
        $this->config = new BaseManager();

        // Configure Instance
        if (!empty($config)) {
            $this->configure($config, $connection);
        }
    }

    /**
     * Configures current instance
     * @param array $config
     * @param null $connection
     * @throws ConnectionNotFoundException
     */
    public function configure(array $config, $connection = null)
    {
        // Set options with defaults
        if (empty($config)) {
            $config = $this->getDefaults();
        } else {
            /* Set Defaults Where Needed */
            /* ToDo: Merging with defaults should be refactored */
            foreach ($this->getDefaults() as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (!isset($config[$key][$k])) {
                            $config[$key][$k] = $v;
                        }
                    }
                }
                if (!isset($config[$key])) {
                    $config[$key] = $value;
                }
            }
        }


        /* Connection Manager and Connection */
        if (isset($config['connections'])) {
            // Set the connection manifest
            $this->connections->reset($config['connections']);

            // Set the current connection for the Query Builder
            parent::__construct(
                $this->connections->fetch($connection)
            );

            unset($config['connections']);
        } else {
            throw new ConnectionNotFoundException("Spider cannot be instantiated without a connection");
        }

        /* Components for the IoC Manager */
        if (isset($config['components'])) {

            $this->di->initDI($config['components']);
            unset($config['components']);
        }

        /* General Configuration */
        $this->config->reset($config);
    }

    /* Instance Public API: Factories */
    public function connection($alias = null)
    {
        return $this->connections->make($alias);
    }

    public function querybuilder($connection = null)
    {
        return new Query($this->connections->make($connection));
    }

    /* Instance Public API: Getters and Setters */
    /* Inherits get|setConnection() */
    /**
     * Gets the current driver
     * @return Drivers\DriverInterface
     */
    public function getDriver()
    {
        return $this->connection->getDriver();
    }

    /**
     * Gets the current general configuration as an array
     * @return array
     */
    public function getConfig()
    {
        $config = $this->config->all();
        $config['components'] = $this->di->getIocManifest();

        $config['connections'] = $this->connections->all();
        unset($config['connections']['cache']);

        return $config;
    }
}
