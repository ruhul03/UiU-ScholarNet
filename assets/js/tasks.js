/* tasks.js - Tasks Page Scripts */

function openModal() {
    const modal = document.getElementById('taskModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal() {
    const modal = document.getElementById('taskModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function setPriority(level, btn) {
    const priorityInput = document.getElementById('task_priority');
    if (priorityInput) {
        priorityInput.value = level;
    }
    const btns = document.querySelectorAll('.priority-btn');
    btns.forEach(function(b) {
        b.classList.remove('active');
    });
    btn.classList.add('active');
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
};
