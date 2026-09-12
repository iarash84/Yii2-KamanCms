<?php

namespace common\installer;

use PDO;
use RuntimeException;

class InstallerService
{
    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?: dirname(__DIR__, 2);
    }

    public function lockPath(): string
    {
        $configured = trim((string) getenv('INSTALL_LOCK_FILE'));

        return $configured !== '' ? $configured : $this->root . '/.install.lock';
    }

    public function isInstalled(): bool
    {
        return is_file($this->lockPath());
    }

    public function requirements(): array
    {
        $checks = [
            'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'Mbstring' => extension_loaded('mbstring'),
            'OpenSSL' => extension_loaded('openssl'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'Process execution' => function_exists('proc_open'),
            'PHP CLI' => $this->phpCliBinary() !== null,
        ];
        foreach ($this->writablePaths() as $path) {
            $checks['Writable: ' . $this->relative($path)] = is_dir($path) && is_writable($path);
        }
        $environmentFile = $this->environmentPath();
        $configurationDirectory = dirname($environmentFile);
        $checks['Writable: project configuration'] = is_dir($configurationDirectory)
            && is_writable($configurationDirectory)
            && (!is_file($environmentFile) || is_writable($environmentFile));
        return $checks;
    }

    public function isReady(): bool
    {
        return !in_array(false, $this->requirements(), true);
    }

    public function ensureDatabase(array $database): void
    {
        $this->validateDatabase($database);
        $name = $this->databaseName($database['name'] ?? '');
        $pdo = new PDO($this->serverDsn($database), (string) ($database['user'] ?? ''), (string) ($database['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->pdo($database)->query('SELECT 1')->fetchColumn();
    }

    public function databaseExists(array $database): bool
    {
        $this->validateDatabase($database);
        $name = $this->databaseName($database['name'] ?? '');
        $pdo = new PDO($this->serverDsn($database), (string) ($database['user'] ?? ''), (string) ($database['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $statement = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :name');
        $statement->execute(['name' => $name]);

        return $statement->fetchColumn() !== false;
    }

    public function databaseSchemaExists(array $database): bool
    {
        $pdo = $this->pdo($database);
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES '
            . 'WHERE TABLE_SCHEMA = :database AND TABLE_NAME IN (\'migration\', \'user\')'
        );
        $statement->execute(['database' => $this->databaseName($database['name'] ?? '')]);

        return (int) $statement->fetchColumn() === 2;
    }

    public function migrate(array $database): string
    {
        return $this->runYii(['migrate', '--interactive=0'], $this->databaseEnvironment($database));
    }

    public function createAdministrator(array $database, array $admin): string
    {
        $this->validateAdministrator($admin);
        return $this->runYii(['seed', '0'], array_merge($this->databaseEnvironment($database), [
            'ADMIN_USERNAME' => (string) $admin['username'],
            'ADMIN_EMAIL' => (string) $admin['email'],
            'ADMIN_PASSWORD' => (string) $admin['password'],
        ]));
    }

    public function configureSite(array $database, array $site, array $admin): void
    {
        $pdo = $this->pdo($database);
        $user = $pdo->prepare('SELECT id FROM user WHERE username = :username LIMIT 1');
        $user->execute(['username' => $admin['username']]);
        $userId = (int) $user->fetchColumn();
        if (!$userId) {
            throw new RuntimeException('Administrator account was not found after installation.');
        }
        $values = ['CompanyName' => trim((string) $site['name']), 'Address' => trim((string) ($site['address'] ?? ''))];
        foreach ($values as $type => $content) {
            $statement = $pdo->prepare(
                'INSERT INTO site_setting (user_id,type,content) VALUES (:user_id,:type,:content) '
                . 'ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),content=VALUES(content)'
            );
            $statement->execute(['content' => $content, 'user_id' => $userId, 'type' => $type]);
        }
    }

    public function writeEnvironment(array $database, array $site): void
    {
        $values = array_merge([
            'YII_ENV' => 'prod', 'YII_DEBUG' => '0',
            'APP_LANGUAGE' => $site['language'] ?? 'fa',
            'ADMIN_LANGUAGE' => $site['admin_language'] ?? $site['language'] ?? 'fa',
            'APP_URL' => $site['url'] ?? '',
            'APP_COOKIE_VALIDATION_KEY' => bin2hex(random_bytes(32)),
            'APP_DATA_ENCRYPTION_KEY' => bin2hex(random_bytes(32)),
            'APP_ANALYTICS_KEY' => bin2hex(random_bytes(32)),
        ], $this->databaseEnvironment($database));
        $lines = ["# Generated by the application installer.\n"];
        foreach ($values as $name => $value) {
            $lines[] = $name . '=' . $this->quoteEnvironmentValue((string) $value) . "\n";
        }
        $target = $this->environmentPath();
        $temporary = $target . '.installing';
        $backup = $target . '.installer-backup';
        if (file_put_contents($temporary, implode('', $lines), LOCK_EX) === false) {
            throw new RuntimeException('The local environment file could not be written.');
        }
        if (is_file($target) && !rename($target, $backup)) {
            @unlink($temporary);
            throw new RuntimeException('The local environment file could not be written.');
        }
        if (!rename($temporary, $target)) {
            if (is_file($backup)) {
                @rename($backup, $target);
            }
            @unlink($temporary);
            throw new RuntimeException('The local environment file could not be written.');
        }
        @unlink($backup);
    }

    public function lock(array $site): void
    {
        $payload = json_encode(['installed_at' => gmdate(DATE_ATOM), 'language' => $site['language'] ?? 'fa'], JSON_PRETTY_PRINT);
        if (file_put_contents($this->lockPath(), $payload . "\n", LOCK_EX) === false) {
            throw new RuntimeException('The installer lock file could not be created.');
        }
    }

    public function validateAdministrator(array $admin): void
    {
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', (string) ($admin['username'] ?? ''))) {
            throw new RuntimeException('Administrator username is invalid.');
        }
        if (!filter_var($admin['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Administrator email is invalid.');
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{12,72}$/', (string) ($admin['password'] ?? ''))) {
            throw new RuntimeException('Administrator password must contain upper and lower case letters, a number and a symbol, and be at least 12 characters.');
        }
    }

    public function validateDatabase(array $database): void
    {
        $host = trim((string) ($database['host'] ?? ''));
        $port = filter_var($database['port'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        $user = trim((string) ($database['user'] ?? ''));
        $this->databaseName((string) ($database['name'] ?? ''));
        if ($host === '' || preg_match('/^[a-zA-Z0-9.-]+$/', $host) !== 1) {
            throw new RuntimeException('Database host is invalid.');
        }
        if ($port === false) {
            throw new RuntimeException('Database port must be between 1 and 65535.');
        }
        if ($user === '' || mb_strlen($user) > 128) {
            throw new RuntimeException('Database user is invalid.');
        }
    }

    public function phpCliBinary(): ?string
    {
        $configured = trim((string) getenv('PHP_CLI_BINARY'));
        $executable = PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php';
        $loadedConfiguration = php_ini_loaded_file();
        $extensionDirectory = (string) ini_get('extension_dir');
        $candidates = array_filter([
            $configured,
            $loadedConfiguration ? dirname($loadedConfiguration) . DIRECTORY_SEPARATOR . $executable : null,
            $extensionDirectory !== '' ? dirname($extensionDirectory) . DIRECTORY_SEPARATOR . $executable : null,
            PHP_BINDIR . DIRECTORY_SEPARATOR . $executable,
            PHP_SAPI === 'cli' ? PHP_BINARY : null,
        ]);
        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate) && (PHP_OS_FAMILY === 'Windows' || is_executable($candidate))) {
                return $candidate;
            }
        }

        return null;
    }

    private function runYii(array $arguments, array $environment): string
    {
        $binary = $this->phpCliBinary();
        if ($binary === null) {
            throw new RuntimeException('PHP CLI was not found. Set PHP_CLI_BINARY to the full path of the PHP CLI executable.');
        }
        $command = array_merge([$binary, $this->root . '/yii'], $arguments);
        $outputFile = tempnam($this->root . '/console/runtime', 'installer-out-');
        $errorFile = tempnam($this->root . '/console/runtime', 'installer-err-');
        if ($outputFile === false || $errorFile === false) {
            if ($outputFile !== false) {
                @unlink($outputFile);
            }
            if ($errorFile !== false) {
                @unlink($errorFile);
            }
            throw new RuntimeException('The installer could not create temporary command output files.');
        }
        $environmentVariables = getenv();
        $environmentVariables = is_array($environmentVariables) ? $environmentVariables : [];
        $process = proc_open($command, [
            1 => ['file', $outputFile, 'w'],
            2 => ['file', $errorFile, 'w'],
        ], $pipes, $this->root, array_merge($environmentVariables, $environment, [
            'YII_ENV' => 'prod', 'YII_DEBUG' => '0',
        ]));
        if (!is_resource($process)) {
            @unlink($outputFile);
            @unlink($errorFile);
            throw new RuntimeException('The installation command could not be started.');
        }
        $exitCode = proc_close($process);
        $output = (string) file_get_contents($outputFile);
        $error = (string) file_get_contents($errorFile);
        @unlink($outputFile);
        @unlink($errorFile);
        if ($exitCode !== 0) {
            throw new RuntimeException(trim($error ?: $output) ?: 'The installation command failed.');
        }
        return trim($output);
    }

    private function pdo(array $database): PDO
    {
        return new PDO($this->serverDsn($database) . ';dbname=' . $this->databaseName($database['name'] ?? ''), (string) ($database['user'] ?? ''), (string) ($database['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private function serverDsn(array $database): string
    {
        return 'mysql:host=' . ($database['host'] ?? '127.0.0.1') . ';port=' . (int) ($database['port'] ?? 3306) . ';charset=utf8mb4';
    }

    private function databaseEnvironment(array $database): array
    {
        return ['DB_HOST' => $database['host'], 'DB_PORT' => (string) $database['port'], 'DB_NAME' => $database['name'], 'DB_USER' => $database['user'], 'DB_PASSWORD' => $database['password']];
    }

    private function databaseName(string $name): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new RuntimeException('Database name may only contain letters, numbers and underscores.');
        }
        return $name;
    }

    private function writablePaths(): array
    {
        return [$this->root . '/frontend/runtime', $this->root . '/console/runtime', $this->root . '/frontend/web/assets', $this->root . '/frontend/web/upload', $this->root . '/storage/resumes', $this->root . '/storage/analytics-queue'];
    }

    private function environmentPath(): string
    {
        $configured = trim((string) getenv('APP_ENV_FILE'));

        return $configured !== '' ? $configured : $this->root . '/.env';
    }

    private function quoteEnvironmentValue(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        return '"' . addcslashes($value, "\\\"") . '"';
    }

    private function relative(string $path): string
    {
        return ltrim(str_replace('\\', '/', substr($path, strlen($this->root))), '/');
    }
}
