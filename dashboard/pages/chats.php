<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/tawkto.php';

Auth::requireLogin();

$pageTitle = 'Live Chats';
$tawkto = new TawkTo();

// AJAX handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'get_messages') {
        $chatId = sanitize($_POST['chat_id'] ?? '');
        $result = $tawkto->getChatMessages($chatId);
        jsonResponse($result);
    }

    if ($action === 'send_message') {
        $chatId  = sanitize($_POST['chat_id'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        if (empty($message)) {
            jsonResponse(['success' => false, 'message' => 'Message cannot be empty'], 400);
        }
        $result = $tawkto->sendMessage($chatId, $message);
        jsonResponse(['success' => !isset($result['error']), 'data' => $result]);
    }
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'chats') {
    $page   = intval($_GET['page'] ?? 1);
    $result = $tawkto->getChats(['page' => $page, 'pageSize' => 30]);
    jsonResponse($result);
}

// Load initial chats
try {
    $chatsData = $tawkto->getChats(['pageSize' => 30]);
    $chats = $chatsData['data'] ?? [];
    $apiError = isset($chatsData['error']) ? $chatsData['error'] : false;
} catch (Exception $e) {
    $chats = [];
    $apiError = $e->getMessage();
}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h2 style="font-weight:700;color:var(--text-primary);">
        <i class="fas fa-comments me-2" style="color:var(--accent-cyan);"></i> Live Chats
    </h2>
    <button onclick="refreshChats()" class="btn btn-outline btn-sm">
        <i class="fas fa-sync-alt me-1"></i> Refresh
    </button>
</div>

<?php if ($apiError): ?>
<div class="alert-custom alert-warning mb-3">
    <i class="fas fa-exclamation-triangle me-2"></i>
    Tawk.to API Error: <?= sanitize((string)$apiError) ?> — Please check your API key in settings.
</div>
<?php endif; ?>

<!-- Chat Layout -->
<div class="chat-layout">
    <!-- Left: Chat List -->
    <div class="chat-sidebar">
        <div class="chat-search">
            <input type="text" id="chatSearchInput" class="form-control form-control-sm"
                   placeholder="Search chats..." oninput="filterChats(this.value)">
        </div>
        <div class="chat-list" id="chatList">
            <?php if (empty($chats)): ?>
                <div class="empty-state" style="padding:2rem 1rem;">
                    <div class="empty-state-icon" style="font-size:2rem;"><i class="fas fa-comments"></i></div>
                    <div class="empty-state-title">No chats found</div>
                    <div class="empty-state-text">Check your Tawk.to API settings</div>
                </div>
            <?php else: ?>
                <?php foreach ($chats as $chat):
                    $visitor   = $chat['visitor'] ?? [];
                    $visitorName = $visitor['name'] ?? ('Visitor #' . substr($chat['_id'] ?? 'unknown', -4));
                    $lastMsg   = $chat['lastMessage'] ?? '';
                    $status    = $chat['status'] ?? 'open';
                    $time      = $chat['ts'] ?? '';
                ?>
                <div class="chat-list-item" onclick="loadChat('<?= sanitize($chat['_id'] ?? '') ?>', this)"
                     data-name="<?= strtolower(sanitize($visitorName)) ?>"
                     id="chat-item-<?= sanitize($chat['_id'] ?? '') ?>">
                    <div class="chat-avatar" style="background: <?= $status === 'open' ? 'var(--gradient-blue)' : 'linear-gradient(135deg,#475569,#334155)' ?>">
                        <?= strtoupper(substr($visitorName, 0, 2)) ?>
                    </div>
                    <div class="chat-info">
                        <div class="chat-name"><?= sanitize($visitorName) ?></div>
                        <div class="chat-preview"><?= sanitize(substr($lastMsg, 0, 45)) ?>...</div>
                    </div>
                    <div class="d-flex flex-column align-items-end gap-1">
                        <div class="chat-time"><?= $time ? date('h:i A', strtotime($time)) : '' ?></div>
                        <span class="badge <?= $status === 'open' ? 'badge-success' : 'badge-secondary' ?>" style="font-size:.6rem;"><?= $status ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Chat Window -->
    <div class="chat-main" id="chatMain">
        <!-- Empty state -->
        <div id="chatEmptyState" style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1rem;">
            <div style="font-size:4rem;opacity:.2;color:var(--text-muted);">
                <i class="fas fa-comments"></i>
            </div>
            <div style="color:var(--text-muted);font-size:.9rem;">Select a chat to start messaging</div>
        </div>

        <!-- Chat Window (hidden initially) -->
        <div id="chatWindow" style="display:none;flex-direction:column;height:100%;">
            <div class="chat-header">
                <div class="chat-avatar" id="activeChatAvatar">—</div>
                <div class="flex-grow-1">
                    <div style="font-weight:600;color:var(--text-primary);" id="activeChatName">—</div>
                    <div style="font-size:.75rem;color:var(--text-muted);" id="activeChatStatus">—</div>
                </div>
                <button class="btn btn-sm btn-outline" onclick="refreshMessages()">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="text-center py-4"><span class="spinner"></span></div>
            </div>

            <div class="chat-input-area">
                <textarea class="form-control" id="messageInput" rows="2"
                          placeholder="Type your message... (Ctrl+Enter to send)"
                          onkeydown="handleChatKeydown(event)"></textarea>
                <button class="btn-send" onclick="sendMessage()" id="sendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = <<<'JSEOF'
<script>
let activeChatId = null;

function loadChat(chatId, el) {
    // Update active states
    document.querySelectorAll('.chat-list-item').forEach(i => i.classList.remove('active'));
    if (el) el.classList.add('active');

    activeChatId = chatId;

    // Show chat window
    document.getElementById('chatEmptyState').style.display = 'none';
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.style.display = 'flex';

    // Update header
    const name = el ? el.querySelector('.chat-name')?.textContent : 'Chat';
    const initials = name.substring(0, 2).toUpperCase();
    document.getElementById('activeChatAvatar').textContent = initials;
    document.getElementById('activeChatName').textContent = name;
    document.getElementById('activeChatStatus').textContent = 'Chat ID: ' + chatId.substring(0, 8) + '...';

    // Load messages
    fetchMessages(chatId);
}

function fetchMessages(chatId) {
    const msgContainer = document.getElementById('chatMessages');
    msgContainer.innerHTML = '<div class="text-center py-4"><span class="spinner"></span></div>';

    const formData = new FormData();
    formData.append('action', 'get_messages');
    formData.append('chat_id', chatId);

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            renderMessages(data);
        })
        .catch(() => {
            msgContainer.innerHTML = '<div class="text-center py-4 text-muted">Failed to load messages</div>';
        });
}

