/* auth.js - Authentication Page Scripts */

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.password-field').forEach((field) => {
        const input = field.querySelector('input[type="password"], input[data-password-input]');
        const toggle = field.querySelector('.password-toggle');
        const icon = toggle ? toggle.querySelector('i') : null;

        if (!input || !toggle || !icon) {
            return;
        }

        input.setAttribute('data-password-input', 'true');

        toggle.addEventListener('click', () => {
            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';
            toggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
            toggle.setAttribute('title', shouldShow ? 'Hide password' : 'Show password');
            icon.classList.toggle('fa-eye', !shouldShow);
            icon.classList.toggle('fa-eye-slash', shouldShow);
        });
    });

    // Student/Faculty toggle functionality
    const studentToggle = document.getElementById('studentToggle');
    const facultyToggle = document.getElementById('facultyToggle');
    const roleInput = document.getElementById('roleInput');

    if (studentToggle && facultyToggle) {
        studentToggle.addEventListener('click', () => {
            studentToggle.classList.add('active');
            facultyToggle.classList.remove('active');
            if (roleInput) {
                roleInput.value = studentToggle.getAttribute('data-role') || 'student';
            }
        });

        facultyToggle.addEventListener('click', () => {
            facultyToggle.classList.add('active');
            studentToggle.classList.remove('active');
            if (roleInput) {
                roleInput.value = facultyToggle.getAttribute('data-role') || 'faculty';
            }
        });

        if (roleInput) {
            roleInput.value = studentToggle.getAttribute('data-role') || 'student';
        }
    }

    const interestsContainer = document.getElementById('interestsContainer');
    const interestsHidden = document.getElementById('interestsHidden');
    const interestInput = document.getElementById('interestInput');
    const addInterestBtn = document.getElementById('addInterestBtn');

    if (interestsContainer && interestsHidden && interestInput && addInterestBtn) {
        let interests = interestsHidden.value
            .split(',')
            .map(function(item) { return item.trim(); })
            .filter(Boolean);

        function syncHiddenField() {
            interestsHidden.value = interests.join(',');
        }

        function renderInterests() {
            interestsContainer.innerHTML = '';

            interests.forEach(function(interest, index) {
                const tag = document.createElement('button');
                tag.type = 'button';
                tag.className = 'tag tag-button';
                tag.setAttribute('data-index', index.toString());
                tag.innerHTML = `${interest} <i class="fa-solid fa-xmark"></i>`;
                interestsContainer.appendChild(tag);
            });

            syncHiddenField();
        }

        function addInterest() {
            const value = interestInput.value.trim();
            if (!value) {
                return;
            }

            const alreadyExists = interests.some(function(item) {
                return item.toLowerCase() === value.toLowerCase();
            });

            if (!alreadyExists) {
                interests.push(value);
                renderInterests();
            }
            interestInput.value = '';
        }

        addInterestBtn.addEventListener('click', addInterest);
        interestInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addInterest();
            }
        });

        interestsContainer.addEventListener('click', function(event) {
            const target = event.target.closest('.tag-button');
            if (!target) {
                return;
            }
            const index = Number(target.getAttribute('data-index'));
            if (!Number.isNaN(index)) {
                interests.splice(index, 1);
                renderInterests();
            }
        });

        renderInterests();
    }
});
