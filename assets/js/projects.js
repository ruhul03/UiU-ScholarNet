/* projects.js - Projects Page Scripts */

function openModal() {
    const modal = document.getElementById('projectModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal() {
    const modal = document.getElementById('projectModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal or dropdown when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
    
    // Close dropdowns if clicking outside the entire options wrapper
    if (!event.target.closest('.options-wrapper')) {
        const dropdowns = document.querySelectorAll('.options-dropdown');
        dropdowns.forEach(function(d) {
            d.style.display = 'none';
        });
    }
};

function toggleProjectOptions(event, id) {
    event.stopPropagation();
    const dropdown = document.getElementById('dropdown-' + id);
    if (!dropdown) return;

    // Check current state
    const isCurrentlyVisible = window.getComputedStyle(dropdown).display === 'flex';
    
    // Close all dropdowns first
    document.querySelectorAll('.options-dropdown').forEach(function(d) {
        d.style.display = 'none';
    });
    
    // Toggle based on previous state
    if (!isCurrentlyVisible) {
        dropdown.style.display = 'flex';
    }
}

// Handle project actions via delegated events
document.addEventListener('click', function(e) {

    
    // Delete action
    if (e.target.closest('.delete-trigger')) {
        e.preventDefault();
        const id = e.target.closest('.delete-trigger').dataset.id;
        if (confirm('Are you sure you want to permanently remove this project from the institutional archive?')) {
            const form = document.getElementById('delete-form-' + id);
            if (form) form.submit();
        }
    }
});
