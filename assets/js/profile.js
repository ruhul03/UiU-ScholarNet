document.addEventListener('DOMContentLoaded', () => {
    const editBtn = document.getElementById('openProfileEdit');
    const modal = document.getElementById('profileEditModal');
    const closeBtn = document.getElementById('closeProfileEdit');
    const cancelBtn = document.getElementById('cancelProfileEdit');

    if (!editBtn || !modal) {
        return;
    }

    const closeModal = () => {
        modal.classList.add('modal-hidden');
    };

    editBtn.addEventListener('click', () => {
        modal.classList.remove('modal-hidden');
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
});
