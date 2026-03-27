<?php
require __DIR__ . '/../../connections/db.php';
session_start();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #3b82f6;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .read {
            background: #d1fae5;
            color: #065f46;
        }
        .unread {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <h1>📬 Notifications Database Check</h1>
    
    <?php
    try {
        // Get all notifications
        $stmt = $conn->prepare("SELECT * FROM broadcast ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        if (empty($notifications)) {
            echo '<p style="color: orange;">⚠️ No notifications found in the database.</p>';
            echo '<p>You need to create some notifications first from the admin broadcast page.</p>';
        } else {
            echo '<p>✅ Found ' . count($notifications) . ' notification(s)</p>';
            echo '<table>';
            echo '<thead><tr>';
            echo '<th>ID</th>';
            echo '<th>Subject</th>';
            echo '<th>Recipient</th>';
            echo '<th>From</th>';
            echo '<th>Status</th>';
            echo '<th>Created</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($notifications as $notif) {
                $status = $notif->is_read ? 'Read' : 'Unread';
                $badgeClass = $notif->is_read ? 'read' : 'unread';
                
                echo '<tr>';
                echo '<td>' . htmlspecialchars($notif->id) . '</td>';
                echo '<td>' . htmlspecialchars($notif->subject) . '</td>';
                echo '<td>' . htmlspecialchars($notif->recipient) . '</td>';
                echo '<td>' . htmlspecialchars($notif->username) . '</td>';
                echo '<td><span class="badge ' . $badgeClass . '">' . $status . '</span></td>';
                echo '<td>' . htmlspecialchars($notif->created_at) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        }
        
        // Show current session info
        echo '<div style="margin-top: 30px; padding: 15px; background: #f3f4f6; border-radius: 8px;">';
        echo '<h3>🔐 Current Session Info</h3>';
        if (isset($_SESSION['username'])) {
            echo '<p><strong>Logged in as:</strong> ' . htmlspecialchars($_SESSION['username']) . '</p>';
            echo '<p><strong>Role:</strong> ' . htmlspecialchars($_SESSION['role'] ?? 'N/A') . '</p>';
        } else {
            echo '<p style="color: red;">❌ Not logged in! You need to login first.</p>';
        }
        echo '</div>';
        
    } catch (PDOException $e) {
        echo '<p style="color: red;">❌ Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    ?>
    
    <div style="margin-top: 30px;">
        <a href="<?= $base ?>admin/" style="padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;">← Back to Admin</a>
    </div>
</body>
</html>
