/* auth.js - Authentication Page Scripts */

document.addEventListener('DOMContentLoaded', function() {
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
            .map((item) => item.trim())
            .filter(Boolean);

        const syncHiddenField = () => {
            interestsHidden.value = interests.join(',');
        };

        const renderInterests = () => {
            interestsContainer.innerHTML = '';

            interests.forEach((interest, index) => {
                const tag = document.createElement('button');
                tag.type = 'button';
                tag.className = 'tag tag-button';
                tag.setAttribute('data-index', index.toString());
                tag.innerHTML = `${interest} <i class="fa-solid fa-xmark"></i>`;
                interestsContainer.appendChild(tag);
            });

            syncHiddenField();
        };

        const addInterest = () => {
            const value = interestInput.value.trim();
            if (!value) {
                return;
            }

            const alreadyExists = interests.some((item) => item.toLowerCase() === value.toLowerCase());
            if (!alreadyExists) {
                interests.push(value);
                renderInterests();
            }
            interestInput.value = '';
        };

        addInterestBtn.addEventListener('click', addInterest);
        interestInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addInterest();
            }
        });

        interestsContainer.addEventListener('click', (event) => {
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
