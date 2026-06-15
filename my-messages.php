<?php
session_start();
include 'db.php';

if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];

// Get all doctors this parent has chatted with
$doctors_query = mysqli_query($conn, "SELECT DISTINCT d.id, d.name, d.specialty,
                                      (SELECT COUNT(*) FROM messages WHERE doctor_id=d.id AND parent_id='$parent_id' AND is_read=0 AND sender='doctor') as unread_count,
                                      (SELECT message FROM messages WHERE doctor_id=d.id AND parent_id='$parent_id' ORDER BY created_at DESC LIMIT 1) as last_message,
                                      (SELECT created_at FROM messages WHERE doctor_id=d.id AND parent_id='$parent_id' ORDER BY created_at DESC LIMIT 1) as last_time
                                      FROM doctors d 
                                      JOIN messages m ON m.doctor_id = d.id 
                                      WHERE m.parent_id='$parent_id'
                                      GROUP BY d.id
                                      ORDER BY last_time DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - Marvelous Kids</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:800px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:20px;border-radius:15px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
        .back-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;text-decoration:none;}
        .messages-list{background:white;border-radius:15px;overflow:hidden;}
        .message-item{display:flex;align-items:center;padding:15px;border-bottom:1px solid #eee;text-decoration:none;color:inherit;transition:background 0.2s;}
        .message-item:hover{background:#f5f5f5;}
        .doctor-avatar{width:50px;height:50px;background:linear-gradient(135deg,#2E8BFF,#00C896);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;margin-right:15px;}
        .message-info{flex:1;}
        .doctor-name{font-weight:bold;color:#333;}
        .last-message{font-size:12px;color:#666;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;}
        .message-time{font-size:10px;color:#999;}
        .unread-badge{background:#e74c3c;color:white;border-radius:50%;padding:2px 8px;font-size:10px;margin-left:10px;}
        .no-messages{text-align:center;padding:60px;color:#999;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-comments"></i> My Messages</h1>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <div class="messages-list">
        <?php if(mysqli_num_rows($doctors_query) > 0): ?>
            <?php while($doctor = mysqli_fetch_assoc($doctors_query)): ?>
            <a href="parent-chat.php?doctor_id=<?php echo $doctor['id']; ?>&doctor_name=<?php echo urlencode($doctor['name']); ?>" class="message-item">
                <div class="doctor-avatar">👨‍⚕️</div>
                <div class="message-info">
                    <div class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></div>
                    <div class="last-message"><?php echo htmlspecialchars(substr($doctor['last_message'] ?? 'No messages yet', 0, 50)); ?></div>
                </div>
                <div>
                    <div class="message-time"><?php echo $doctor['last_time'] ? date('M d, h:i A', strtotime($doctor['last_time'])) : ''; ?></div>
                    <?php if($doctor['unread_count'] > 0): ?>
                        <div class="unread-badge"><?php echo $doctor['unread_count']; ?></div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-messages">
                <i class="fas fa-inbox" style="font-size:60px;margin-bottom:15px;color:#ccc;"></i>
                <p>No messages yet.</p>
                <p style="font-size:12px;margin-top:10px;">Start a conversation with a doctor from the dashboard!</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>