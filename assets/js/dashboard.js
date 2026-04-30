/* dashboard.js - Dashboard Page Scripts */

function openProjectsModal() {
    const modal = document.getElementById('projectsModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeProjectsModal() {
    const modal = document.getElementById('projectsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function openCreateModal() {
    const modal = document.getElementById('createProjectModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeCreateModal() {
    const modal = document.getElementById('createProjectModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
};
