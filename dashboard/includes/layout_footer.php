    </main>
</div>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script>
// Auto-dismiss flash message
const flashAlert = document.getElementById('flashAlert');
if (flashAlert) {
    setTimeout(() => { flashAlert.style.opacity = '0'; setTimeout(() => flashAlert.remove(), 500); }, 4000);
}

// Live clock
function updateTime() {
    const el = document.getElementById('topbarTime');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
setInterval(updateTime, 1000);
updateTime();
</script>
<?= $extraJs ?? '' ?>
</body>
</html>
