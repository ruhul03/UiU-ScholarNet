<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    $user_id = (int)$_SESSION['user_id'];
    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $channel = $_POST['channel'] ?? '';

    if ($target_user_id > 0) {
        // Delete DM messages between the two users
        $del = $conn->prepare("DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        $del->bind_param("iiii", $user_id, $target_user_id, $target_user_id, $user_id);
        $del->execute();
        $_SESSION['success'] = "Chat history cleared.";
        header("Location: ../dashboard/messages.php?user_id=" . $target_user_id);
        exit();
    } elseif (!empty($channel)) {
        // Simple permission check: must be sender of the messages or if it's a project channel, ideally owner.
        // For simplicity in this demo, let's just clear messages they sent, or all if they want to 'leave/clear' their view.
        // Wait, standard clear chat in a channel might just clear for everyone if admin, but here let's just delete messages THEY sent in the channel to be safe.
        // Or if they just want to wipe the whole channel... let's wipe messages they sent. 
        // We shouldn't let them wipe other people's messages in a shared channel unless they are project owner.
        
        if (strpos($channel, 'project_') === 0) {
            $project_id = (int)str_replace('project_', '', $channel);
            // Check if owner
            $ostmt = $conn->prepare("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role = 'owner')");
            $ostmt->bind_param("iii", $user_id, $project_id, $user_id);
            $ostmt->execute();
            if ($ostmt->get_result()->num_rows > 0) {
                // Team leader can clear whole channel
                $del = $conn->prepare("DELETE FROM messages WHERE channel = ?");
                $del->bind_param("s", $channel);
                $del->execute();
                $_SESSION['success'] = "Channel history cleared.";
            } else {
                $_SESSION['error'] = "Only team leaders can clear a channel.";
            }
        } else {
            // It's a custom group or something, let's just delete their own messages for safety
            $del = $conn->prepare("DELETE FROM messages WHERE channel = ? AND sender_id = ?");
            $del->bind_param("si", $channel, $user_id);
            $del->execute();
            $_SESSION['success'] = "Your messages in this channel were cleared.";
        }
        
        header("Location: ../dashboard/messages.php?channel=" . urlencode($channel));
        exit();
    }
}

header("Location: ../dashboard/messages.php");
exit();