function renderMessages(data) {
    const container = document.getElementById('chatMessages');
    const messages  = data.data || data.messages || data || [];

    if (!Array.isArray(messages) || messages.length === 0) {
        container.innerHTML = '<div class="text-center py-4" style="color:var(--text-muted);">No messages yet in this chat</div>';
        return;
    }

    let html = '';
    messages.forEach(msg => {
        const isAgent   = msg.type === 'msg' && (msg.sender?.type === 'agent' || msg.isAgent);
        const isVisitor = !isAgent;
        const text      = msg.msg || msg.message || msg.body || '';
        const time      = msg.ts ? new Date(msg.ts).toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit'}) : '';
        const sender    = msg.sender?.name || (isAgent ? 'Agent' : 'Visitor');

        html += `
            <div class="d-flex flex-column ${isAgent ? 'align-items-end' : 'align-items-start'}">
                <div class="message-bubble ${isAgent ? 'agent' : 'visitor'}">
                    ${escapeHtml(text)}
                </div>
                <div class="message-meta ${isAgent ? 'agent-meta' : ''}">
                    ${sender} · ${time}
                </div>
            </div>`;
    });

    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const text  = input.value.trim();
    if (!text || !activeChatId) return;

    const sendBtn = document.getElementById('sendBtn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner"></span>';

    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('chat_id', activeChatId);
    formData.append('message', text);

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                fetchMessages(activeChatId);
                showToast('Message sent!', 'success');
            } else {
                showToast('Failed to send message. Check Tawk.to API key.', 'error');
            }
        })
        .finally(() => {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        });
}

function handleChatKeydown(e) {
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
}

function refreshMessages() {
    if (activeChatId) fetchMessages(activeChatId);
}

function filterChats(val) {
    const q = val.toLowerCase();
    document.querySelectorAll('.chat-list-item').forEach(item => {
        item.style.display = item.dataset.name?.includes(q) ? '' : 'none';
    });
}

function refreshChats() {
    location.reload();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Auto-refresh messages every 30s
setInterval(() => {
    if (activeChatId) fetchMessages(activeChatId);
}, 30000);
</script>
JSEOF;

include __DIR__ . '/../includes/layout_footer.php';
?>
