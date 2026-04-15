// ============================================================
// DASHBOARD - Main JS
// ============================================================

// Sidebar Toggle
const sidebarToggle  = document.getElementById('sidebarToggle');
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        sidebarOverlay.classList.toggle('show');
    });
}
if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('show');
    });
}

// Confirm delete
function confirmDelete(message, callback) {
    if (confirm(message || 'Are you sure? This action cannot be undone.')) {
        callback();
    }
}

// Toast notification
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');
    const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    toast.className = `alert-custom alert-${type}`;
    toast.style.cssText = 'margin-bottom:8px;animation:slideIn 0.3s ease;';
    toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'} me-2"></i>${message}
        <button onclick="this.parentElement.remove()" class="alert-close"><i class="fas fa-times"></i></button>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 400); }, 4000);
}

function createToastContainer() {
    const el = document.createElement('div');
    el.id = 'toastContainer';
    el.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;min-width:300px;';
    document.body.appendChild(el);
    return el;
}

// Format number with commas
function formatNumber(n) {
    return new Intl.NumberFormat().format(n);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => showToast('Copied to clipboard!', 'success'));
}

// API Helper
const API = {
    async get(url, params = {}) {
        const query = new URLSearchParams(params).toString();
        const res = await fetch(url + (query ? '?' + query : ''));
        return res.json();
    },
    async post(url, data = {}) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return res.json();
    }
};

// Table search filter
function initTableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    input.addEventListener('input', () => {
        const filter = input.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', () => {
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));
});

// Animate numbers counting up
function animateCount(el, target, duration = 1000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        el.textContent = formatNumber(Math.floor(current));
    }, 16);
}

// Animate all stat values on page load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stat-value[data-value]').forEach(el => {
        const val = parseFloat(el.dataset.value);
        if (!isNaN(val)) animateCount(el, val);
    });
});
