<!-- GLOBAL ALERT MODAL -->
<div id="globalAlertModal" class="alert-modal-overlay">
    <div class="alert-modal-box">
        <div id="alertModalIcon" class="alert-modal-icon"></div>
        <h3 id="alertModalTitle" class="alert-modal-title"></h3>
        <p id="alertModalMessage" class="alert-modal-message"></p>
        <button onclick="closeAlertModal()" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem;">OK, Got it</button>
    </div>
</div>

<script>
function showAlertModal(type, title, message) {
    const modal = document.getElementById('globalAlertModal');
    if (!modal) return;
    const icon = document.getElementById('alertModalIcon');
    const titleEl = document.getElementById('alertModalTitle');
    const msgEl = document.getElementById('alertModalMessage');

    icon.className = 'alert-modal-icon ' + type;
    if (type === 'success') icon.innerHTML = '<i class="fas fa-check-circle"></i>';
    else if (type === 'error') icon.innerHTML = '<i class="fas fa-times-circle"></i>';
    else if (type === 'warning') icon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
    else icon.innerHTML = '<i class="fas fa-info-circle"></i>';

    titleEl.textContent = title;
    msgEl.textContent = message;
    modal.classList.add('active');
}

function closeAlertModal() {
    const modal = document.getElementById('globalAlertModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    // Look for both .alert classes and potential PHP session variables passed to JS
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        alerts.forEach(alert => {
            let type = 'info';
            if (alert.classList.contains('alert-success')) type = 'success';
            else if (alert.classList.contains('alert-danger') || alert.classList.contains('alert-error')) type = 'error';
            else if (alert.classList.contains('alert-warning')) type = 'warning';

            const title = type === 'success' ? 'Success!' : (type === 'error' ? 'Oops!' : (type === 'warning' ? 'Warning' : 'Notification'));
            const message = alert.textContent.trim();

            if (message) {
                showAlertModal(type, title, message);
                alert.style.display = 'none';
                alert.classList.add('modalized');
            }
        });
    }
});
</script>
