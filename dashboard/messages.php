<?php
// ==========================================
// STEP 1: INITIALIZATION & SECURITY
// ==========================================
// We use simple procedural PHP for beginner developers to easily trace the logic.
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

// ==========================================
// STEP 2: DEFINE STATIC CHANNELS
// ==========================================
$channels_map = [
    'general' => [
        'name' => 'General Discussion',
        'badge' => 'GD',
        'color' => 'bg-blue',
        'desc' => 'General chat for all researchers.'
    ],
    'modernist_lab' => [
        'name' => 'Modernist Lab',
        'badge' => 'ML',
        'color' => 'bg-orange',
        'desc' => 'Research on 20th-century preservation.'
    ],
    'architectural_studies' => [
        'name' => 'Architectural Studies',
        'badge' => 'AS',
        'color' => 'bg-gray',
        'desc' => 'Focus group for architecture.'
    ]
];

// Add custom dynamic channels from database
$custom_channels_res = mysqli_query($conn, "SELECT DISTINCT channel FROM messages WHERE channel NOT IN ('general', 'modernist_lab', 'architectural_studies')");
if ($custom_channels_res) {
    while ($row = mysqli_fetch_assoc($custom_channels_res)) {
        $c_name = $row['channel'];
        $channels_map[$c_name] = [
            'name' => ucwords(str_replace('_', ' ', $c_name)),
            'badge' => strtoupper(substr($c_name, 0, 2)),
            'color' => 'bg-blue',
            'desc' => 'Custom user-created channel.'
        ];
    }
}

// ==========================================
// STEP 3: ROUTING & FETCHING MESSAGES
// ==========================================
$chat_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$channel = '';
$chat_target_info = [];
$messages = null;

