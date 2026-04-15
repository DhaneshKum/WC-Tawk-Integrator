<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/woocommerce.php';
require_once __DIR__ . '/includes/tawkto.php';

Auth::requireLogin();

$pageTitle = 'Dashboard';

$wc      = new WooCommerce();
$tawkto  = new TawkTo();

// Fetch stats (with error handling)
try {
    $wcStats     = $wc->getDashboardStats();
    $chatStats   = $tawkto->getDashboardStats();
    $recentOrders = $wcStats['recent_orders'] ?? [];
    $wcError      = false;
} catch (Exception $e) {
    $wcError      = true;
    $wcStats      = ['today_orders' => 0, 'today_revenue' => 0, 'processing_orders' => 0, 'pending_orders' => 0, 'recent_orders' => []];
    $chatStats    = ['total_chats' => 0, 'open_chats' => 0, 'missed_chats' => 0, 'total_tickets' => 0];
    $recentOrders = [];
}

include __DIR__ . '/includes/layout_header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="mb-1" style="color:var(--text-primary);font-weight:700;">Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>, <?= sanitize($currentUser['full_name']) ?> 👋</h2>
        <p class="mb-0" style="color:var(--text-muted);font-size:.875rem;"><?= date('l, d F Y') ?></p>
    </div>
    <button onclick="refreshStats()" class="btn btn-outline" id="refreshBtn">
        <i class="fas fa-sync-alt me-2"></i> Refresh
    </button>
</div>

<?php if ($wcError): ?>
<div class="alert-custom alert-warning mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    Could not connect to WooCommerce/Tawk.to API. Please check your settings.
    <a href="pages/settings.php" class="ms-2" style="color:inherit;font-weight:600;">Configure →</a>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <!-- Today Orders -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--card-accent: rgba(59,130,246,0.12);">
            <div class="stat-icon" style="background: var(--gradient-blue);">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="stat-value" data-value="<?= $wcStats['today_orders'] ?>"><?= $wcStats['today_orders'] ?></div>
            <div class="stat-label">Today's Orders</div>
            <div class="stat-change up"><i class="fas fa-arrow-up me-1"></i>Orders placed today</div>
        </div>
    </div>

    <!-- Today Revenue -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--card-accent: rgba(16,185,129,0.12);">
            <div class="stat-icon" style="background: var(--gradient-green);">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value" style="font-size:1.4rem;"><?= formatCurrency($wcStats['today_revenue']) ?></div>
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-change up"><i class="fas fa-arrow-up me-1"></i>Total earnings today</div>
        </div>
    </div>

    <!-- Processing Orders -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--card-accent: rgba(245,158,11,0.12);">
            <div class="stat-icon" style="background: var(--gradient-orange);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value" data-value="<?= $wcStats['processing_orders'] ?>"><?= $wcStats['processing_orders'] ?></div>
            <div class="stat-label">Processing</div>
            <div class="stat-change" style="color:var(--accent-orange);"><i class="fas fa-hourglass-half me-1"></i>Need attention</div>
        </div>
    </div>

    <!-- Open Chats -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="--card-accent: rgba(6,182,212,0.12);">
            <div class="stat-icon" style="background: var(--gradient-cyan);">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-value" data-value="<?= $chatStats['open_chats'] ?>"><?= $chatStats['open_chats'] ?></div>
            <div class="stat-label">Open Chats</div>
            <div class="stat-change" style="color:var(--accent-cyan);"><i class="fas fa-circle me-1" style="font-size:.5rem;"></i>Live now</div>
        </div>
    </div>
</div>

<!-- Second row stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card" style="--card-accent: rgba(239,68,68,0.1);">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon mb-0" style="background: rgba(239,68,68,0.15); color: var(--accent-red);">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size:1.5rem;"><?= $wcStats['pending_orders'] ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card" style="--card-accent: rgba(245,158,11,0.1);">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon mb-0" style="background: rgba(245,158,11,0.15); color: var(--accent-orange);">
                    <i class="fas fa-phone-missed"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size:1.5rem;"><?= $chatStats['missed_chats'] ?></div>
                    <div class="stat-label">Missed Chats</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card" style="--card-accent: rgba(139,92,246,0.1);">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon mb-0" style="background: rgba(139,92,246,0.15); color: var(--accent-purple);">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size:1.5rem;"><?= $chatStats['total_tickets'] ?></div>
                    <div class="stat-label">Open Tickets</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders + Quick Chat -->
