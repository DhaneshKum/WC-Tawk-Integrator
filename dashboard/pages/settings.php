<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/woocommerce.php';
require_once __DIR__ . '/../includes/tawkto.php';

Auth::requireAdmin();

$pageTitle = 'Settings';

// Handle save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_api') {
        // Read current config
        $configPath = __DIR__ . '/../includes/config.php';
        $config = file_get_contents($configPath);

        $wcUrl    = sanitize($_POST['wc_url'] ?? '');
        $wcKey    = sanitize($_POST['wc_key'] ?? '');
        $wcSecret = sanitize($_POST['wc_secret'] ?? '');
        $tawkKey  = sanitize($_POST['tawk_key'] ?? '');
        $tawkProp = sanitize($_POST['tawk_property'] ?? '');
        $tawkInbox= sanitize($_POST['tawk_inbox'] ?? '');

        $config = preg_replace("/define\('WC_STORE_URL',.*?\);/", "define('WC_STORE_URL', '$wcUrl');", $config);
        $config = preg_replace("/define\('WC_CONSUMER_KEY',.*?\);/", "define('WC_CONSUMER_KEY', '$wcKey');", $config);
        $config = preg_replace("/define\('WC_CONSUMER_SECRET',.*?\);/", "define('WC_CONSUMER_SECRET', '$wcSecret');", $config);
        $config = preg_replace("/define\('TAWKTO_API_KEY',.*?\);/", "define('TAWKTO_API_KEY', '$tawkKey');", $config);
        $config = preg_replace("/define\('TAWKTO_PROPERTY_ID',.*?\);/", "define('TAWKTO_PROPERTY_ID', '$tawkProp');", $config);
        $config = preg_replace("/define\('TAWKTO_INBOX_ID',.*?\);/", "define('TAWKTO_INBOX_ID', '$tawkInbox');", $config);

        if (file_put_contents($configPath, $config)) {
            redirect('settings.php', 'Settings saved successfully!', 'success');
        } else {
            redirect('settings.php', 'Failed to save — check file permissions', 'error');
        }
    }

    if ($action === 'test_wc') {
        $wc = new WooCommerce();
        $result = $wc->getOrders(['per_page' => 1]);
        if (isset($result['error'])) {
            jsonResponse(['success' => false, 'message' => $result['error']]);
        } else {
            jsonResponse(['success' => true, 'message' => 'WooCommerce connected! Found ' . count($result) . ' order(s)']);
        }
    }

    if ($action === 'test_tawk') {
        $tawkto = new TawkTo();
        $result = $tawkto->getChats(['pageSize' => 1]);
        if (isset($result['error'])) {
            jsonResponse(['success' => false, 'message' => $result['error']]);
        } else {
            jsonResponse(['success' => true, 'message' => 'Tawk.to connected successfully!']);
        }
    }
}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 style="font-weight:700;color:var(--text-primary);">
        <i class="fas fa-cog me-2" style="color:var(--text-muted);"></i> Settings
    </h2>
</div>

