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
        <header class="dash-header" style="margin-bottom: 2rem;">
            <div style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 700;">THE ARCHITECTURAL SCHOLAR</div>
            <div style="display: flex; gap: 2rem; align-items: center;">
                <div style="display: flex; gap: 2rem; font-size: 0.9rem; font-weight: 700; opacity: 0.6;">
                    <span>Directory</span>
                    <span>Resources</span>
                </div>
                <div style="display: flex; gap: 1.5rem; border-left: 1px solid #eee; padding-left: 2rem;">
                    <i class="fa-regular fa-bell"></i>
                    <i class="fa-solid fa-rotate"></i>
                    <button class="btn btn-primary" style="padding: 0.5rem 1.5rem; font-size: 0.8rem; border-radius: 4px;">Invite</button>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" style="width: 35px; height: 35px; border-radius: 50%;">
                </div>
            </div>
        </header>

        <div class="messages-layout">
            <!-- Left: Channels -->
            <aside class="comm-sidebar">
                <section>
                    <h4>TEAM CHANNELS <i class="fa-solid fa-circle-plus"></i></h4>
                    <div class="channel-item active">
                        <div class="channel-badge" style="background: #e1ecf4; color: #39739d;">ML</div>
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 700;">Modernist Lab</div>
                            <div style="font-size: 0.65rem; opacity: 0.5;">The paper draft is ready...</div>
                        </div>
                    </div>
                    <div class="channel-item">
                        <div class="channel-badge" style="background: #fff3e0; color: #ef6c00;">AS</div>
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 700;">Architectural Studies</div>
                            <div style="font-size: 0.65rem; opacity: 0.5;">Meeting scheduled for tomorrow...</div>
                        </div>
                    </div>
                    <div class="channel-item">
                        <div class="channel-badge" style="background: #e3f2fd; color: #1565c0;">QD</div>
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 700;">Quantitative Data</div>
                            <div style="font-size: 0.65rem; opacity: 0.5;">Please check the latest CSV...</div>
                        </div>
                    </div>
                </section>

                <section style="margin-top: 4rem;">
                    <h4>DIRECT MESSAGES</h4>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.85rem; font-weight: 700;">
                            <img src="https://ui-avatars.com/api/?name=Dr+Messi&background=000&color=fff" style="width: 30px; height: 30px; border-radius: 50%;">
                            <span>Dr. Messi</span>
                            <div style="width: 8px; height: 8px; background: #4CAF50; border-radius: 50%; margin-left: auto;"></div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.85rem; font-weight: 700; opacity: 0.6;">
                            <img src="https://ui-avatars.com/api/?name=Dr+Cristiano&background=eee&color=000" style="width: 30px; height: 30px; border-radius: 50%;">
                            <span>Dr. Cristiano</span>
                        </div>
                    </div>
                </section>
            </aside>

            <!-- Middle: Chat Area -->
            <div class="chat-area">
                <div class="chat-header">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="channel-badge" style="background: #e1ecf4; color: #39739d;">ML</div>
                        <div>
                            <div style="font-weight: 800;">Modernist Lab</div>
                            <div style="font-size: 0.75rem; opacity: 0.5;"><i class="fa-solid fa-circle" style="color: #4CAF50; font-size: 0.5rem;"></i> 12 Team Members Online</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1.5rem; opacity: 0.5;">
                        <i class="fa-solid fa-phone"></i>
                        <i class="fa-solid fa-video"></i>
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                </div>

                <div class="chat-messages">
                    <div style="text-align: center; font-size: 0.7rem; font-weight: 800; opacity: 0.3; text-transform: uppercase; letter-spacing: 1px;">Channel: <?php echo htmlspecialchars($channel); ?></div>

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
                        <div style="text-align:center; opacity:0.4; padding: 2rem 0;">No messages yet. Start the conversation.</div>
                    <?php endif; ?>
                </div>

                <div class="chat-input-wrapper">
                    <form class="chat-input-box" action="../actions/post_message.php" method="POST" style="display:flex; align-items:center; gap: 0.75rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="channel" value="<?php echo htmlspecialchars($channel, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-solid fa-plus" style="opacity: 0.3;"></i>
                        <i class="fa-regular fa-face-smile" style="opacity: 0.3;"></i>
                        <input name="message" type="text" placeholder="Write your message here..." style="flex: 1; border: none; background: transparent; outline: none; font-family: var(--font-body);" autocomplete="off" maxlength="2000" required>
                        <i class="fa-solid fa-microphone" style="opacity: 0.3;"></i>
                        <button type="submit" style="background: #007bff; color: white; width: 40px; height: 40px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                    <div style="display: flex; gap: 1.5rem; margin-top: 1rem; font-size: 0.65rem; font-weight: 800; opacity: 0.4;">
                        <span>@ MENTION</span>
                        <span><i class="fa-solid fa-paperclip"></i> ATTACH</span>
                        <span style="margin-left: auto;">Press Enter to send, Shift+Enter for new line.</span>
                    </div>
                </div>
            </div>

            <!-- Right: Info/Member Sidebar -->
            <aside class="info-sidebar">
                <div style="text-align: center; margin-bottom: 3rem;">
                    <div class="channel-badge" style="width: 80px; height: 80px; font-size: 1.5rem; margin: 0 auto 1.5rem; background: #e1ecf4; color: #39739d; position: relative;">
                        ML
                        <div style="position: absolute; bottom: 0; right: 0; width: 20px; height: 20px; background: #007bff; border-radius: 50%; border: 3px solid white; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; color: white;"><i class="fa-solid fa-check"></i></div>
                    </div>
                    <h3 style="font-size: 1.4rem; margin-bottom: 0.5rem;">Modernist Lab</h3>
                    <p style="font-size: 0.8rem; opacity: 0.5;">Cross-disciplinary research on 20th-century preservation.</p>
                </div>

                <section>
                    <h4>PINNED ARTIFACTS</h4>
                    <div class="pinned-card">
                        <i class="fa-regular fa-file-pdf" style="font-size: 1.5rem; color: #007bff;"></i>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 700;">Research_Brief_v2.pdf</div>
                            <div style="font-size: 0.6rem; opacity: 0.4;">BY DR. THORNE • 2D AGO</div>
                        </div>
                    </div>
                    <div class="pinned-card">
                        <i class="fa-solid fa-link" style="font-size: 1.5rem; color: #ef6c00;"></i>
                        <div>
                            <div style="font-size: 0.75rem; font-weight: 700;">Archive Access Portal</div>
                            <div style="font-size: 0.6rem; opacity: 0.4;">EXTERNAL LINK</div>
                        </div>
                    </div>
                </section>

                <section style="margin-top: 3rem;">
                    <h4>SHARED MEDIA</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                        <div style="aspect-ratio: 1; background: #000; border-radius: 4px;"></div>
                        <div style="aspect-ratio: 1; background: #333; border-radius: 4px;"></div>
                        <div style="aspect-ratio: 1; background: #666; border-radius: 4px;"></div>
                        <div style="aspect-ratio: 1; background: #f5f5f5; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; opacity: 0.6;">+12</div>
                    </div>
                </section>

                <button class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 0.75rem; font-weight: 700; margin-top: 3rem;">View Team Settings</button>
            </aside>
        </div>

        <!-- Floating Edit Button -->
        <div style="position: fixed; bottom: 2rem; right: 2rem; width: 60px; height: 60px; background: #6b5b2a; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); cursor: pointer;">
            <i class="fa-solid fa-pen-nib"></i>
        </div>
    </main>

</body>
</html>
