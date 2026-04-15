<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/woocommerce.php';

Auth::requireLogin();

$pageTitle = 'Orders';
$wc = new WooCommerce();

// Handle update status (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $orderId = intval($_POST['order_id'] ?? 0);
        $status  = sanitize($_POST['status'] ?? '');
        $result  = $wc->updateOrderStatus($orderId, $status);
        jsonResponse(['success' => !isset($result['error']), 'data' => $result]);
    }
}

// Handle AJAX request for order detail
if (isset($_GET['ajax']) && isset($_GET['get_order'])) {
    $orderId = intval($_GET['get_order']);
    try {
        $order = $wc->getOrder($orderId);
        jsonResponse($order);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()]);
    }
}

// Pagination & Filters
$page      = max(1, intval($_GET['page'] ?? 1));
$status    = sanitize($_GET['status'] ?? '');
$perPage   = 15;
$search    = sanitize($_GET['search'] ?? '');

$params = ['page' => $page, 'per_page' => $perPage];
if ($status) $params['status'] = $status;
if ($search) $params['search'] = $search;

try {
    $orders = $wc->getOrders($params);
    $apiError = isset($orders['error']) ? $orders['error'] : false;
    if ($apiError) $orders = [];
} catch (Exception $e) {
    $orders = [];
    $apiError = $e->getMessage();
}

// Single order detail
$selectedOrder = null;
if (!empty($_GET['id'])) {
    try {
        $selectedOrder = $wc->getOrder(intval($_GET['id']));
    } catch (Exception $e) {
        $selectedOrder = null;
    }
}