<div class="row g-3">
    <!-- Recent Orders -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-shopping-bag me-2" style="color:var(--accent-blue)"></i>Recent Orders</span>
                <a href="pages/orders.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentOrders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-shopping-bag"></i></div>
                        <div class="empty-state-title">No orders found</div>
                        <div class="empty-state-text">Check your WooCommerce API settings</div>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>
                                    <a href="pages/orders.php?id=<?= $order['id'] ?>"
                                       style="color:var(--accent-blue);font-weight:600;text-decoration:none;">
                                        #<?= $order['number'] ?? $order['id'] ?>
                                    </a>
                                </td>
                                <td style="color:var(--text-primary);">
                                    <?php
                                        $billing = $order['billing'] ?? [];
                                        $name = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
                                        echo sanitize($name ?: 'Guest');
                                    ?>
                                </td>
                                <td style="color:var(--accent-green);font-weight:600;">
                                    <?= formatCurrency($order['total'] ?? 0, $order['currency'] ?? 'PKR') ?>
                                </td>
                                <td><?= getStatusBadge($order['status'] ?? 'pending') ?></td>
                                <td><?= timeAgo($order['date_created'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats / Tawk.to -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-comments me-2" style="color:var(--accent-cyan)"></i>Chat Overview</span>
                <a href="pages/chats.php" class="btn btn-sm btn-outline">Open Chats</a>
            </div>
            <div class="card-body">
                <!-- Chat Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.15);border-radius:12px;padding:1rem;text-align:center;">
                            <div style="font-size:1.6rem;font-weight:800;color:var(--accent-green);"><?= $chatStats['open_chats'] ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Open</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.15);border-radius:12px;padding:1rem;text-align:center;">
                            <div style="font-size:1.6rem;font-weight:800;color:var(--accent-orange);"><?= $chatStats['missed_chats'] ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Missed</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.15);border-radius:12px;padding:1rem;text-align:center;">
                            <div style="font-size:1.6rem;font-weight:800;color:var(--accent-blue);"><?= $chatStats['total_chats'] ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Total</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div style="background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.15);border-radius:12px;padding:1rem;text-align:center;">
                            <div style="font-size:1.6rem;font-weight:800;color:var(--accent-purple);"><?= $chatStats['total_tickets'] ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Tickets</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="font-size:.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.75rem;">Quick Actions</div>
                <div class="d-flex flex-column gap-2">
                    <a href="pages/orders.php?status=pending" class="btn btn-outline d-flex align-items-center gap-2 justify-content-start">
                        <i class="fas fa-clock" style="color:var(--accent-orange);"></i>
                        <span>View Pending Orders (<?= $wcStats['pending_orders'] ?>)</span>
                    </a>
                    <a href="pages/chats.php" class="btn btn-outline d-flex align-items-center gap-2 justify-content-start">
                        <i class="fas fa-comments" style="color:var(--accent-cyan);"></i>
                        <span>Open Chat Panel</span>
                    </a>
                    <a href="pages/tickets.php" class="btn btn-outline d-flex align-items-center gap-2 justify-content-start">
                        <i class="fas fa-ticket-alt" style="color:var(--accent-purple);"></i>
                        <span>View Tickets (<?= $chatStats['total_tickets'] ?>)</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
function refreshStats() {
    const btn = document.getElementById('refreshBtn');
    btn.innerHTML = '<span class="spinner me-2"></span> Refreshing...';
    btn.disabled = true;
    setTimeout(() => location.reload(), 500);
}
</script>
JS;
include __DIR__ . '/includes/layout_footer.php';
?>
