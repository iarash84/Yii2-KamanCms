<?php

namespace tests\unit;

use common\installer\InstallerWorkflow;
use PHPUnit\Framework\TestCase;

class InstallerWorkflowTest extends TestCase
{
    public function testStepNormalizationPreservesOnlyValidAndFinishedSteps(): void
    {
        self::assertSame(1, InstallerWorkflow::normalizeStep(-10));
        self::assertSame(6, InstallerWorkflow::normalizeStep(6));
        self::assertSame(9, InstallerWorkflow::normalizeStep(9));
        self::assertSame(9, InstallerWorkflow::normalizeStep(10));
    }

    public function testEachRequirementPageUsesItsOwnChecks(): void
    {
        self::assertTrue(InstallerWorkflow::checksPassed(['PHP' => true, 'PDO' => true]));
        self::assertFalse(InstallerWorkflow::checksPassed(['Writable runtime' => false]));
    }

    public function testGoingBackOnlyClearsDataOwnedByEarlierSteps(): void
    {
        $session = [
            'installer_database' => ['name' => 'cms'],
            'installer_admin' => ['username' => 'admin'],
        ];
        self::assertSame(7, InstallerWorkflow::goBack(8, $session));
        self::assertArrayHasKey('installer_database', $session);
        self::assertArrayNotHasKey('installer_admin', $session);

        self::assertSame(3, InstallerWorkflow::goBack(4, $session));
        self::assertArrayNotHasKey('installer_database', $session);
    }

    public function testRestartRequiresExplicitConfirmation(): void
    {
        self::assertFalse(InstallerWorkflow::restartConfirmed(null));
        self::assertFalse(InstallerWorkflow::restartConfirmed('0'));
        self::assertTrue(InstallerWorkflow::restartConfirmed('1'));
    }

    public function testHttpsDetectionDoesNotTreatApacheOffAsSecure(): void
    {
        self::assertFalse(InstallerWorkflow::isHttps(null));
        self::assertFalse(InstallerWorkflow::isHttps('off'));
        self::assertFalse(InstallerWorkflow::isHttps('0'));
        self::assertTrue(InstallerWorkflow::isHttps('on'));
        self::assertTrue(InstallerWorkflow::isHttps('1'));
    }

    public function testWebInstallerProvidesAjaxProgressAndAStandardFormFallback(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/frontend/web/install.php');

        self::assertStringContainsString('data-installer-form', $source);
        self::assertStringContainsString('data-installer-loading', $source);
        self::assertStringContainsString("fetch(form.action || window.location.href", $source);
        self::assertStringContainsString("connect-src 'self'", $source);
        self::assertStringContainsString('<form method="post" data-installer-form>', $source);
    }
}
