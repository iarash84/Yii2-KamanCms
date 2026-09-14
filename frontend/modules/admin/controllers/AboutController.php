<?php

namespace frontend\modules\admin\controllers;

use Yii;
use yii\web\Controller;

class AboutController extends Controller
{
    public function actionIndex()
    {
        $systemInfo = Yii::$app->params['systemInfo'];

        return $this->render('index', [
            'systemInfo' => $systemInfo,
            'releaseNotes' => $this->releaseNotes(),
            'technicalInfo' => [
                'نوع سامانه' => 'سیستم مدیریت محتوای چندزبانه',
                'زبان‌های قابل استفاده' => 'فارسی و انگلیسی',
                'پشتیبانی از دستگاه‌ها' => 'رایانه، تبلت و موبایل',
                'وضعیت سامانه' => 'آماده استفاده',
            ],
        ]);
    }

    private function releaseNotes(): array
    {
        $path = dirname(Yii::getAlias('@app')) . DIRECTORY_SEPARATOR . 'CHANGELOG.md';
        $contents = is_file($path) ? (string) file_get_contents($path) : '';
        if ($contents === '') {
            return [];
        }

        $sections = [];
        $sectionTitle = '';
        $sectionLines = [];
        foreach (explode("\n", $contents) as $line) {
            $trimmedLine = trim($line);
            if (strncmp($trimmedLine, '## ', 3) === 0) {
                if ($sectionTitle !== '') {
                    $sections[] = ['title' => $sectionTitle, 'content' => implode("\n", $sectionLines)];
                }
                $sectionTitle = trim(substr($trimmedLine, 3));
                $sectionLines = [];
                continue;
            }
            if ($sectionTitle !== '') {
                $sectionLines[] = $line;
            }
        }
        if ($sectionTitle !== '') {
            $sections[] = ['title' => $sectionTitle, 'content' => implode("\n", $sectionLines)];
        }

        // Prefer a populated unreleased section; otherwise use the newest version section.
        $selected = '';
        $newestVersion = '';
        foreach ($sections as $section) {
            $title = $section['title'];
            if (preg_match('/^v\d+\.\d+\.\d+/', $title)) {
                $newestVersion = $section['content'];
            } elseif ($this->hasNotes($section['content'])) {
                $selected = $section['content'];
            }
        }
        if ($selected === '') {
            $selected = $newestVersion;
        }

        $notes = [];
        $category = null;
        foreach (explode("\n", $selected) as $line) {
            $trimmedLine = trim($line);
            if (strncmp($trimmedLine, '### ', 4) === 0) {
                $category = trim(substr($trimmedLine, 4));
                $notes[$category] = [];
            } elseif ($category !== null) {
                $bullet = ltrim($line);
                if (strncmp($bullet, '- ', 2) === 0) {
                    $notes[$category][] = trim(substr($bullet, 2));
                }
            }
        }

        return $notes;
    }

    private function hasNotes(string $section): bool
    {
        foreach (explode("\n", $section) as $line) {
            if (strncmp(ltrim($line), '- ', 2) === 0 && trim(substr(ltrim($line), 2)) !== '') {
                return true;
            }
        }

        return false;
    }

    private function databaseVersion(): string
    {
        try {
            $schema = Yii::$app->db->getSchema();
            $driver = Yii::$app->db->getDriverName();
            $version = method_exists($schema, 'getServerVersion') ? $schema->getServerVersion() : '';
            return trim(ucfirst($driver) . ($version ? ' ' . $version : ''));
        } catch (\Throwable $exception) {
            Yii::warning('Database version could not be determined: ' . $exception->getMessage(), __METHOD__);
            return 'نامشخص';
        }
    }
}
