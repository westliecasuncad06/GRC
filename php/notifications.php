<?php
require_once 'db.php';

class NotificationManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a notification for a user
     */
    public function createNotification($user_id, $user_type, $title, $message, $type, $related_request_id = null, $related_class_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, user_type, title, message, type, related_request_id, related_class_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$user_id, $user_type, $title, $message, $type, $related_request_id, $related_class_id]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("Error creating notification: " . implode(", ", $errorInfo));
                return false;
            }
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Exception creating notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notifications for a user
     */
    public function getNotifications($user_id, $user_type, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications
                WHERE user_id = ? AND user_type = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $user_type, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($user_id, $user_type) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM notifications
                WHERE user_id = ? AND user_type = ? AND is_read = 0
            ");
            $stmt->execute([$user_id, $user_type]);
            return $stmt->fetch()['count'];
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id, $user_type) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1
                WHERE notification_id = ? AND user_id = ? AND user_type = ?
            ");
            return $stmt->execute([$notification_id, $user_id, $user_type]);
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id, $user_type) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications SET is_read = 1
                WHERE user_id = ? AND user_type = ? AND is_read = 0
            ");
            return $stmt->execute([$user_id, $user_type]);
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification by ID
     */
    public function getNotificationById($notification_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM notifications WHERE notification_id = ?");
            $stmt->execute([$notification_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting notification by ID: " . $e->getMessage());
            return null;
        }
    }
}

// Initialize notification manager
$notificationManager = new NotificationManager($pdo);
?>
