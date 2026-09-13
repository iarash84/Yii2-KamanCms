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
                'PHP' => PHP_VERSION,
                'Yii Framework' => Yii::getVersion(),
                'Database' => $this->databaseVersion(),
                'محیط اجرا' => YII_ENV,
            ],
        ]);
    }

    private function releaseNotes(): array
    {
        $path = dirname(Yii::getAlias('@app')) . DIRECTORY_SEPARATOR . 'CHANGELOG.md';
        $contents = is_file($path) ? (string) file_get_contents($path) : '';
        $sections = preg_split('/^##\s+/m', $contents, -1, PREG_SPLIT_NO_EMPTY);
        $unreleased = '';

        foreach ($sections as $section) {
            if (str_starts_with(trim($section), 'منتشرنشده')) {
                $unreleased = $section;
                break;
            }
        }

        $notes = [];
        $category = null;
        foreach (preg_split('/\R/', $unreleased) as $line) {
            if (preg_match('/^###\s+(.+)$/u', trim($line), $matches)) {
                $category = trim($matches[1]);
                $notes[$category] = [];
            } elseif ($category !== null && preg_match('/^\s*-\s+(.+)$/u', $line, $matches)) {
                $notes[$category][] = trim($matches[1]);
            }
        }

        return $notes;
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
