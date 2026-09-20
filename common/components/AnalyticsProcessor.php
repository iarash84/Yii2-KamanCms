<?php

namespace common\components;

use Yii;
use yii\db\Expression;
use yii\db\IntegrityException;

class AnalyticsProcessor
{
    public function processFile(string $file): int
    {
        $processed = 0;
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Could not open the analytics queue file.');
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $event = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $this->processEvent($event);
                $processed++;
            }
        } finally {
            fclose($handle);
        }

        return $processed;
    }

    public function cleanup(int $retentionDays): int
    {
        $cutoff = gmdate('Y-m-d', strtotime('-' . $retentionDays . ' days'));
        return Yii::$app->db->createCommand()->delete('{{%visitor_unique}}', [
            '<', 'visit_date', $cutoff,
        ])->execute();
    }

    private function processEvent(array $event): void
    {
        foreach (['event_id', 'date', 'path', 'country', 'visitor_hash'] as $key) {
            if (!isset($event[$key]) || !is_string($event[$key]) || $event[$key] === '') {
                throw new \UnexpectedValueException('Invalid analytics event.');
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$this->insertUnique($event['date'], $event['visitor_hash'], 'event', $event['event_id'])) {
                $transaction->rollBack();
                return;
            }

            $siteUnique = $this->insertUnique(
                $event['date'],
                $event['visitor_hash'],
                'site',
                '*'
            );
            $this->increment('{{%visitor_daily}}', ['visit_date' => $event['date']], $siteUnique);
            $this->increment('{{%visitor_country_daily}}', [
                'visit_date' => $event['date'], 'country_code' => $event['country'],
            ], $this->insertUnique(
                $event['date'],
                $event['visitor_hash'],
                'country',
                $event['country']
            ));
            $this->increment('{{%visitor_page_daily}}', [
                'visit_date' => $event['date'], 'path' => $event['path'],
            ], $this->insertUnique(
                $event['date'],
                $event['visitor_hash'],
                'page',
                $event['path']
            ));
            $transaction->commit();
        } catch (\Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $exception;
        }
    }

    private function insertUnique(string $date, string $hash, string $type, string $value): bool
    {
        try {
            Yii::$app->db->createCommand()->insert('{{%visitor_unique}}', [
                'visit_date' => $date,
                'visitor_hash' => $hash,
                'dimension_type' => $type,
                'dimension_value' => $value,
            ])->execute();
            return true;
        } catch (IntegrityException $exception) {
            return false;
        }
    }

    private function increment(string $table, array $condition, bool $unique): void
    {
        $values = array_merge($condition, [
            'page_views' => 1,
            'visitors' => $unique ? 1 : 0,
        ]);
        Yii::$app->db->createCommand()->upsert($table, $values, [
            'page_views' => new Expression('[[page_views]] + 1'),
            'visitors' => new Expression('[[visitors]] + ' . ($unique ? '1' : '0')),
        ])->execute();
    }
}
