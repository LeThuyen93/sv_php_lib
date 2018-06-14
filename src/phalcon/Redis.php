<?php
/**
 * Created by PhpStorm.
 * User: quandt
 * Date: 6/6/18
 * Time: 5:25 PM
 */

namespace CodeBase;

use Predis\Client;

/**
 * Redis helper
 */
class Redis
{
    private $client;

    /**
     * Call this method to get singleton
     */
    public static function shared()
    {
        static $instance = null;
        if( $instance === null )
        {
            $instance = new static();
        }
        return $instance;
    }

    /**
     * Make constructor private, so nobody can call "new Class".
     */
    private function __construct() {}

    /**
     * Make clone magic method private, so nobody can clone instance.
     */
    private function __clone() {}

    /**
     * Make sleep magic method private, so nobody can serialize instance.
     */
    private function __sleep() {}

    /**
     * Make wakeup magic method private, so nobody can unserialize instance.
     */
    private function __wakeup() {}


    /**
     * Initialize Redis sentinel client
     * @param array $servers is redis server config array
     * @param string $serviceName is service name
     * @param array $options sentinel options
     * @return void
     */
    public function initializeSentinel($servers = [
                                           'scheme' => 'tcp',
                                           'host'   => '127.0.0.1',
                                           'port'   => 6379,
                                       ], $serviceName = null, $options = null) {
        $sentinelOpts = null;

        // Initialize sentinel options
        if ($serviceName != null) {
            $sentinelOpts = array(
                'replication' => 'sentinel',
                'service' => $serviceName
            );
        }

        // Merge sentinel options
        if ($options != null) {
            if ($sentinelOpts == null) {
                $sentinelOpts = $options;
            }
            else {
                $sentinelOpts = array_merge($sentinelOpts, $options);
            }
        }

        // Initialize redis client
        if ($sentinelOpts == null) {
            $this->client = new Client($servers);
        }
        else {
            $this->client = new Client($servers, $sentinelOpts);
        }
    }

    /**
     * Initialize Redis cluster client
     * @param array $servers redis host
     * @param array $options cluster options
     * @return void
     */
    public function initializeCluster($servers = [
        'scheme' => 'tcp',
        'host'   => '127.0.0.1',
        'port'   => 6379,
    ], $options = null) {

        $clusterOptions = array(
            'cluster' => 'redis'
        );

        if ($options != null) {
            $clusterOptions = array_merge($clusterOptions, $options);
        }

        // Initialize redis client
        $this->client = new Client($servers, $clusterOptions);
    }

    /**
     * Initialize Redis single client
     * @param string $host redis host
     * @param integer $port redis port
     * @param array $options redis options
     * @return void
     */
    public function initializeSingle($host = '127.0.0.1', $port = 6379, $options = null) {
        $redisOptions = [
            'scheme' => 'tcp',
            'host'   => $host,
            'port'   => $port,
        ];

        if ($options != null) {
            $redisOptions = array_merge($redisOptions, $options);
        }
        $this->client = new Client($redisOptions);
    }

    /**
     * Set value for key
     * @param string $key key name
     * @param mixed $value value
     * @return void
     */
    public function set($key, $value, $timeout = null) {
        if ($this->client) {
            $this->client->set($key, $value);
            if ($timeout != null) {
                $this->client->setTimeout($key, $timeout);
            }
        }
    }

    /**
     * Get value from key
     * @param string $key key name
     * @param mixed $defaultValue default value
     * @return mixed
     */
    public function get($key, $defaultValue) {
        if ($this->client) {
            return $this->client->get($key, $defaultValue);
        }
        return $defaultValue;
    }


    /**
     * Check whether a key is existed
     * @param string $key key name
     * @return boolean
     */
    public function hasKey($key) {
        if ($this->client) {
            return $this->client->exists($key);
        }
        return false;
    }

    /**
     * Opens the underlying connection and connects to the server.
     */
    public function connect() {
        if ($this->client) {
            $this->client->connect();
        }
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     */
    public function disconnect() {
        if ($this->client) {
            $this->client->disconnect();
        }
    }

    /**
     * Closes the underlying connection and disconnects from the server.
     *
     * This is the same as `Client::disconnect()` as it does not actually send
     * the `QUIT` command to Redis, but simply closes the connection.
     */
    public function quit() {
        if ($this->client) {
            $this->client->quit();
        }
    }

    /**
     * Returns the current state of the underlying connection.
     *
     * @return bool
     */
    public function isConnected() {
        if ($this->client) {
            $this->client->isConnected();
        }
    }

    /**
     * Returns the current connection.
     *
     * @return mixed
     */
    public function getConnection() {
        if ($this->client) {
            return $this->client->getConnection();
        }
        return null;
    }

    /**
     * Returns the redis client.
     *
     * @return Client
     */
    public function getClient() {
        return $this->client;
    }

}