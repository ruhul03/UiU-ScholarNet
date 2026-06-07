document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('openProfileEdit');
    const modal = document.getElementById('profileEditModal');
    const closeBtn = document.getElementById('closeProfileEdit');
    const cancelBtn = document.getElementById('cancelProfileEdit');

    if (!editBtn || !modal) {
        return;
    }

    function closeModal() {
        modal.classList.add('modal-hidden');
    }

    editBtn.addEventListener('click', function() {
        modal.classList.remove('modal-hidden');
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
});
