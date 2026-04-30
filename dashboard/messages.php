<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

$channel = isset($_GET['channel']) ? (string)$_GET['channel'] : 'general';
if (!preg_match('/^[a-z0-9_-]{1,50}$/', $channel)) {
    $channel = 'general';
}

$mstmt = $conn->prepare("
    SELECT m.*, u.full_name
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.channel = ?
    ORDER BY m.created_at ASC
    LIMIT 200
");
$mstmt->bind_param("s", $channel);
$mstmt->execute();
$messages = $mstmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="dash-header dash-header-messages">
            <div class="brand-title">THE ARCHITECTURAL SCHOLAR</div>
            <div class="nav-links">
                <div class="nav-link-item">
                    <span>Directory</span>
                    <span>Resources</span>
                </div>
                <div class="header-actions">
                    <i class="fa-regular fa-bell"></i>
                    <i class="fa-solid fa-rotate"></i>
                    <button class="btn btn-primary invite-btn">Invite</button>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" class="header-avatar">
                </div>
            </div>
        </header>

        <div class="messages-layout">
            <!-- Left: Channels -->
            <aside class="comm-sidebar">
                <section>
                    <h4>TEAM CHANNELS <i class="fa-solid fa-circle-plus"></i></h4>
                    <div class="channel-item active">
                        <div class="channel-badge channel-badge-blue">ML</div>
                        <div>
                            <div class="channel-name">Modernist Lab</div>
                            <div class="channel-preview">The paper draft is ready...</div>
                        </div>
                    </div>
                    <div class="channel-item">
                        <div class="channel-badge channel-badge-orange">AS</div>
                        <div>
                            <div class="channel-name">Architectural Studies</div>
                            <div class="channel-preview">Meeting scheduled for tomorrow...</div>
                        </div>
                    </div>
                    <div class="channel-item">
                        <div class="channel-badge channel-badge-lightblue">QD</div>
                        <div>
                            <div class="channel-name">Quantitative Data</div>
                            <div class="channel-preview">Please check the latest CSV...</div>
                        </div>
                    </div>
                </section>

                <section style="margin-top: 4rem;">
                    <h4>DIRECT MESSAGES</h4>
                    <div class="dm-list">
                        <div class="dm-item">
                            <img src="https://ui-avatars.com/api/?name=Dr+Messi&background=000&color=fff" class="dm-avatar">
                            <span>Dr. Messi</span>
                            <div class="online-indicator"></div>
                        </div>
                        <div class="dm-item inactive">
                            <img src="https://ui-avatars.com/api/?name=Dr+Cristiano&background=eee&color=000" class="dm-avatar">
                            <span>Dr. Cristiano</span>
                        </div>
                    </div>
                </section>
            </aside>

            <!-- Middle: Chat Area -->
            <div class="chat-area">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="channel-badge channel-badge-blue">ML</div>
                        <div>
                            <div class="chat-title">Modernist Lab</div>
                            <div class="chat-meta"><i class="fa-solid fa-circle online-dot"></i> 12 Team Members Online</div>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <i class="fa-solid fa-phone"></i>
                        <i class="fa-solid fa-video"></i>
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                </div>

                <div class="chat-messages">
                    <div class="channel-tag">Channel: <?php echo htmlspecialchars($channel); ?></div>

                    <?php if ($messages && $messages->num_rows > 0): ?>
                        <?php while($msg = $messages->fetch_assoc()): 
                            $is_self = ((int)$msg['sender_id'] === (int)$user_id);
                            $name = $is_self ? 'You' : (string)$msg['full_name'];
                        ?>
                            <div class="msg-bubble <?php echo $is_self ? 'self' : ''; ?>">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($name); ?>&background=<?php echo $is_self ? '0a1128' : '000'; ?>&color=fff">
                                <div class="msg-content-wrapper">
                                    <div class="msg-meta">
                                        <span class="name"><?php echo htmlspecialchars($name); ?></span>
                                        <span class="time"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></span>
                                    </div>
                                    <div class="msg-text">
                                        <?php echo nl2br(htmlspecialchars((string)$msg['message'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-messages">No messages yet. Start the conversation.</div>
                    <?php endif; ?>
                </div>

                <div class="chat-input-wrapper">
                    <form class="chat-input-box chat-form" action="../actions/post_message.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="channel" value="<?php echo htmlspecialchars($channel, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-solid fa-plus" style="opacity: 0.3;"></i>
                        <i class="fa-regular fa-face-smile" style="opacity: 0.3;"></i>
                        <input name="message" type="text" class="chat-input" placeholder="Write your message here..." autocomplete="off" maxlength="2000" required>
                        <i class="fa-solid fa-microphone" style="opacity: 0.3;"></i>
                        <button type="submit" class="send-btn">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                    <div class="chat-shortcuts">
                        <span>@ MENTION</span>
                        <span><i class="fa-solid fa-paperclip"></i> ATTACH</span>
                        <span class="shortcut-hint">Press Enter to send, Shift+Enter for new line.</span>
                    </div>
                </div>
            </div>

            <!-- Right: Info/Member Sidebar -->
            <aside class="info-sidebar">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <div class="channel-badge info-badge channel-badge-blue">
                        ML
                        <div class="verified-badge"><i class="fa-solid fa-check"></i></div>
                    </div>
                    <h3 class="info-title">Modernist Lab</h3>
                    <p class="info-desc">Cross-disciplinary research on 20th-century preservation.</p>
                </div>

                <section>
                    <h4>PINNED ARTIFACTS</h4>
                    <div class="pinned-card">
                        <i class="fa-regular fa-file-pdf pinned-icon-pdf"></i>
                        <div>
                            <div class="pinned-name">Research_Brief_v2.pdf</div>
                            <div class="pinned-meta">BY DR. THORNE • 2D AGO</div>
                        </div>
                    </div>
                    <div class="pinned-card">
                        <i class="fa-solid fa-link pinned-icon-link"></i>
                        <div>
                            <div class="pinned-name">Archive Access Portal</div>
                            <div class="pinned-meta">EXTERNAL LINK</div>
                        </div>
                    </div>
                </section>

                <section style="margin-top: 3rem;">
                    <h4>SHARED MEDIA</h4>
                    <div class="media-grid">
                        <div class="media-item"></div>
                        <div class="media-item dark"></div>
                        <div class="media-item gray"></div>
                        <div class="media-item light">+12</div>
                    </div>
                </section>

                <button class="btn btn-outline view-settings-btn">View Team Settings</button>
            </aside>
        </div>

        <!-- Floating Edit Button -->
        <div class="floating-edit-btn">
            <i class="fa-solid fa-pen-nib"></i>
        </div>
    </main>

</body>
</html>
