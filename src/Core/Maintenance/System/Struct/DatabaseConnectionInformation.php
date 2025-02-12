<?php declare(strict_types=1);

namespace Shopware\Core\Maintenance\System\Struct;

use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Maintenance\MaintenanceException;

/**
 * @internal
 */
#[Package('framework')]
class DatabaseConnectionInformation extends Struct
{
    protected string $hostname = '';

    protected int $port = 3306;

    protected ?string $username = null;

    protected ?string $password = null;

    protected string $databaseName = '';

    protected ?string $sslCaPath = null;

    protected ?string $sslCertPath = null;

    protected ?string $sslCertKeyPath = null;

    protected ?bool $sslDontVerifyServerCert = null;

    public function assign(array $options): DatabaseConnectionInformation
    {
        // We pass request values directly to the assign method,
        // so we need to cast them to the correct type first
        if (isset($options['port'])) {
            $options['port'] = (int) $options['port'];
        }
        if (isset($options['sslDontVerifyServerCert'])) {
            $options['sslDontVerifyServerCert'] = (bool) $options['sslDontVerifyServerCert'];
        }

        parent::assign($options);

        return $this;
    }

    public static function fromEnv(): self
    {
        $dsn = trim((string) EnvironmentHelper::getVariable('DATABASE_URL', getenv('DATABASE_URL')));
        if ($dsn === '') {
            throw MaintenanceException::environmentVariableNotDefined('DATABASE_URL');
        }

        $params = parse_url($dsn);
        if ($params === false) {
            throw MaintenanceException::environmentVariableNotValid('DATABASE_URL', $dsn, 'Not a valid DSN');
        }

        foreach ($params as $param => $value) {
            if (!\is_string($value)) {
                continue;
            }

            $params[$param] = rawurldecode($value);
        }

        $path = (string) ($params['path'] ?? '/');
        $dbName = substr($path, 1);
        if (!isset($params['scheme'], $params['host']) || trim($dbName) === '') {
            throw MaintenanceException::environmentVariableNotValid('DATABASE_URL', $dsn, 'Not a valid DSN');
        }

        return (new self())->assign([
            'hostname' => $params['host'],
            'port' => (int) ($params['port'] ?? '3306'),
            'username' => $params['user'] ?? null,
            'password' => $params['pass'] ?? null,
            'databaseName' => $dbName,
            'sslCaPath' => EnvironmentHelper::getVariable('DATABASE_SSL_CA'),
            'sslCertPath' => EnvironmentHelper::getVariable('DATABASE_SSL_CERT'),
            'sslCertKeyPath' => EnvironmentHelper::getVariable('DATABASE_SSL_KEY'),
            'sslDontVerifyServerCert' => EnvironmentHelper::getVariable('DATABASE_SSL_DONT_VERIFY_SERVER_CERT'),
        ]);
    }

    /**
     * @return array{host: string, port: int, charset: string, driver: 'pdo_mysql', dbname?: string, user?: string, password?: string, driverOptions: array<int, string|bool>}
     */
    public function toDBALParameters(bool $withoutDatabaseName = false): array
    {
        $parameters = [
            'host' => $this->hostname,
            'port' => $this->port,
            'charset' => 'utf8mb4',
            'driver' => 'pdo_mysql',
            'driverOptions' => [
                \PDO::ATTR_STRINGIFY_FETCHES => true,
            ],
        ];

        if (!$withoutDatabaseName) {
            $parameters['dbname'] = $this->databaseName;
        }

        if ($this->username !== null) {
            $parameters['user'] = $this->username;
        }

        if ($this->password !== null) {
            $parameters['password'] = $this->password;
        }

        if ($this->sslCaPath) {
            $parameters['driverOptions'][\PDO::MYSQL_ATTR_SSL_CA] = $this->sslCaPath;
        }

        if ($this->sslCertPath) {
            $parameters['driverOptions'][\PDO::MYSQL_ATTR_SSL_CERT] = $this->sslCertPath;
        }

        if ($this->sslCertKeyPath) {
            $parameters['driverOptions'][\PDO::MYSQL_ATTR_SSL_KEY] = $this->sslCertKeyPath;
        }

        if ($this->sslDontVerifyServerCert) {
            $parameters['driverOptions'][\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        return $parameters;
    }

    public function asDsn(bool $withoutDatabaseName = false): string
    {
        $dsn = \sprintf(
            'mysql://%s%s:%d',
            $this->username ? ($this->username . ($this->password ? ':' . rawurlencode($this->password) : '') . '@') : '',
            $this->hostname,
            $this->port
        );

        if (!$withoutDatabaseName) {
            $dsn .= '/' . $this->databaseName;
        }

        return $dsn;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getSslCaPath(): ?string
    {
        return $this->sslCaPath;
    }

    public function getSslCertPath(): ?string
    {
        return $this->sslCertPath;
    }

    public function getSslCertKeyPath(): ?string
    {
        return $this->sslCertKeyPath;
    }

    public function getSslDontVerifyServerCert(): ?bool
    {
        return $this->sslDontVerifyServerCert;
    }

    public function hasAdvancedSetting(): bool
    {
        return $this->port !== 3306 || $this->sslCaPath || $this->sslCertPath || $this->sslCertKeyPath || $this->sslDontVerifyServerCert !== null;
    }

    public function validate(): void
    {
        if ($this->hostname === '') {
            throw MaintenanceException::dbConnectionParameterMissing('hostname');
        }

        if ($this->databaseName === '') {
            throw MaintenanceException::dbConnectionParameterMissing('databaseName');
        }

        if ($this->username === null || $this->username === '') {
            throw MaintenanceException::dbConnectionParameterMissing('username');
        }
    }
}