$orderStatuses = ['', 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed'];

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 style="font-weight:700;color:var(--text-primary);">
        <i class="fas fa-shopping-bag me-2" style="color:var(--accent-blue);"></i> Orders
    </h2>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/pages/orders.php" class="btn btn-outline btn-sm">
            <i class="fas fa-sync-alt me-1"></i> Refresh
        </a>
    </div>
</div>

<?php if ($apiError): ?>
<div class="alert-custom alert-warning mb-3">
    <i class="fas fa-exclamation-triangle me-2"></i>
    API Error: <?= sanitize((string)$apiError) ?> — Please check your WooCommerce settings.
</div>
<?php endif; ?>

<!-- Filters Bar -->
<div class="filters-bar">
    <form method="GET" action="" class="d-flex gap-2 flex-wrap align-items-center flex-grow-1">
        <input type="text" name="search" class="form-control" placeholder="Search by name/email..."
               value="<?= $search ?>" style="max-width:220px;">
        <select name="status" class="form-select" style="max-width:160px;">
            <option value="">All Statuses</option>
            <?php foreach (array_filter($orderStatuses) as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                    <?= ucfirst(str_replace('-', ' ', $s)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> Filter</button>
        <a href="orders.php" class="btn btn-outline btn-sm"><i class="fas fa-times me-1"></i> Clear</a>
    </form>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="empty-state-title">No orders found</div>
                <div class="empty-state-text">Try adjusting your filters or check API configuration</div>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order):
                        $billing  = $order['billing'] ?? [];
                        $name     = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
                        $email    = $billing['email'] ?? '';
                        $items    = count($order['line_items'] ?? []);
                    ?>
                    <tr>
                        <td>
                            <span style="color:var(--accent-blue);font-weight:700;">#<?= $order['number'] ?? $order['id'] ?></span>
                        </td>
                        <td style="color:var(--text-primary);font-weight:500;"><?= sanitize($name ?: 'Guest') ?></td>
                        <td style="color:var(--text-muted);font-size:.8rem;"><?= sanitize($email) ?></td>
                        <td><span class="badge badge-secondary"><?= $items ?> item<?= $items != 1 ? 's' : '' ?></span></td>
                        <td style="color:var(--accent-green);font-weight:700;">
                            <?= formatCurrency($order['total'] ?? 0, $order['currency'] ?? 'PKR') ?>
                        </td>
                        <td><?= getStatusBadge($order['status'] ?? 'pending') ?></td>
                        <td style="color:var(--text-muted);font-size:.8rem;"><?= formatDate($order['date_created'] ?? '') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline btn-icon"
                                        onclick="viewOrder(<?= $order['id'] ?>)"
                                        title="View Details" data-bs-toggle="tooltip">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline btn-icon"
                                        onclick="showStatusModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')"
                                        title="Update Status" data-bs-toggle="tooltip">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center px-3 py-3" style="border-top:1px solid var(--border-color);">
            <small style="color:var(--text-muted);">Page <?= $page ?> — Showing <?= count($orders) ?> orders</small>
            <div class="d-flex gap-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $status ?>&search=<?= $search ?>"
                       class="btn btn-sm btn-outline"><i class="fas fa-chevron-left"></i> Prev</a>
                <?php endif; ?>
                <?php if (count($orders) == $perPage): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $status ?>&search=<?= $search ?>"
                       class="btn btn-sm btn-outline">Next <i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailBody">
                <div class="text-center py-4"><span class="spinner"></span></div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="updateOrderId">
                <label class="form-label">New Status</label>
                <select class="form-select" id="newStatus">
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="on-hold">On Hold</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateOrderStatus()">
                    <i class="fas fa-check me-1"></i> Update
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JS'
<script>
function viewOrder(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
    document.getElementById('orderDetailBody').innerHTML = '<div class="text-center py-4"><span class="spinner"></span></div>';
    modal.show();

    fetch('?ajax=1&get_order=' + orderId)
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                document.getElementById('orderDetailBody').innerHTML = '<div class="alert-custom alert-warning">Error: ' + data.error + '</div>';
                return;
            }
            const o = data;
            const billing = o.billing || {};
            const items = (o.line_items || []).map(i =>
                `<tr>
                    <td>${i.name}</td>
                    <td class="text-center">${i.quantity}</td>
                    <td class="text-end" style="color:var(--accent-green);">${i.subtotal}</td>
                </tr>`
            ).join('');

            let vinNumber = '—';
            if (o.meta_data) {
                const vinMeta = o.meta_data.find(m => m.key.toLowerCase().includes('vin'));
                if (vinMeta) vinNumber = vinMeta.value;
            }

            document.getElementById('orderDetailBody').innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><i class="fas fa-user me-2"></i>Customer Info</div>
                            <div class="card-body">
                                <div class="order-detail-row"><span class="order-detail-label">Name</span><span class="order-detail-value">${billing.first_name || ''} ${billing.last_name || ''}</span></div>
                                <div class="order-detail-row"><span class="order-detail-label">Email</span><span class="order-detail-value">${billing.email || '—'}</span></div>
                                <div class="order-detail-row"><span class="order-detail-label">Phone</span><span class="order-detail-value">${billing.phone || '—'}</span></div>
                                <div class="order-detail-row"><span class="order-detail-label">VIN Number</span><span class="order-detail-value" style="font-weight:700;color:var(--accent-blue);">${vinNumber}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Order Info</div>
                            <div class="card-body">
                                <div class="order-detail-row"><span class="order-detail-label">Order #</span><span class="order-detail-value">#${o.number || o.id}</span></div>
                                <div class="order-detail-row"><span class="order-detail-label">Status</span><span class="order-detail-value">${o.status}</span></div>
                                <div class="order-detail-row"><span class="order-detail-label">Payment</span><span class="order-detail-value">${o.payment_method_title || '—'}</span></div>
                                <div class="order-detail-row"><span class="order-detail-label">Date</span><span class="order-detail-value">${o.date_created || '—'}</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><i class="fas fa-list me-2"></i>Order Items</div>
                            <div class="card-body p-0">
                                <table class="table mb-0">
                                    <thead><tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Total</th></tr></thead>
                                    <tbody>${items}</tbody>
                                    <tfoot>
                                        <tr><td colspan="2" class="text-end fw-bold" style="color:var(--text-secondary);">Total:</td>
                                            <td class="text-end fw-bold" style="color:var(--accent-green);font-size:1rem;">${o.currency || ''} ${o.total || 0}</td></tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    ${o.customer_note ? `<div class="col-12"><div class="alert-custom alert-warning"><i class="fas fa-sticky-note me-2"></i><strong>Customer Note:</strong> ${o.customer_note}</div></div>` : ''}
                </div>`;
        });
}

function showStatusModal(orderId, currentStatus) {
    document.getElementById('updateOrderId').value = orderId;
    document.getElementById('newStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function updateOrderStatus() {
    const orderId = document.getElementById('updateOrderId').value;
    const status  = document.getElementById('newStatus').value;

    const btn = event.target;
    btn.innerHTML = '<span class="spinner me-1"></span> Updating...';
    btn.disabled = true;

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_status&order_id=' + orderId + '&status=' + status
    })
    .then(r => r.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
        if (data.success) {
            showToast('Order status updated successfully!', 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast('Failed to update status. Check API settings.', 'error');
        }
    })
    .finally(() => {
        btn.innerHTML = '<i class="fas fa-check me-1"></i> Update';
        btn.disabled = false;
    });
}

// AJAX order detail endpoint
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('id')) {
    viewOrder(urlParams.get('id'));
}
</script>
JS;


include __DIR__ . '/../includes/layout_footer.php';
?>
