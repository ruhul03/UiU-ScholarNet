/* auth.js - Authentication Page Scripts */

document.addEventListener('DOMContentLoaded', function() {
    // Student/Faculty toggle functionality
    const studentToggle = document.getElementById('studentToggle');
    const facultyToggle = document.getElementById('facultyToggle');

    if (studentToggle && facultyToggle) {
        studentToggle.addEventListener('click', () => {
            studentToggle.classList.add('active');
            facultyToggle.classList.remove('active');
        });

        facultyToggle.addEventListener('click', () => {
            facultyToggle.classList.add('active');
            studentToggle.classList.remove('active');
        });
    }
});
