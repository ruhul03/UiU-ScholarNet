/* collaboration.js - Collaboration Page Scripts */

function openModal() {
    const modal = document.getElementById('collabModal');
    if (modal) {
        modal.classList.remove('modal-hidden');
    }
}

function closeModal() {
    const modal = document.getElementById('collabModal');
    if (modal) {
        modal.classList.add('modal-hidden');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('collabModal');
    const filterForm = document.getElementById('collabFilterForm');
    const viewInput = document.getElementById('viewInput');
    const viewButtons = document.querySelectorAll('.view-btn[data-view]');

    if (modal) {
        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    if (filterForm) {
        const selects = filterForm.querySelectorAll('select');
        selects.forEach(function(select) {
            select.addEventListener('change', function () {
                filterForm.submit();
            });
        });
    }

    if (viewInput && filterForm && viewButtons.length > 0) {
        viewButtons.forEach(function(button) {
            button.addEventListener('click', function () {
                const view = button.getAttribute('data-view');
                if (!view) {
                    return;
                }
                viewInput.value = view;
                filterForm.submit();
            });
        });
    }
});
