<?php

namespace frontend\components;

use common\components\AnalyticsQueue;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\Request;

class VisitorAnalytics implements BootstrapInterface
{
    public function bootstrap($app)
    {
        $app->on(Application::EVENT_AFTER_REQUEST, function () use ($app) {
            $this->record($app);
        });
    }

    private function record($app)
    {
        $request = $app->request;
        if (!$request instanceof Request || !$request->getIsGet() || $request->getIsAjax()) {
            return;
        }
        $route = (string) ($app->controller ? $app->controller->route : '');
        $status = (int) $app->response->statusCode;
        $userAgent = (string) $request->userAgent;
        if ($route === '' || strpos($route, 'admin/') === 0 || $status >= 400 || $this->isBot($userAgent)) {
            return;
        }

        try {
            $date = gmdate('Y-m-d');
            $path = '/' . ltrim((string) parse_url($request->url, PHP_URL_PATH), '/');
            $path = mb_substr($path === '/' ? '/' : rtrim($path, '/'), 0, 500);
            $country = $this->countryCode($request);
            $secret = trim((string) getenv('APP_ANALYTICS_KEY'));
            if ($secret === '') {
                throw new \RuntimeException('APP_ANALYTICS_KEY is not configured.');
            }
            $visitorHash = hash_hmac('sha256', $date . '|' . $request->userIP . '|' . $userAgent, $secret);

            AnalyticsQueue::fromEnvironment()->enqueue([
                'event_id' => bin2hex(random_bytes(16)),
                'date' => $date,
                'path' => $path,
                'country' => $country,
                'visitor_hash' => $visitorHash,
            ]);
        } catch (\Throwable $exception) {
            Yii::warning('Visitor statistics could not be recorded: ' . $exception->getMessage(), __METHOD__);
        }
    }

    private function countryCode(Request $request)
    {
        foreach (['CF-IPCountry', 'X-Country-Code'] as $header) {
            $code = strtoupper(trim((string) $request->headers->get($header)));
            if (preg_match('/^[A-Z]{2}$/', $code)) {
                return $code;
            }
        }
        return 'ZZ';
    }

    private function isBot($userAgent)
    {
        return $userAgent === '' || preg_match('/bot|crawl|spider|slurp|preview|monitor/i', $userAgent) === 1;
    }
}
