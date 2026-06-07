// assets/js/profile_popup.js

document.addEventListener('DOMContentLoaded', function() {
    const popupContainer = document.getElementById('global-profile-popup');
    
    if (!popupContainer) return;

    // Attach click listener to document to handle dynamically added elements
    document.addEventListener('click', async function(e) {
        // Find if the clicked element or its parent has the trigger class
        const trigger = e.target.closest('.user-profile-trigger');
        
        if (trigger) {
            e.preventDefault();
            const userId = trigger.getAttribute('data-user-id');
            if (!userId) return;

            openPopup();
            showLoader();

            try {
                const response = await fetch(`../actions/get_user_profile.php?user_id=${userId}`);
                const data = await response.json();
                
                if (data.error) {
                    showError(data.error);
                } else {
                    renderProfile(userId, data);
                }
            } catch (err) {
                showError("Failed to load profile.");
            }
        }
        
        // Handle close button or click outside
        if (e.target.matches('.profile-popup-close') || e.target.matches('.profile-popup-overlay')) {
            closePopup();
        }
    });

    function openPopup() {
        popupContainer.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closePopup() {
        popupContainer.classList.remove('active');
        document.body.style.overflow = '';
    }

    function showLoader() {
        popupContainer.innerHTML = `
            <div class="profile-popup-card" style="display: flex; align-items: center; justify-content: center; height: 300px;">
                <button class="profile-popup-close" style="color: #333; background: #eee;"><i class="fa-solid fa-xmark"></i></button>
                <div class="profile-popup-loader"></div>
            </div>
        `;
    }

    function showError(msg) {
        popupContainer.innerHTML = `
            <div class="profile-popup-card" style="display: flex; align-items: center; justify-content: center; height: 300px; flex-direction: column; color: #dc3545;">
                <button class="profile-popup-close" style="color: #333; background: #eee;"><i class="fa-solid fa-xmark"></i></button>
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>${msg}</p>
            </div>
        `;
    }

    function renderProfile(userId, data) {
        let skillsHtml = '';
        if (data.skills && data.skills.length > 0) {
            skillsHtml = '<div class="profile-popup-skills">';
            data.skills.forEach(function(skill) {
                skillsHtml += `<span class="profile-popup-skill">${skill}</span>`;
            });
            skillsHtml += '</div>';
        } else {
            skillsHtml = '<div class="profile-popup-skills"><span class="profile-popup-skill" style="background: transparent; color: #888;">No skills listed</span></div>';
        }

        popupContainer.innerHTML = `
            <div class="profile-popup-card">
                <div class="profile-popup-header">
                    <button class="profile-popup-close"><i class="fa-solid fa-xmark"></i></button>
                    <div class="profile-popup-avatar-wrapper">
                        <img src="${data.avatar_url}" alt="Avatar">
                    </div>
                </div>
                <div class="profile-popup-body">
                    <h3 class="profile-popup-name">${data.full_name}</h3>
                    <div class="profile-popup-role">${data.role} &bull; ${data.department}</div>
                    
                    <div class="profile-popup-stats">
                        <div class="profile-popup-stat">
                            <span class="profile-popup-stat-val">${data.reputation}</span>
                            <span class="profile-popup-stat-label">Reputation</span>
                        </div>
                        <div class="profile-popup-stat">
                            <span class="profile-popup-stat-val">${data.joined_date}</span>
                            <span class="profile-popup-stat-label">Joined</span>
                        </div>
                    </div>

                    ${skillsHtml}

                    <div class="profile-popup-actions">
                        <a href="messages.php?user=${userId}" class="profile-popup-btn profile-popup-btn-primary">
                            <i class="fa-solid fa-paper-plane"></i> Direct Message
                        </a>
                        <a href="profile.php?id=${userId}" class="profile-popup-btn profile-popup-btn-secondary">
                            <i class="fa-regular fa-user"></i> Full Profile
                        </a>
                    </div>
                </div>
            </div>
        `;
    }
});
