<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    $user_id = (int)$_SESSION['user_id'];
    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $channel = $_POST['channel'] ?? '';

    if ($target_user_id > 0) {
        // Delete DM messages between the two users
        db_query(
            "DELETE FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)", 
            [$user_id, $target_user_id, $target_user_id, $user_id], 
            "iiii"
        );
        $_SESSION['success'] = "Chat history cleared.";
        header("Location: ../dashboard/messages.php?user_id=" . $target_user_id);
        exit();
    } elseif (!empty($channel)) {
        // Clear channel message history
        if (strpos($channel, 'project_') === 0) {
            $project_id = (int)str_replace('project_', '', $channel);
            
            // Check if the current user is the owner or creator of the project
            $ostmt = db_query(
                "SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role = 'owner')", 
                [$user_id, $project_id, $user_id], 
                "iii"
            );
            if ($ostmt && $ostmt->num_rows > 0) {
                // Team leader can clear whole channel
                db_query("DELETE FROM messages WHERE channel = ?", [$channel], "s");
                $_SESSION['success'] = "Channel history cleared.";
            } else {
                $_SESSION['error'] = "Only team leaders can clear a channel.";
            }
        } else {
            // In public/custom channels, users can only clear their own messages
            db_query("DELETE FROM messages WHERE channel = ? AND sender_id = ?", [$channel, $user_id], "si");
            $_SESSION['success'] = "Your messages in this channel were cleared.";
        }
        
        header("Location: ../dashboard/messages.php?channel=" . urlencode($channel));
        exit();
    }
}

header("Location: ../dashboard/messages.php");
exit();
