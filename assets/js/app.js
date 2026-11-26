/**
 * Global Application JavaScript Utilities
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss standard alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert.alert-dismissible');
    alerts.forEach((alert) => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 5000);
    });

    // Copy to clipboard helper buttons
    const copyBtns = document.querySelectorAll('[data-copy-target]');
    copyBtns.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-copy-target');
            const targetEl = document.getElementById(targetId);
            if (!targetEl) return;

            const textToCopy = targetEl.value || targetEl.innerText;
            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-check text-success"></i> Copied!';
                btn.classList.add('btn-success', 'text-white');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success', 'text-white');
                }, 2000);
            }).catch((err) => {
                console.error('Failed to copy: ', err);
            });
        });
    });
});
