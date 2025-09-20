<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

require_once '../php/db.php';
require_once '../php/notifications.php';

$student_id = $_SESSION['user_id'];

// Initialize notification manager
$notificationManager = new NotificationManager($pdo);

// Get notifications for the student
$notifications = $notificationManager->getNotifications($student_id, 'student', 50, 0);

// Get unread count
$unread_count = $notificationManager->getUnreadCount($student_id, 'student');

// Mark all as read when viewing notifications
if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'all') {
    $notificationManager->markAllAsRead($student_id, 'student');
    header('Location: notifications.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Notifications - Global Reciprocal College</title>
    <link rel="stylesheet" href="../css/styles_fixed.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
    <?php include '../includes/navbar_student.php'; ?>
    <?php include '../includes/sidebar_student.php'; ?>

    <main class="main-content">
        <div class="table-header-enhanced">
            <h2 class="table-title-enhanced"><i class="fas fa-bell"></i> My Notifications</h2>
            <?php if ($unread_count > 0): ?>
                <a href="notifications.php?mark_read=all" class="btn btn-primary" style="margin-left: auto;">
                    <i class="fas fa-check-double"></i> Mark All as Read
                </a>
            <?php endif; ?>
        </div>

        <div class="table-container" style="margin-top: 1rem;">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <h3>No Notifications</h3>
                    <p>You don't have any notifications at the moment.</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-icon">
                                <i class="fas <?php
                                    echo match($notification['type']) {
                                        'enrollment_approved' => 'fa-check-circle',
                                        'enrollment_rejected' => 'fa-times-circle',
                                        'unenrollment_approved' => 'fa-check-circle',
                                        'unenrollment_rejected' => 'fa-times-circle',
                                        'success' => 'fa-check-circle',
                                        'warning' => 'fa-exclamation-triangle',
                                        'info' => 'fa-info-circle',
                                        default => 'fa-bell'
                                    };
                                ?>" style="color: <?php
                                    echo match($notification['type']) {
                                        'enrollment_approved', 'unenrollment_approved', 'success' => '#28a745',
                                        'enrollment_rejected', 'unenrollment_rejected' => '#dc3545',
                                        'warning' => '#ffc107',
                                        'info' => '#17a2b8',
                                        default => '#007bff'
                                    };
                                ?>"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-header">
                                    <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                    <span class="notification-time">
                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                    </span>
                                </div>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <?php if (!$notification['is_read']): ?>
                                    <a href="#" class="mark-read-link" onclick="markAsRead(<?php echo $notification['notification_id']; ?>)">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <style>
        .table-header-enhanced {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .table-title-enhanced {
            margin: 0;
            color: #007bff;
            font-weight: 600;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .no-notifications {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .no-notifications h3 {
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }

        .notification-item.unread {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-left: 4px solid #007bff;
        }

        .notification-item.read {
            background: #f8f9fa;
        }

        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .notification-icon {
            flex-shrink: 0;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(0, 123, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .notification-header h4 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #343a40;
        }

        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
            margin-left: 1rem;
        }

        .notification-content p {
            margin: 0 0 0.5rem 0;
            color: #495057;
            line-height: 1.5;
        }

        .mark-read-link {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            color: #007bff;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .mark-read-link:hover {
            color: #0056b3;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
    </style>

    <script>
        function markAsRead(notificationId) {
            fetch('../php/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove or update the notification item
                    const notificationItem = document.querySelector(`[onclick="markAsRead(${notificationId})"]`).closest('.notification-item');
                    notificationItem.classList.remove('unread');
                    notificationItem.classList.add('read');
                    const markReadLink = notificationItem.querySelector('.mark-read-link');
                    if (markReadLink) {
                        markReadLink.remove();
                    }
                } else {
                    alert('Error marking notification as read: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the notification as read.');
            });
        }
    </script>
</body>
</html>
