<?php
namespace Spider;

use Michaels\Manager\Contracts\IocContainerInterface;
use Michaels\Manager\IocManager;
use Michaels\Manager\Manager as BaseManager;
use Spider\Commands\Query;
use Spider\Connections\Manager as ConnectionManager;
use Spider\Exceptions\ConnectionNotFoundException;
use Symfony\Component\Config\Definition\Exception\Exception;

class Spider extends Query
{
    /**
     * Global setup configuration
     * [
     *      // Connection Manifest
     *      'connections' => [
     *          'default' => $alias,
     *          'connection_one' => [
     *              driver => $driver class or alias
     *              // Other credentials
     *          ]
     *      ],
     *
     *      // General configuration
     *      'errors' => [
     *          'not_supported' => 'fatal|quiet|silent'
     *      ]
     *      'errors' => 'fatal' // for all errors
     * ]
     * @var array
     */
    protected static $setup = [];

    /** @var array Defaults for global setup configuration, minus connections */
    protected static $defaults = [
        'errors' => [
            'not_supported' => 'silent'
        ],
    ];

    /** @var  BaseManager Configuration for a specific instance */
    protected $config;

    /* Static Factory and Global Configuration */
    /**
     * Setup global configuration
     * @param array $setup
     * @param IocContainerInterface $di
     */
    public static function setup(array $setup = [], IocContainerInterface $di = null)
    {
        static::$setup = $setup;
    }

    /**
     * Returns the static setup
     * @return array
     */
    public static function getSetup()
    {
        return static::$setup;
    }

    /**
     * Builds a new spider based on default or provided connection alias
     * @param null $connectionAlias
     * @return static
     */
    public static function make($connectionAlias = null)
    {
        if (!is_string($connectionAlias) && !is_null($connectionAlias)) {
            throw new \InvalidArgumentException("Spider::make() only accepts an alias for an already set connection");
        }

        return new static(static::$setup, $connectionAlias);
    }

    /**
     * Returns static defaults (for testing)
     * @return array
     */
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
     * @param IocContainerInterface $di
     * @throws ConnectionNotFoundException
     */
    public function __construct(array $config = [], $connection = null)
    {
        // Setup dependencies
        $this->connections = new ConnectionManager();
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
            foreach ($this->getDefaults() as $key => $value) {
                if (!isset($config[$key])) {
                    $config[$key] = $value;
                } elseif (is_array($value)) {
                    $config[$key] = array_merge($this->getDefaults()[$key], $config[$key]);
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

        /* General Configuration */
        $this->config->reset($config);
    }

    /**
     * Adds a connection
     * @param $name
     * @param array $details
     * @return $this
     */
    public function addConnection($name, array $details)
    {
        $this->connections->add($name, $details);
        return $this;
    }

    /* Instance Public API: Factories */
    /**
     * Produces a new connection from set credentials
     * @param null $alias
     * @return Connections\Connection
     * @throws ConnectionNotFoundException
     */
    public function connection($alias = null)
    {
        return $this->connections->make($alias);
    }

    /**
     * Produces a new query builder from set credentials
     * @param null $connection
     * @return Query
     * @throws ConnectionNotFoundException
     */
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
        $config['connections'] = $this->connections->all();
        unset($config['connections']['cache']);

        return $config;
    }
}
