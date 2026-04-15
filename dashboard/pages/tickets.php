<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tawkto.php';

Auth::requireLogin();

$pageTitle = 'Tickets';
$tawkto = new TawkTo();

// AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reply_ticket') {
        $ticketId = sanitize($_POST['ticket_id'] ?? '');
        $message  = sanitize($_POST['message'] ?? '');
        $result   = $tawkto->replyToTicket($ticketId, $message);
        jsonResponse(['success' => !isset($result['error']), 'data' => $result]);
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'ticket') {
    $ticketId = sanitize($_GET['id'] ?? '');
    $result = $tawkto->getTicket($ticketId);
    jsonResponse($result);
}

// Fetch tickets
try {
    $ticketsData = $tawkto->getTickets(['pageSize' => 30]);
    $tickets     = $ticketsData['data'] ?? [];
    $apiError    = isset($ticketsData['error']) ? $ticketsData['error'] : false;
} catch (Exception $e) {
    $tickets  = [];
    $apiError = $e->getMessage();
}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 style="font-weight:700;color:var(--text-primary);">
        <i class="fas fa-ticket-alt me-2" style="color:var(--accent-purple);"></i> Support Tickets
    </h2>
    <button onclick="location.reload()" class="btn btn-outline btn-sm">
        <i class="fas fa-sync-alt me-1"></i> Refresh
    </button>
</div>

<?php if ($apiError): ?>
<div class="alert-custom alert-warning mb-3">
    <i class="fas fa-exclamation-triangle me-2"></i>
    API Error: <?= sanitize((string)$apiError) ?>
</div>
<?php endif; ?>

<!-- Tickets Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i>All Tickets</span>
        <input type="text" id="ticketSearch" class="form-control form-control-sm"
               style="max-width:200px;" placeholder="Search..."
               oninput="filterTickets(this.value)">
    </div>
    <div class="card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-ticket-alt"></i></div>
                <div class="empty-state-title">No tickets found</div>
                <div class="empty-state-text">Tickets from Tawk.to will appear here</div>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0" id="ticketsTable">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket):
                        $contact = $ticket['contact'] ?? [];
                        $contactName = $contact['name'] ?? 'Unknown';
                        $status = $ticket['status'] ?? 'open';
                        $priority = $ticket['priority'] ?? 'normal';
                    ?>
                    <tr>
                        <td><span style="color:var(--accent-purple);font-weight:700;">#<?= substr($ticket['_id'] ?? 'N/A', -6) ?></span></td>
                        <td style="color:var(--text-primary);font-weight:500;max-width:250px;">
                            <span class="text-truncate d-block"><?= sanitize($ticket['subject'] ?? 'No subject') ?></span>
                        </td>
                        <td>
                            <div style="color:var(--text-primary);font-size:.85rem;"><?= sanitize($contactName) ?></div>
                            <div style="color:var(--text-muted);font-size:.75rem;"><?= sanitize($contact['email'] ?? '') ?></div>
                        </td>
                        <td><?= getStatusBadge($status) ?></td>
                        <td>
                            <?php
                            $prBadge = ['urgent' => 'badge-danger', 'high' => 'badge-warning', 'normal' => 'badge-primary', 'low' => 'badge-secondary'];
                            $prClass = $prBadge[$priority] ?? 'badge-secondary';
                            ?>
                            <span class="badge <?= $prClass ?>"><?= ucfirst($priority) ?></span>
                        </td>
                        <td style="color:var(--text-muted);font-size:.8rem;">
                            <?= isset($ticket['createdAt']) ? timeAgo($ticket['createdAt']) : '—' ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary btn-icon"
                                    onclick="openTicket('<?= sanitize($ticket['_id'] ?? '') ?>')"
                                    title="View & Reply">
                                <i class="fas fa-reply"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ticket Detail Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-ticket-alt me-2"></i>Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="ticketModalBody">
                <div class="text-center py-4"><span class="spinner"></span></div>
            </div>
            <div class="modal-footer" style="display:none;" id="ticketReplyArea">
                <div class="w-100">
                    <input type="hidden" id="activeTicketId">
                    <textarea class="form-control mb-2" id="ticketReplyInput" rows="3"
                              placeholder="Type your reply..."></textarea>
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-outline" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary" onclick="sendTicketReply()">
                            <i class="fas fa-reply me-1"></i> Send Reply
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JSEOF'
<script>
function openTicket(ticketId) {
    document.getElementById('activeTicketId').value = ticketId;
    const body = document.getElementById('ticketModalBody');
    body.innerHTML = '<div class="text-center py-4"><span class="spinner"></span></div>';
    document.getElementById('ticketReplyArea').style.display = 'none';
    new bootstrap.Modal(document.getElementById('ticketModal')).show();

    fetch('?ajax=ticket&id=' + ticketId)
        .then(r => r.json())
        .then(data => {
            const t = data;
            const msgs = (t.messages || []).map(m => {
                const isAgent = m.sender?.type === 'agent';
                return `<div class="d-flex flex-column ${isAgent ? 'align-items-end' : 'align-items-start'} mb-2">
                    <div class="message-bubble ${isAgent ? 'agent' : 'visitor'}">${escapeHtml(m.body || m.msg || '')}</div>
                    <div class="message-meta ${isAgent ? 'agent-meta' : ''}">${m.sender?.name || 'User'}</div>
                </div>`;
            }).join('');

            body.innerHTML = `
                <div class="mb-3 p-3" style="background:rgba(255,255,255,0.04);border-radius:10px;">
                    <div style="font-size:1rem;font-weight:600;color:var(--text-primary);margin-bottom:.5rem;">${escapeHtml(t.subject || 'No subject')}</div>
                    <div class="d-flex gap-3 flex-wrap">
                        <small style="color:var(--text-muted)"><i class="fas fa-user me-1"></i>${escapeHtml(t.contact?.name || '—')}</small>
                        <small style="color:var(--text-muted)"><i class="fas fa-envelope me-1"></i>${escapeHtml(t.contact?.email || '—')}</small>
                        <small style="color:var(--text-muted)"><i class="fas fa-circle me-1"></i>${t.status || '—'}</small>
                    </div>
                </div>
                <div style="max-height:350px;overflow-y:auto;">${msgs || '<div class="text-center text-muted py-3">No messages</div>'}</div>`;
            document.getElementById('ticketReplyArea').style.display = 'flex';
        });
}

function sendTicketReply() {
    const ticketId = document.getElementById('activeTicketId').value;
    const message  = document.getElementById('ticketReplyInput').value.trim();
    if (!message) return showToast('Please enter a reply message', 'warning');

    const btn = event.target;
    btn.innerHTML = '<span class="spinner me-1"></span> Sending...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'reply_ticket');
    formData.append('ticket_id', ticketId);
    formData.append('message', message);

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('ticketReplyInput').value = '';
                showToast('Reply sent successfully!', 'success');
                openTicket(ticketId);
            } else {
                showToast('Failed to send reply. Check Tawk.to API.', 'error');
            }
        })
        .finally(() => {
            btn.innerHTML = '<i class="fas fa-reply me-1"></i> Send Reply';
            btn.disabled = false;
        });
}

function filterTickets(val) {
    const q = val.toLowerCase();
    document.querySelectorAll('#ticketsTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
JSEOF;

include __DIR__ . '/../includes/layout_footer.php';
?>
