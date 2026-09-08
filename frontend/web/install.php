<?php

declare(strict_types=1);

use common\installer\InstallerService;
use common\installer\InstallerWorkflow;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/common/config/env.php';
require $root . '/common/installer/InstallerService.php';
require $root . '/common/installer/InstallerWorkflow.php';

$installer = new InstallerService($root);
$scriptNonce = base64_encode(random_bytes(18));
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'nonce-{$scriptNonce}'; connect-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header('Referrer-Policy: no-referrer');
if ($installer->isInstalled()) {
    http_response_code(403);
    exit('Installer is locked.');
}

$isHttps = InstallerWorkflow::isHttps($_SERVER['HTTPS'] ?? null);
ini_set('session.use_strict_mode', '1');
session_name('website_installer');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => $isHttps,
    'path' => '/',
]);
session_start();
@set_time_limit(600);
if (!isset($_SESSION['installer_csrf'])) {
    $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
}

$step = InstallerWorkflow::normalizeStep($_SESSION['installer_step'] ?? 1);
$language = $_SESSION['installer_language'] ?? 'fa';
$messages = [
    'fa' => ['title' => 'نصب Yii2-KamanCms', 'next' => 'ادامه', 'back' => 'بازگشت', 'restart' => 'شروع مجدد', 'restart_confirm' => 'تمام اطلاعات واردشده در فرایند نصب پاک می‌شود. آیا مطمئن هستید؟', 'restart_required' => 'برای شروع مجدد باید پاک شدن اطلاعات فرایند نصب را تأیید کنید.', 'retry' => 'تلاش دوباره', 'welcome' => 'به نصب‌کننده Yii2-KamanCms خوش آمدید', 'intro' => 'این راهنما تنظیمات، دیتابیس و حساب مدیر اصلی را به‌صورت امن ایجاد می‌کند.', 'language' => 'زبان نصب', 'requirements' => 'بررسی پیش‌نیازهای PHP', 'permissions' => 'بررسی دسترسی پوشه‌ها', 'database' => 'تنظیمات دیتابیس', 'connection' => 'آزمایش اتصال دیتابیس', 'migrations' => 'ایجاد ساختار دیتابیس', 'admin' => 'حساب مدیر اصلی', 'site' => 'تنظیمات سایت', 'finish' => 'نصب با موفقیت کامل شد', 'login' => 'ورود به پنل مدیریت', 'run' => 'اجرای migrationها', 'test' => 'آزمایش اتصال', 'save' => 'تکمیل نصب', 'passed' => 'موفق', 'failed' => 'ناموفق', 'loading' => 'در حال پردازش…', 'network_error' => 'ارتباط با نصب‌کننده برقرار نشد. اتصال را بررسی و دوباره تلاش کنید.'],
    'en' => ['title' => 'Yii2-KamanCms installation', 'next' => 'Continue', 'back' => 'Back', 'restart' => 'Start over', 'restart_confirm' => 'All information entered during installation will be cleared. Are you sure?', 'restart_required' => 'You must confirm clearing the installation progress before starting over.', 'retry' => 'Try again', 'welcome' => 'Welcome to the Yii2-KamanCms installer', 'intro' => 'This wizard securely creates the configuration, database schema and primary administrator.', 'language' => 'Installer language', 'requirements' => 'PHP requirements', 'permissions' => 'Directory permissions', 'database' => 'Database settings', 'connection' => 'Test database connection', 'migrations' => 'Create database schema', 'admin' => 'Primary administrator', 'site' => 'Site settings', 'finish' => 'Installation completed successfully', 'login' => 'Sign in to administration', 'run' => 'Run migrations', 'test' => 'Test connection', 'save' => 'Complete installation', 'passed' => 'Passed', 'failed' => 'Failed', 'loading' => 'Processing…', 'network_error' => 'The installer could not be reached. Check your connection and try again.'],
];
$t = static function (string $key) use (&$language, $messages): string {
    return $messages[$language][$key] ?? $key;
};
$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$requirements = $installer->requirements();
$phpChecks = array_filter($requirements, static fn ($key) => strpos($key, 'Writable:') !== 0, ARRAY_FILTER_USE_KEY);
$permissionChecks = array_filter($requirements, static fn ($key) => strpos($key, 'Writable:') === 0, ARRAY_FILTER_USE_KEY);
$wizardActions = static function (string $primary, bool $disabled = false) use ($t, $escape): string {
    return '<div class="actions">'
        . '<input type="hidden" name="restart_confirmed" value="1">'
        . '<button class="button button-secondary button-restart" type="submit" name="action" value="restart" formnovalidate '
        . 'data-installer-restart data-confirm="' . $escape($t('restart_confirm')) . '">' . $escape($t('restart')) . '</button>'
        . '<button class="button button-secondary" type="submit" name="action" value="back" formnovalidate>' . $escape($t('back')) . '</button>'
        . '<button class="button" type="submit" name="action" value="next"' . ($disabled ? ' disabled' : '') . '>' . $escape($primary) . '</button>'
        . '</div>';
};
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['installer_csrf'], (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request token.');
    }
    try {
        $action = (string) ($_POST['action'] ?? 'next');
        if (!in_array($action, ['next', 'back', 'restart'], true)) {
            throw new RuntimeException('Invalid installer action.');
        }
        if ($action === 'restart') {
            if (!InstallerWorkflow::restartConfirmed($_POST['restart_confirmed'] ?? null)) {
                throw new RuntimeException($t('restart_required'));
            }
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['installer_csrf'] = bin2hex(random_bytes(32));
            $language = 'fa';
            $step = 1;
        } elseif ($action === 'back') {
            $step = InstallerWorkflow::goBack($step, $_SESSION);
        } elseif ($step === 1) {
            session_regenerate_id(true);
            $language = in_array($_POST['language'] ?? '', ['fa', 'en'], true) ? $_POST['language'] : 'fa';
            $_SESSION['installer_language'] = $language;
            $step = 2;
        } elseif ($step === 2 && InstallerWorkflow::checksPassed($phpChecks)) {
            $step = 3;
        } elseif ($step === 3 && InstallerWorkflow::checksPassed($permissionChecks)) {
            $step = 4;
        } elseif ($step === 4) {
            $password = (string) ($_POST['password'] ?? '');
            if ($password === '' && isset($_SESSION['installer_database']['password'])) {
                $password = (string) $_SESSION['installer_database']['password'];
            }
            $database = [
                'host' => trim((string) ($_POST['host'] ?? '')), 'port' => (int) ($_POST['port'] ?? 0),
                'name' => trim((string) ($_POST['name'] ?? '')), 'user' => trim((string) ($_POST['user'] ?? '')),
                'password' => $password,
            ];
            $installer->validateDatabase($database);
            $_SESSION['installer_database'] = $database;
            $step = 5;
        } elseif ($step === 5) {
            $installer->ensureDatabase($_SESSION['installer_database']);
            $step = 6;
        } elseif ($step === 6) {
            $installer->migrate($_SESSION['installer_database']);
            $step = 7;
        } elseif ($step === 7) {
            $admin = ['username' => trim((string) $_POST['username']), 'email' => trim((string) $_POST['email']), 'password' => (string) $_POST['password']];
            $_SESSION['installer_admin_draft'] = ['username' => $admin['username'], 'email' => $admin['email']];
            $installer->validateAdministrator($admin);
            $installer->createAdministrator($_SESSION['installer_database'], $admin);
            $_SESSION['installer_admin'] = $admin;
            $step = 8;
        } elseif ($step === 8) {
            $site = ['name' => trim((string) $_POST['name']), 'address' => trim((string) ($_POST['address'] ?? '')), 'url' => trim((string) $_POST['url']), 'language' => in_array($_POST['language'] ?? '', ['fa', 'en'], true) ? $_POST['language'] : 'fa', 'admin_language' => in_array($_POST['admin_language'] ?? '', ['fa', 'en'], true) ? $_POST['admin_language'] : 'fa'];
            $_SESSION['installer_site_draft'] = $site;
            $siteScheme = strtolower((string) parse_url($site['url'], PHP_URL_SCHEME));
            if ($site['name'] === '' || !filter_var($site['url'], FILTER_VALIDATE_URL) || !in_array($siteScheme, ['http', 'https'], true)) {
                throw new RuntimeException('Site name and a valid URL are required.');
            }
            $installer->configureSite($_SESSION['installer_database'], $site, $_SESSION['installer_admin']);
            $installer->writeEnvironment($_SESSION['installer_database'], $site);
            $installer->lock($site);
            $_SESSION = ['installer_step' => InstallerWorkflow::FINISHED_STEP, 'installer_language' => $language];
            $step = InstallerWorkflow::FINISHED_STEP;
        }
        $_SESSION['installer_step'] = $step;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

// A browser session can outlive the project files. Do not trust an advanced
// wizard step when the database it depends on has since been removed.
if ($step >= 6 && isset($_SESSION['installer_database'])) {
    try {
        if (!$installer->databaseExists($_SESSION['installer_database'])) {
            $step = 4;
            unset($_SESSION['installer_admin']);
            $_SESSION['installer_step'] = $step;
        }
    } catch (Throwable $exception) {
        // Connection errors are reported by the relevant database step. They
        // must not silently discard values the user may need to correct.
    }
}
if ($step >= 7 && isset($_SESSION['installer_database'])) {
    try {
        if (!$installer->databaseSchemaExists($_SESSION['installer_database'])) {
            $step = 6;
            unset($_SESSION['installer_admin']);
            $_SESSION['installer_step'] = $step;
        }
    } catch (Throwable $exception) {
        // The migration step will show the actionable connection/schema error.
    }
}
if ($step >= 5 && !isset($_SESSION['installer_database'])) {
    $step = 4;
    $_SESSION['installer_step'] = $step;
}
if ($step >= 8 && !isset($_SESSION['installer_admin'])) {
    $step = 7;
    $_SESSION['installer_step'] = $step;
}

$direction = $language === 'fa' ? 'rtl' : 'ltr';
?><!doctype html>
<html lang="<?= $escape($language) ?>" dir="<?= $direction ?>">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $escape($t('title')) ?></title>
<style>:root{color-scheme:light;--bg:#f4f6fb;--surface:#fff;--text:#172033;--muted:#68758a;--primary:#4f5fd7;--border:#e1e6ef;--ok:#188064;--bad:#bd4057}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 15% 10%,#e5e8ff,transparent 28%),var(--bg);color:var(--text);font-family:system-ui,"Segoe UI",sans-serif;line-height:1.7}.shell{width:min(52rem,calc(100% - 2rem));margin:4vh auto}.brand{display:flex;align-items:center;gap:.7rem;margin-block-end:1rem;font-weight:800}.mark{display:grid;width:2.5rem;height:2.5rem;place-items:center;border-radius:.7rem;background:var(--primary);color:#fff}.card{position:relative;padding:clamp(1.25rem,5vw,3rem);overflow:hidden;border:1px solid var(--border);border-radius:1.25rem;background:var(--surface);box-shadow:0 22px 65px rgba(23,32,51,.1);transition:opacity .2s ease,transform .2s ease}.card.is-entering{opacity:0;transform:translateY(.6rem)}.card.is-loading> :not(.installer-loading){opacity:.42;pointer-events:none}.steps{display:flex;gap:.35rem;margin-block-end:2rem}.steps i{height:.35rem;flex:1;border-radius:1rem;background:var(--border);transition:background-color .25s ease}.steps i.done{background:var(--primary)}h1{margin:.2rem 0 1rem;font-size:clamp(1.8rem,5vw,2.7rem);line-height:1.2}.muted{color:var(--muted)}label{display:grid;gap:.4rem;margin-block:1rem;font-weight:700}input,select{width:100%;min-height:3rem;padding:.65rem .8rem;border:1px solid var(--border);border-radius:.65rem;font:inherit;background:#fff}input:focus,select:focus{outline:3px solid #dfe3ff;border-color:var(--primary)}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 1rem}.checks{display:grid;gap:.65rem;margin-block:1.5rem}.check{display:flex;justify-content:space-between;gap:1rem;padding:.75rem;border-bottom:1px solid var(--border)}.ok{color:var(--ok)}.bad,.error{color:var(--bad)}.error{padding:1rem;border-radius:.65rem;background:#fbecef}.actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.75rem;margin-block-start:2rem}.button{display:inline-flex;min-height:2.8rem;align-items:center;justify-content:center;padding:.6rem 1.2rem;border:1px solid transparent;border-radius:.65rem;background:var(--primary);color:#fff;font:inherit;font-weight:800;text-decoration:none;cursor:pointer}.button-secondary{border-color:var(--border);background:var(--surface);color:var(--text)}.button-restart{margin-inline-end:auto;color:var(--bad)}.button[disabled]{opacity:.45;cursor:not-allowed}.installer-loading{position:absolute;inset:0;z-index:5;display:grid;place-content:center;justify-items:center;gap:.8rem;padding:2rem;color:var(--primary);background:rgba(255,255,255,.88);text-align:center;backdrop-filter:blur(3px)}.installer-loading[hidden]{display:none}.installer-spinner{width:2.75rem;height:2.75rem;border:.25rem solid #dfe3ff;border-block-start-color:var(--primary);border-radius:50%;animation:installer-spin .75s linear infinite}.ajax-error{margin-block-end:1rem}@keyframes installer-spin{to{transform:rotate(1turn)}}@media(max-width:38rem){.shell{margin:1rem auto}.grid{grid-template-columns:1fr}.card{padding:1.25rem}.actions .button{width:100%}.button-restart{margin-inline-end:0}}@media(prefers-reduced-motion:reduce){.card{transition:none}.installer-spinner{animation-duration:1.5s}}</style></head>
<body><main class="shell" data-installer-shell data-loading-label="<?= $escape($t('loading')) ?>" data-network-error="<?= $escape($t('network_error')) ?>"><div class="brand"><span class="mark">B</span><?= $escape($t('title')) ?></div><section class="card" data-installer-card><div class="installer-loading" data-installer-loading role="status" aria-live="polite" hidden><span class="installer-spinner" aria-hidden="true"></span><strong data-installer-loading-text><?= $escape($t('loading')) ?></strong></div><div class="steps"><?php for ($i=1;$i<=InstallerWorkflow::FINISHED_STEP;$i++): ?><i class="<?= $i <= $step ? 'done' : '' ?>"></i><?php endfor; ?></div>
<?php if ($error): ?><p class="error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
<?php if ($step === 1): ?><h1><?= $escape($t('welcome')) ?></h1><p class="muted"><?= $escape($t('intro')) ?></p><form method="post" data-installer-form><input type="hidden" name="_csrf" value="<?= $escape($_SESSION['installer_csrf']) ?>"><label><?= $escape($t('language')) ?><select name="language"><option value="fa">فارسی</option><option value="en">English</option></select></label><div class="actions"><button class="button"><?= $escape($t('next')) ?></button></div></form>
<?php elseif (in_array($step,[2,3],true)): $checks=$step===2?$phpChecks:$permissionChecks; ?><h1><?= $escape($t($step===2?'requirements':'permissions')) ?></h1><div class="checks"><?php foreach($checks as $label=>$passed): ?><div class="check"><span><?= $escape($label) ?></span><strong class="<?= $passed?'ok':'bad' ?>"><?= $escape($t($passed?'passed':'failed')) ?></strong></div><?php endforeach; ?></div><form method="post" data-installer-form><input type="hidden" name="_csrf" value="<?= $escape($_SESSION['installer_csrf']) ?>"><?= $wizardActions($t('next'), in_array(false,$checks,true)) ?></form>
<?php elseif ($step === 4): $database=$_SESSION['installer_database']??[]; ?><h1><?= $escape($t('database')) ?></h1><form method="post" data-installer-form><input type="hidden" name="_csrf" value="<?= $escape($_SESSION['installer_csrf']) ?>"><div class="grid"><label>Host<input name="host" value="<?= $escape($database['host']??'127.0.0.1') ?>" required></label><label>Port<input name="port" type="number" value="<?= $escape($database['port']??3306) ?>" required></label><label>Database<input name="name" value="<?= $escape($database['name']??'yii2_kamancms') ?>" pattern="[A-Za-z0-9_]+" required></label><label>User<input name="user" value="<?= $escape($database['user']??'root') ?>" required></label></div><label>Password<input name="password" type="password" autocomplete="new-password"><?php if(isset($database['password'])): ?><small class="muted">Leave blank to keep the saved password.</small><?php endif; ?></label><?= $wizardActions($t('next')) ?></form>
<?php elseif ($step === 5): ?><h1><?= $escape($t('connection')) ?></h1><p class="muted">Database: <?= $escape($_SESSION['installer_database']['name'] ?? '') ?></p><form method="post" data-installer-form><input type="hidden" name="_csrf" value="<?= $escape($_SESSION['installer_csrf']) ?>"><?= $wizardActions($t('test')) ?></form>
<?php elseif ($step === 6): ?><h1><?= $escape($t('migrations')) ?></h1><p class="muted">Yii migrations create all tables, indexes and RBAC data. You can safely retry this step after an error.</p><form method="post" data-installer-form><input type="hidden" name="_csrf" value="<?= $escape($_SESSION['installer_csrf']) ?>"><?= $wizardActions($t('run')) ?></form>
<?php elseif ($step === 7): $adminDraft=$_SESSION['installer_admin_draft']??[]; ?><h1><?= $escape($t('admin')) ?></h1><form method="post" data-installer-form><input type="hidden" name="_csrf" value="<?= $escape($_SESSION['installer_csrf']) ?>"><label>Username<input name="username" value="<?= $escape($adminDraft['username']??'admin') ?>" minlength="3" required></label><label>Email<input name="email" type="email" value="<?= $escape($adminDraft['email']??'') ?>" required></label><label>Password<input name="password" type="password" minlength="12" autocomplete="new-password" required><small class="muted">12+ characters with uppercase, lowercase, number and symbol</small></label><?= $wizardActions($t('next')) ?></form>
<?php elseif ($step === 8): $siteDraft=$_SESSION['installer_site_draft']??[]; $defaultUrl=($isHttps?'https':'http').'://'.($_SERVER['HTTP_HOST']??'127.0.0.1:8080'); ?><h1><?= $escape($t('site')) ?></h1><form method="post" data-installer-form><input type="hidden" name="_csrf" value="<?= $escape($_SESSION['installer_csrf']) ?>"><label>Site name<input name="name" value="<?= $escape($siteDraft['name']??'') ?>" required></label><label>Site URL<input name="url" type="url" value="<?= $escape($siteDraft['url']??$defaultUrl) ?>" required></label><label>Address<input name="address" value="<?= $escape($siteDraft['address']??'') ?>"></label><div class="grid"><label>Site language<select name="language"><option value="fa" <?= ($siteDraft['language']??'fa')==='fa'?'selected':'' ?>>فارسی</option><option value="en" <?= ($siteDraft['language']??'fa')==='en'?'selected':'' ?>>English</option></select></label><label>Admin language<select name="admin_language"><option value="fa" <?= ($siteDraft['admin_language']??'fa')==='fa'?'selected':'' ?>>فارسی</option><option value="en" <?= ($siteDraft['admin_language']??'fa')==='en'?'selected':'' ?>>English</option></select></label></div><?= $wizardActions($t('save')) ?></form>
<?php else: ?><h1><?= $escape($t('finish')) ?></h1><p class="muted">The installer is now locked. Remove <code>.install.lock</code> manually only when an intentional reinstall is required.</p><div class="actions"><a class="button" href="/<?= $escape($language) ?>/login"><?= $escape($t('login')) ?></a></div><?php endif; ?>
</section></main>
<script nonce="<?= $escape($scriptNonce) ?>">
(() => {
    const shell = document.querySelector('[data-installer-shell]');
    if (!shell || !window.fetch || !window.DOMParser) return;

    let busy = false;
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-installer-form]');
        if (!form) return;
        event.preventDefault();
        if (busy) return;

        const submitter = event.submitter;
        if (submitter?.matches('[data-installer-restart]')) {
            if (!window.confirm(submitter.dataset.confirm || '')) return;
            const confirmation = form.elements.namedItem('restart_confirmed');
            if (confirmation) confirmation.value = '1';
        }

        const card = form.closest('[data-installer-card]');
        const loading = card?.querySelector('[data-installer-loading]');
        const loadingText = loading?.querySelector('[data-installer-loading-text]');
        busy = true;
        card?.classList.add('is-loading');
        card?.setAttribute('aria-busy', 'true');
        if (loadingText) loadingText.textContent = shell.dataset.loadingLabel || 'Processing…';
        if (loading) loading.hidden = false;

        try {
            const payload = new FormData(form);
            if (submitter?.name) payload.set(submitter.name, submitter.value);
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: payload,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            });
            if (!response.ok) throw new Error('Installer request failed');

            const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextCard = nextDocument.querySelector('[data-installer-card]');
            const nextShell = nextDocument.querySelector('[data-installer-shell]');
            if (!nextCard || !nextShell) throw new Error('Invalid installer response');

            document.title = nextDocument.title;
            document.documentElement.lang = nextDocument.documentElement.lang;
            document.documentElement.dir = nextDocument.documentElement.dir;
            shell.dataset.loadingLabel = nextShell.dataset.loadingLabel || '';
            shell.dataset.networkError = nextShell.dataset.networkError || '';

            const currentBrand = shell.querySelector('.brand');
            const nextBrand = nextShell.querySelector('.brand');
            if (currentBrand && nextBrand) currentBrand.replaceWith(document.importNode(nextBrand, true));

            const importedCard = document.importNode(nextCard, true);
            importedCard.classList.add('is-entering');
            card.replaceWith(importedCard);
            requestAnimationFrame(() => importedCard.classList.remove('is-entering'));
            const heading = importedCard.querySelector('h1');
            if (heading) {
                heading.tabIndex = -1;
                heading.focus({preventScroll: true});
            }
        } catch (error) {
            if (loading) loading.hidden = true;
            card?.classList.remove('is-loading');
            card?.removeAttribute('aria-busy');
            card?.querySelector('.ajax-error')?.remove();
            const message = document.createElement('p');
            message.className = 'error ajax-error';
            message.setAttribute('role', 'alert');
            message.textContent = shell.dataset.networkError || 'The installer could not be reached.';
            card?.prepend(message);
        } finally {
            busy = false;
        }
    });
})();
</script>
</body></html>
