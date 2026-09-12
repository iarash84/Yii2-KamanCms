<?php

namespace console\controllers;

use common\components\AnalyticsProcessor;
use common\components\AnalyticsQueue;
use yii\console\Controller;
use yii\console\ExitCode;

class AnalyticsController extends Controller
{
    public function actionProcess($once = false): int
    {
        $queue = AnalyticsQueue::fromEnvironment();
        $processor = new AnalyticsProcessor();
        $once = filter_var($once, FILTER_VALIDATE_BOOLEAN);

        do {
            $file = $queue->claim();
            if ($file !== null) {
                $count = $processor->processFile($file);
                $queue->complete($file);
                $this->stdout("Processed {$count} analytics events.\n");
            } elseif (!$once) {
                usleep(500000);
            }
        } while (!$once);

        return ExitCode::OK;
    }

    public function actionCleanup($retentionDays = 90): int
    {
        $retentionDays = (int) $retentionDays;
        if ($retentionDays < 1) {
            $this->stderr("retentionDays must be greater than zero.\n");
            return ExitCode::USAGE;
        }

        $deleted = (new AnalyticsProcessor())->cleanup($retentionDays);
        $this->stdout("Removed {$deleted} expired analytics markers.\n");
        return ExitCode::OK;
    }
}
