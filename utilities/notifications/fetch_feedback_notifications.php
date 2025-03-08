<?php
function fetchFeedbackNotifications($userId, $mysqli) {
    $stmt = $mysqli->prepare("
        SELECT 
            n.id,
            n.commenter_id,
            u.name,
            n.feedback_content,
            n.blog_id,
            b.title as blog_title
        FROM 
            blog_feedback_notifications n
        JOIN 
            user u ON n.commenter_id = u.user_id
        JOIN 
            blogs b ON n.blog_id = b.id
        WHERE 
            n.receiver_id = ?
        ORDER BY 
            n.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $notifications;
}
?>
