<?php

namespace frontend\components;

use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\web\Response;

class SecurityHeaders implements BootstrapInterface
{
    public function bootstrap($app)
    {
        Event::on(Response::class, Response::EVENT_BEFORE_SEND, function ($event) use ($app) {
            $headers = $event->sender->headers;
            $headers->remove('X-Powered-By');
            $headers->set('X-Content-Type-Options', 'nosniff');
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            $contentSecurityPolicy = "default-src 'self'; img-src 'self' data: https:; "
                . "style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; "
                . "font-src 'self' data: https:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
            $headers->set('Content-Security-Policy', $contentSecurityPolicy);
            $headers->set('Content-Security-Policy-Report-Only', $contentSecurityPolicy);
            if ($app->request->isSecureConnection) {
                $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        });
    }
}