<div class="row g-4">
    <!-- API Settings -->
    <div class="col-lg-8">
        <form method="POST">
            <input type="hidden" name="action" value="save_api">

            <!-- WooCommerce -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;background:linear-gradient(135deg,#96588a,#7b3f8a);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-shopping-bag" style="color:white;font-size:.8rem;"></i>
                        </div>
                        WooCommerce API Settings
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">WordPress Store URL</label>
                        <input type="url" name="wc_url" class="form-control"
                               value="<?= htmlspecialchars(WC_STORE_URL) ?>"
                               placeholder="https://your-wordpress-site.com">
                        <div class="mt-1" style="font-size:.78rem;color:var(--text-muted);">
                            Your WordPress site URL (without trailing slash)
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Consumer Key</label>
                            <input type="text" name="wc_key" class="form-control font-monospace"
                                   value="<?= htmlspecialchars(WC_CONSUMER_KEY) ?>"
                                   placeholder="ck_xxxxxxxxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Consumer Secret</label>
                            <input type="password" name="wc_secret" class="form-control font-monospace"
                                   value="<?= htmlspecialchars(WC_CONSUMER_SECRET) ?>"
                                   placeholder="cs_xxxxxxxxxxxxxxxx">
                        </div>
                    </div>
                    <div class="mt-3 p-3 rounded" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.15);">
                        <div style="font-size:.8rem;color:var(--accent-blue);font-weight:600;margin-bottom:.5rem;">
                            <i class="fas fa-info-circle me-1"></i> How to get WooCommerce API keys:
                        </div>
                        <ol style="font-size:.78rem;color:var(--text-muted);margin:0;padding-left:1.2rem;">
                            <li>Go to WordPress Admin → WooCommerce → Settings</li>
                            <li>Click "Advanced" tab → REST API</li>
                            <li>Add new key → Set permissions to "Read/Write"</li>
                            <li>Copy the Consumer Key and Consumer Secret</li>
                        </ol>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm mt-3" onclick="testWooCommerce()">
                        <i class="fas fa-plug me-1"></i> Test Connection
                    </button>
                    <span id="wcTestResult" class="ms-2" style="font-size:.85rem;"></span>
                </div>
            </div>

            <!-- Tawk.to -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;background:linear-gradient(135deg,#03c9a9,#02a88f);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-comments" style="color:white;font-size:.8rem;"></i>
                        </div>
                        Tawk.to API Settings
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="password" name="tawk_key" class="form-control font-monospace"
                               value="<?= htmlspecialchars(TAWKTO_API_KEY) ?>"
                               placeholder="Your Tawk.to REST API Key">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Property ID</label>
                            <input type="text" name="tawk_property" class="form-control font-monospace"
                                   value="<?= htmlspecialchars(TAWKTO_PROPERTY_ID) ?>"
                                   placeholder="Property ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Inbox ID</label>
                            <input type="text" name="tawk_inbox" class="form-control font-monospace"
                                   value="<?= htmlspecialchars(TAWKTO_INBOX_ID) ?>"
                                   placeholder="Inbox ID">
                        </div>
                    </div>
                    <div class="mt-3 p-3 rounded" style="background:rgba(3,201,169,.08);border:1px solid rgba(3,201,169,.15);">
                        <div style="font-size:.8rem;color:#03c9a9;font-weight:600;margin-bottom:.5rem;">
                            <i class="fas fa-info-circle me-1"></i> How to get Tawk.to API key:
                        </div>
                        <ol style="font-size:.78rem;color:var(--text-muted);margin:0;padding-left:1.2rem;">
                            <li>Login to <strong>tawk.to</strong> dashboard</li>
                            <li>Go to Admin → REST API</li>
                            <li>Generate a new API key</li>
                            <li>Copy Property ID and Inbox ID from your chat widget URL</li>
                        </ol>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm mt-3" onclick="testTawkTo()">
                        <i class="fas fa-plug me-1"></i> Test Connection
                    </button>
                    <span id="tawkTestResult" class="ms-2" style="font-size:.85rem;"></span>
                </div>
            </div>

            <!-- Save Button -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-save me-2"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Right Sidebar Info -->
    <div class="col-lg-4">
        <!-- System Info -->
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-server me-2"></i>System Info</div>
            <div class="card-body">
                <div class="order-detail-row">
                    <span class="order-detail-label">PHP Version</span>
                    <span class="order-detail-value" style="color:var(--accent-green);"><?= PHP_VERSION ?></span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">cURL</span>
                    <span class="order-detail-value" style="color:<?= function_exists('curl_version') ? 'var(--accent-green)' : 'var(--accent-red)' ?>">
                        <?= function_exists('curl_version') ? '✓ Enabled' : '✗ Disabled' ?>
                    </span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">JSON</span>
                    <span class="order-detail-value" style="color:var(--accent-green);">✓ Enabled</span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">Sessions</span>
                    <span class="order-detail-value" style="color:var(--accent-green);">✓ Active</span>
                </div>
                <div class="order-detail-row">
                    <span class="order-detail-label">App URL</span>
                    <span class="order-detail-value" style="font-size:.75rem;"><?= APP_URL ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-header"><i class="fas fa-link me-2"></i>Quick Links</div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="<?= WC_STORE_URL ?>/wp-admin" target="_blank" class="btn btn-outline text-start">
                    <i class="fab fa-wordpress me-2" style="color:#21759b;"></i> WordPress Admin
                </a>
                <a href="https://tawk.to" target="_blank" class="btn btn-outline text-start">
                    <i class="fas fa-comments me-2" style="color:#03c9a9;"></i> Tawk.to Dashboard
                </a>
                <a href="<?= APP_URL ?>/pages/users.php" class="btn btn-outline text-start">
                    <i class="fas fa-users me-2" style="color:var(--accent-blue);"></i> Manage Users
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JSEOF'
<script>
function testWooCommerce() {
    const el = document.getElementById('wcTestResult');
    el.innerHTML = '<span class="spinner"></span> Testing...';

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=test_wc'
    })
    .then(r => r.json())
    .then(data => {
        el.innerHTML = data.success
            ? `<span style="color:var(--accent-green);"><i class="fas fa-check-circle me-1"></i>${data.message}</span>`
            : `<span style="color:var(--accent-red);"><i class="fas fa-times-circle me-1"></i>${data.message}</span>`;
    });
}

function testTawkTo() {
    const el = document.getElementById('tawkTestResult');
    el.innerHTML = '<span class="spinner"></span> Testing...';

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=test_tawk'
    })
    .then(r => r.json())
    .then(data => {
        el.innerHTML = data.success
            ? `<span style="color:var(--accent-green);"><i class="fas fa-check-circle me-1"></i>${data.message}</span>`
            : `<span style="color:var(--accent-red);"><i class="fas fa-times-circle me-1"></i>${data.message}</span>`;
    });
}
</script>
JSEOF;

include __DIR__ . '/../includes/layout_footer.php';
?>
