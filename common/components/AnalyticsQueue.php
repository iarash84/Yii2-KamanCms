<?php

namespace common\components;

use Yii;

class AnalyticsQueue
{
    private const QUEUE_FILE = 'events.jsonl';
    private const LOCK_FILE = '.lock';

    public function __construct(private string $directory)
    {
    }

    public static function fromEnvironment(): self
    {
        $directory = trim((string) getenv('APP_ANALYTICS_QUEUE_PATH'));
        if ($directory === '') {
            $directory = Yii::getAlias('@storage') . '/analytics-queue';
        } elseif ($directory[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $directory)) {
            $directory = dirname(Yii::getAlias('@storage')) . '/' . ltrim($directory, '/\\\\');
        }
        return new self($directory);
    }

    public function enqueue(array $event): void
    {
        $this->ensureDirectory();
        $lock = $this->openLock();
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Could not lock the analytics queue.');
            }
            $payload = json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            if (file_put_contents($this->queuePath(), $payload, FILE_APPEND | LOCK_EX) === false) {
                throw new \RuntimeException('Could not write to the analytics queue.');
            }
            flock($lock, LOCK_UN);
        } finally {
            fclose($lock);
        }
    }

    public function claim(): ?string
    {
        $this->ensureDirectory();
        $lock = $this->openLock();
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Could not lock the analytics queue.');
            }
            $queuePath = $this->queuePath();
            if (!is_file($queuePath) || filesize($queuePath) === 0) {
                flock($lock, LOCK_UN);
                return null;
            }
            $processingPath = $this->directory . '/events-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.processing';
            if (!rename($queuePath, $processingPath)) {
                throw new \RuntimeException('Could not claim the analytics queue.');
            }
            flock($lock, LOCK_UN);
            return $processingPath;
        } finally {
            fclose($lock);
        }
    }

    public function complete(string $processingPath): void
    {
        if (is_file($processingPath) && !unlink($processingPath)) {
            throw new \RuntimeException('Could not remove the processed analytics queue file.');
        }
    }

    private function ensureDirectory(): void
    {
        if (is_dir($this->directory)) {
            return;
        }
        if (!mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Could not create the analytics queue directory.');
        }
    }

    private function openLock()
    {
        $lock = fopen($this->directory . '/' . self::LOCK_FILE, 'c');
        if ($lock === false) {
            throw new \RuntimeException('Could not open the analytics queue lock.');
        }
        return $lock;
    }

    private function queuePath(): string
    {
        return $this->directory . '/' . self::QUEUE_FILE;
    }
}
