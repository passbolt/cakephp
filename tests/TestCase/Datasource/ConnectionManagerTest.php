<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource;

use BadMethodCallException;
use Cake\Core\Exception\CakeException;
use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Driver\Sqlserver;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceException;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use TestApp\Datasource\FakeConnection;
use TestPlugin\Datasource\TestSource;

/**
 * ConnectionManager Test
 */
class ConnectionManagerTest extends TestCase
{
    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        ConnectionManager::drop('test_variant');
        ConnectionManager::dropAlias('other_name');
        ConnectionManager::dropAlias('test2');
    }

    /**
     * Data provider for valid config data sets.
     *
     * @return array
     */
    public static function configProvider(): array
    {
        return [
            'Array of data using classname key.' => [[
                'className' => FakeConnection::class,
                'instance' => 'Sqlite',
                'database' => ':memory:',
            ]],
            'Direct instance' => [new FakeConnection()],
        ];
    }

    /**
     * Test the various valid config() calls.
     *
     * @param \Cake\Datasource\ConnectionInterface|array $settings
     */
    #[DataProvider('configProvider')]
    public function testConfigVariants($settings): void
    {
        $this->assertNotContains('test_variant', ConnectionManager::configured(), 'test_variant config should not exist.');
        ConnectionManager::setConfig('test_variant', $settings);

        $ds = ConnectionManager::get('test_variant');
        $this->assertInstanceOf(FakeConnection::class, $ds);
        $this->assertContains('test_variant', ConnectionManager::configured());
    }

    /**
     * Test invalid classes cause exceptions
     */
    public function testConfigInvalidOptions(): void
    {
        $this->expectException(MissingDatasourceException::class);
        ConnectionManager::setConfig('test_variant', [
            'className' => 'Herp\Derp',
        ]);
        ConnectionManager::get('test_variant');
    }

    /**
     * Test for errors on duplicate config.
     */
    public function testConfigDuplicateConfig(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot reconfigure existing key `test_variant`');
        $settings = [
            'className' => FakeConnection::class,
            'database' => ':memory:',
        ];
        ConnectionManager::setConfig('test_variant', $settings);
        ConnectionManager::setConfig('test_variant', $settings);
    }

    /**
     * Test get() failing on missing config.
     */
    public function testGetFailOnMissingConfig(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The datasource configuration `test_variant` was not found.');
        ConnectionManager::get('test_variant');
    }

    /**
     * Test loading configured connections.
     */
    public function testGet(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(empty($config), 'No test config, skipping');

        $ds = ConnectionManager::get('test');
        $this->assertSame($ds, ConnectionManager::get('test'));
        $this->assertInstanceOf(Connection::class, $ds);
        $this->assertSame('test', $ds->configName());
    }

    /**
     * Test loading connections without aliases
     */
    public function testGetNoAlias(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('The datasource configuration `other_name` was not found.');
        $config = ConnectionManager::getConfig('test');
        $this->skipIf(empty($config), 'No test config, skipping');

        ConnectionManager::alias('test', 'other_name');
        ConnectionManager::get('other_name', false);
    }

    /**
     * Test that configured() finds configured sources.
     */
    public function testConfigured(): void
    {
        ConnectionManager::setConfig('test_variant', [
            'className' => FakeConnection::class,
            'database' => ':memory:',
        ]);
        $results = ConnectionManager::configured();
        $this->assertContains('test_variant', $results);
    }

    /**
     * testGetPluginDataSource method
     */
    public function testGetPluginDataSource(): void
    {
        $this->loadPlugins(['TestPlugin']);
        $name = 'test_variant';
        $config = ['className' => 'TestPlugin.TestSource', 'foo' => 'bar'];
        ConnectionManager::setConfig($name, $config);
        $connection = ConnectionManager::get($name);

        $this->assertInstanceOf(TestSource::class, $connection);
        unset($config['className']);
        $this->assertSame($config + ['name' => 'test_variant'], $connection->config());
    }

    /**
     * Tests that a connection configuration can be deleted in runtime
     */
    public function testDrop(): void
    {
        ConnectionManager::setConfig('test_variant', [
            'className' => FakeConnection::class,
            'database' => ':memory:',
        ]);
        $result = ConnectionManager::configured();
        $this->assertContains('test_variant', $result);

        $this->assertTrue(ConnectionManager::drop('test_variant'));
        $result = ConnectionManager::configured();
        $this->assertNotContains('test_variant', $result);

        $this->assertFalse(ConnectionManager::drop('probably_does_not_exist'), 'Should return false on failure.');
    }

    public function testAliases(): void
    {
        $this->assertSame(['default' => 'test'], ConnectionManager::aliases());
        ConnectionManager::alias('test', 'test2');
        $this->assertSame(['default' => 'test', 'test2' => 'test'], ConnectionManager::aliases());
        ConnectionManager::dropAlias('test2');
        $this->assertSame(['default' => 'test'], ConnectionManager::aliases());
    }

    /**
     * Test aliasing connections.
     */
    public function testAlias(): void
    {
        ConnectionManager::setConfig('test_variant', [
            'className' => FakeConnection::class,
            'database' => ':memory:',
        ]);
        ConnectionManager::alias('test_variant', 'other_name');
        $result = ConnectionManager::get('test_variant');
        $this->assertSame($result, ConnectionManager::get('other_name'));
    }

    /**
     * provider for DSN strings.
     *
     * @return array
     */
    public static function dsnProvider(): array
    {
        return [
            'no user' => [
                'mysql://localhost:3306/database',
                [
                    'className' => Connection::class,
                    'driver' => Mysql::class,
                    'host' => 'localhost',
                    'database' => 'database',
                    'port' => 3306,
                    'scheme' => 'mysql',
                ],
            ],
            'subdomain host' => [
                'mysql://my.host-name.com:3306/database',
                [
                    'className' => Connection::class,
                    'driver' => Mysql::class,
                    'host' => 'my.host-name.com',
                    'database' => 'database',
                    'port' => 3306,
                    'scheme' => 'mysql',
                ],
            ],
            'user & pass' => [
                'mysql://root:secret@localhost:3306/database?log=1',
                [
                    'scheme' => 'mysql',
                    'className' => Connection::class,
                    'driver' => Mysql::class,
                    'host' => 'localhost',
                    'username' => 'root',
                    'password' => 'secret',
                    'port' => 3306,
                    'database' => 'database',
                    'log' => '1',
                ],
            ],
            'no password' => [
                'mysql://user@localhost:3306/database',
                [
                    'className' => Connection::class,
                    'driver' => Mysql::class,
                    'host' => 'localhost',
                    'database' => 'database',
                    'port' => 3306,
                    'scheme' => 'mysql',
                    'username' => 'user',
                ],
            ],
            'empty password' => [
                'mysql://user:@localhost:3306/database',
                [
                    'className' => Connection::class,
                    'driver' => Mysql::class,
                    'host' => 'localhost',
                    'database' => 'database',
                    'port' => 3306,
                    'scheme' => 'mysql',
                    'username' => 'user',
                    'password' => '',
                ],
            ],
            'sqlite memory' => [
                'sqlite:///:memory:',
                [
                    'className' => Connection::class,
                    'driver' => Sqlite::class,
                    'database' => ':memory:',
                    'scheme' => 'sqlite',
                ],
            ],
            'sqlite path' => [
                'sqlite:////absolute/path',
                [
                    'className' => Connection::class,
                    'driver' => Sqlite::class,
                    'database' => '/absolute/path',
                    'scheme' => 'sqlite',
                ],
            ],
            'sqlite database query' => [
                'sqlite:///?database=:memory:',
                [
                    'className' => Connection::class,
                    'driver' => Sqlite::class,
                    'database' => ':memory:',
                    'scheme' => 'sqlite',
                ],
            ],
            'sqlserver' => [
                'sqlserver://sa:Password12!@.\SQL2012SP1/cakephp?MultipleActiveResultSets=false',
                [
                    'className' => Connection::class,
                    'driver' => Sqlserver::class,
                    'host' => '.\SQL2012SP1',
                    'MultipleActiveResultSets' => false,
                    'password' => 'Password12!',
                    'database' => 'cakephp',
                    'scheme' => 'sqlserver',
                    'username' => 'sa',
                ],
            ],
            'sqllocaldb' => [
                'sqlserver://username:password@(localdb)\.\DeptSharedLocalDB/database',
                [
                    'className' => Connection::class,
                    'driver' => Sqlserver::class,
                    'host' => '(localdb)\.\DeptSharedLocalDB',
                    'password' => 'password',
                    'database' => 'database',
                    'scheme' => 'sqlserver',
                    'username' => 'username',
                ],
            ],
            'classname query arg' => [
                'mysql://localhost/database?className=Custom\Driver',
                [
                    'className' => Connection::class,
                    'database' => 'database',
                    'driver' => 'Custom\Driver',
                    'host' => 'localhost',
                    'scheme' => 'mysql',
                ],
            ],
            'classname and port' => [
                'mysql://localhost:3306/database?className=Custom\Driver',
                [
                    'className' => Connection::class,
                    'database' => 'database',
                    'driver' => 'Custom\Driver',
                    'host' => 'localhost',
                    'scheme' => 'mysql',
                    'port' => 3306,
                ],
            ],
            'custom connection class' => [
                'Cake\Database\Connection://localhost:3306/database?driver=Cake\Database\Driver\Mysql',
                [
                    'className' => Connection::class,
                    'database' => 'database',
                    'driver' => Mysql::class,
                    'host' => 'localhost',
                    'scheme' => Connection::class,
                    'port' => 3306,
                ],
            ],
            'complex password' => [
                'mysql://user:/?#][{}$%20@!@localhost:3306/database?log=1&quoteIdentifiers=1',
                [
                    'className' => Connection::class,
                    'database' => 'database',
                    'driver' => Mysql::class,
                    'host' => 'localhost',
                    'password' => '/?#][{}$%20@!',
                    'port' => 3306,
                    'scheme' => 'mysql',
                    'username' => 'user',
                    'log' => 1,
                    'quoteIdentifiers' => 1,
                ],
            ],
        ];
    }

    /**
     * Test parseDsn method.
     */
    #[DataProvider('dsnProvider')]
    public function testParseDsn(string $dsn, array $expected): void
    {
        $result = ConnectionManager::parseDsn($dsn);
        $this->assertEquals($expected, $result);
    }

    /**
     * Test parseDsn invalid.
     */
    public function testParseDsnInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The DSN string `bagof:nope` could not be parsed.');
        ConnectionManager::parseDsn('bagof:nope');
    }

    /**
     * Tests that directly setting an instance in a config, will not return a different
     * instance later on
     */
    public function testConfigWithObject(): void
    {
        $connection = new FakeConnection();
        ConnectionManager::setConfig('test_variant', $connection);
        $this->assertSame($connection, ConnectionManager::get('test_variant'));
    }

    /**
     * Tests configuring an instance with a callable
     */
    public function testConfigWithCallable(): void
    {
        $connection = new FakeConnection();
        $callable = function ($alias) use ($connection) {
            $this->assertSame('test_variant', $alias);

            return $connection;
        };

        ConnectionManager::setConfig('test_variant', $callable);
        $this->assertSame($connection, ConnectionManager::get('test_variant'));
    }

    /**
     * Tests that setting a config will also correctly set the name for the connection
     */
    public function testSetConfigName(): void
    {
        //Set with explicit name
        ConnectionManager::setConfig('test_variant', [
            'className' => FakeConnection::class,
            'database' => ':memory:',
        ]);
        $result = ConnectionManager::get('test_variant');
        $this->assertSame('test_variant', $result->configName());

        ConnectionManager::drop('test_variant');
        ConnectionManager::setConfig([
            'test_variant' => [
                'className' => FakeConnection::class,
                'database' => ':memory:',
            ],
        ]);
        $result = ConnectionManager::get('test_variant');
        $this->assertSame('test_variant', $result->configName());
    }
}