// Case A: Direct Messaging (DM)
if ($chat_user_id > 0) {
    $upstmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $upstmt->bind_param("i", $chat_user_id);
    $upstmt->execute();
    $chat_user_data = $upstmt->get_result()->fetch_assoc();
    
    if ($chat_user_data) {
        $chat_target_info = [
            'name' => htmlspecialchars($chat_user_data['full_name']),
            'badge' => strtoupper(substr($chat_user_data['full_name'], 0, 2)),
            'color' => 'bg-orange',
            'desc' => ucfirst(htmlspecialchars($chat_user_data['role']))
        ];
        
        $mstmt = $conn->prepare("
            SELECT m.*, u.full_name
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC LIMIT 100
        ");
        $mstmt->bind_param("iiii", $user_id, $chat_user_id, $chat_user_id, $user_id);
        $mstmt->execute();
        $messages = $mstmt->get_result();
    } else {
        $chat_user_id = 0; // Fallback to channel
    }
}

// Case B: Channel Chat
if ($chat_user_id === 0) {
    $channel = isset($_GET['channel']) ? (string)$_GET['channel'] : 'general';
    if (!preg_match('/^[a-z0-9_-]{1,50}$/', $channel)) {
        $channel = 'general';
    }
    
    $chat_target_info = $channels_map[$channel] ?? [
        'name' => ucwords(str_replace('_', ' ', $channel)),
        'badge' => strtoupper(substr($channel, 0, 2)),
        'color' => 'bg-blue',
        'desc' => 'Custom channel.'
    ];
    
    $mstmt = $conn->prepare("
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.channel = ? AND m.receiver_id IS NULL
        ORDER BY m.created_at ASC LIMIT 100
    ");
    $mstmt->bind_param("s", $channel);
    $mstmt->execute();
    $messages = $mstmt->get_result();
}

// Fetch users for the sidebar
$dm_users_res = mysqli_query($conn, "SELECT id, full_name, role FROM users WHERE id != " . (int)$user_id . " ORDER BY full_name ASC LIMIT 10");

// ==========================================
// STEP 4: PRESENTATION (HTML UI)
// ==========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | UIU ScholarNet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content" style="padding-top: 1rem;">
        
        <div class="messages-layout">
            
            <!-- LEFT COLUMN: Navigation -->
            <aside class="comm-sidebar">
                <div class="comm-sidebar-header">
                    <h2>Messages</h2>
                </div>
                
                <div class="comm-sidebar-list">
                    <h4>TEAM CHANNELS</h4>
                    <?php foreach ($channels_map as $key => $info): ?>
                        <a href="?channel=<?php echo urlencode($key); ?>" class="channel-item <?php echo ($channel === $key) ? 'active' : ''; ?>">
                            <div class="avatar-badge <?php echo htmlspecialchars($info['color']); ?>"><?php echo htmlspecialchars($info['badge']); ?></div>
                            <div class="item-info">
                                <div class="item-name"># <?php echo htmlspecialchars($info['name']); ?></div>
                                <div class="item-preview"><?php echo htmlspecialchars($info['desc']); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>

                    <h4>DIRECT MESSAGES</h4>
                    <?php if ($dm_users_res && $dm_users_res->num_rows > 0): ?>
                        <?php while ($dm_user = $dm_users_res->fetch_assoc()): ?>
                            <a href="?user_id=<?php echo $dm_user['id']; ?>" class="dm-item <?php echo ($chat_user_id === (int)$dm_user['id']) ? 'active' : ''; ?>">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($dm_user['full_name']); ?>&background=<?php echo ($chat_user_id === (int)$dm_user['id']) ? '0a1128' : 'f8f7f2'; ?>&color=<?php echo ($chat_user_id === (int)$dm_user['id']) ? 'fff' : '0a1128'; ?>" class="avatar-badge">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($dm_user['full_name']); ?></div>
                                    <div class="item-preview"><?php echo htmlspecialchars($dm_user['role']); ?></div>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </aside>

            <!-- MIDDLE COLUMN: Chat Area -->
            <div class="chat-area">
                <div class="chat-header">
                    <div class="avatar-badge <?php echo htmlspecialchars($chat_target_info['color'] ?? 'bg-blue'); ?>"><?php echo htmlspecialchars($chat_target_info['badge']); ?></div>
                    <div class="chat-title-group">
                        <div class="chat-title"><?php echo htmlspecialchars($chat_target_info['name']); ?></div>
                        <div class="chat-subtitle">Online • <?php echo htmlspecialchars($chat_target_info['desc']); ?></div>
                    </div>
                    <div class="chat-actions">
                        <i class="fa-solid fa-video"></i>
                        <i class="fa-solid fa-phone"></i>
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </div>
                </div>

                <div class="chat-messages" id="chatContainer">
                    <?php if ($messages && $messages->num_rows > 0): ?>
                        <?php while($msg = $messages->fetch_assoc()): 
                            $is_self = ((int)$msg['sender_id'] === (int)$user_id);
                            $sender_name = $is_self ? 'You' : $msg['full_name'];
                        ?>
                            <div class="msg-bubble <?php echo $is_self ? 'self' : ''; ?>">
                                <?php if (!$is_self): ?>
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sender_name); ?>&background=ffffff&color=764ba2">
                                <?php endif; ?>
                                <div class="msg-content">
                                    <div class="msg-text">
                                        <?php echo nl2br(htmlspecialchars((string)$msg['message'])); ?>
                                    </div>
                                    <div class="msg-meta">
                                        <?php echo htmlspecialchars($sender_name); ?> • <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-messages">
                            ✨ Start a beautiful conversation! ✨
                        </div>
                    <?php endif; ?>
                </div>

                <div class="chat-input-wrapper">
                    <form class="chat-form" action="../actions/post_message.php" method="POST" id="chatForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <?php if ($chat_user_id > 0): ?>
                            <input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
                        <?php else: ?>
                            <input type="hidden" name="channel" value="<?php echo htmlspecialchars($channel, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif; ?>
                        
                        <i class="fa-solid fa-plus icon-btn"></i>
                        <input name="message" type="text" class="chat-input" placeholder="Type your message..." autocomplete="off" required>
                        <i class="fa-regular fa-face-smile icon-btn" style="margin-right: 0.5rem;"></i>
                        
                        <button type="submit" class="send-btn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: Context Info Pane -->
            <aside class="info-sidebar">
                <div class="avatar-badge info-badge-lg <?php echo htmlspecialchars($chat_target_info['color'] ?? 'bg-blue'); ?>">
                    <?php echo htmlspecialchars($chat_target_info['badge']); ?>
                </div>
                <h3 class="info-title"><?php echo htmlspecialchars($chat_target_info['name']); ?></h3>
                <p class="info-desc"><?php echo htmlspecialchars($chat_target_info['desc']); ?></p>

                <h4 class="info-section-title">Shared Media</h4>
                <div class="media-grid">
                    <div class="media-item"></div>
                    <div class="media-item"></div>
                    <div class="media-item"></div>
                </div>

                <h4 class="info-section-title">Pinned Artifacts</h4>
                <div class="pinned-card">
                    <i class="fa-solid fa-file-pdf" style="color: var(--primary-color); font-size: 1.5rem;"></i>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-color);">Project_Brief.pdf</div>
                        <div style="font-size: 0.7rem; color: var(--text-color); opacity: 0.6;">Added 2 days ago</div>
                    </div>
                </div>
                <div class="pinned-card">
                    <i class="fa-solid fa-link" style="color: var(--primary-color); font-size: 1.5rem;"></i>
                    <div>
                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-color);">Figma Design</div>
                        <div style="font-size: 0.7rem; color: var(--text-color); opacity: 0.6;">External Link</div>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- JavaScript logic for AJAX messaging (simulated realtime) -->
    <script>
        const chatContainer = document.getElementById('chatContainer');
        if (chatContainer) chatContainer.scrollTop = chatContainer.scrollHeight;

        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(chatForm);
                const inputField = chatForm.querySelector('.chat-input');
                const text = inputField.value.trim();
                if (text === '') return;
                
                const emptyMsg = chatContainer.querySelector('.empty-messages');
                if (emptyMsg) emptyMsg.remove();
                
                const bubble = document.createElement('div');
                bubble.className = 'msg-bubble self temp-bubble';
                bubble.innerHTML = `
                    <div class="msg-content">
                        <div class="msg-text" style="opacity:0.8">${text.replace(/</g, "&lt;").replace(/>/g, "&gt;")}</div>
                        <div class="msg-meta">You • Sending...</div>
                    </div>
                `;
                chatContainer.appendChild(bubble);
                chatContainer.scrollTop = chatContainer.scrollHeight;
                inputField.value = '';
                
                fetch(chatForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        bubble.querySelector('.msg-text').style.opacity = '1';
                        bubble.querySelector('.msg-meta').innerText = 'You • Just now';
                    }
                });
            });
        }
        
        // Polling
        setInterval(() => {
            const inputField = document.querySelector('.chat-input');
            if (document.activeElement !== inputField && inputField.value.trim() === '') {
                window.location.reload();
            }
        }, 15000);
    </script>
</body>
</html>
