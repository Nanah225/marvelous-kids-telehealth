<?php
session_start();
include 'db.php';

// Check if user is logged in as doctor
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login.php");
    exit();
}

$doctor_email = $_SESSION['user_email'];
$doctor_query = mysqli_query($conn, "SELECT id, name FROM doctors WHERE email='$doctor_email'");
$doctor = mysqli_fetch_assoc($doctor_query);

if(!$doctor){
    header("Location: login.php");
    exit();
}

$doctor_id = $doctor['id'];

// Check if is_read column exists
$columns_check = mysqli_query($conn, "SHOW COLUMNS FROM messages");
$has_is_read = false;
while($col = mysqli_fetch_assoc($columns_check)){
    if($col['Field'] == 'is_read'){
        $has_is_read = true;
        break;
    }
}

// Get all parents who have messaged this doctor
if($has_is_read){
    $parents_query = mysqli_query($conn, "SELECT DISTINCT p.id, p.fullname, p.child_name, p.profile_pic,
                                          (SELECT COUNT(*) FROM messages WHERE doctor_id='$doctor_id' AND parent_id=p.id AND is_read=0 AND sender='parent') as unread_count
                                          FROM parents p 
                                          JOIN messages m ON m.parent_id = p.id 
                                          WHERE m.doctor_id='$doctor_id' 
                                          GROUP BY p.id
                                          ORDER BY MAX(m.created_at) DESC");
} else {
    $parents_query = mysqli_query($conn, "SELECT DISTINCT p.id, p.fullname, p.child_name, p.profile_pic,
                                          0 as unread_count
                                          FROM parents p 
                                          JOIN messages m ON m.parent_id = p.id 
                                          WHERE m.doctor_id='$doctor_id' 
                                          GROUP BY p.id
                                          ORDER BY MAX(m.created_at) DESC");
}

// Handle message sending
$selected_parent = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
$messages = [];
if($selected_parent){
    // Mark messages as read if column exists
    if($has_is_read){
        mysqli_query($conn, "UPDATE messages SET is_read=1 WHERE doctor_id='$doctor_id' AND parent_id='$selected_parent' AND sender='parent'");
    }
    
    $messages_query = mysqli_query($conn, "SELECT * FROM messages 
                                           WHERE (parent_id='$selected_parent' AND doctor_id='$doctor_id')
                                           ORDER BY created_at ASC");
    while($msg = mysqli_fetch_assoc($messages_query)){
        $messages[] = $msg;
    }
}

if(isset($_POST['send_message'])){
    $parent_id = intval($_POST['parent_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    mysqli_query($conn, "INSERT INTO messages (parent_id, doctor_id, message, sender) 
                         VALUES ('$parent_id', '$doctor_id', '$message', 'doctor')");
    header("Location: doctor-chat.php?parent_id=$parent_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Messages - Marvelous Kids</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);height:100vh;display:flex;flex-direction:column;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;}
        .back-btn{background:rgba(255,255,255,0.2);padding:8px 15px;border:none;border-radius:10px;color:white;cursor:pointer;text-decoration:none;}
        .chat-container{display:flex;flex:1;overflow:hidden;}
        .parents-list{width:300px;background:white;border-right:1px solid #eee;display:flex;flex-direction:column;}
        .parents-list h3{padding:15px;background:#f8f9fa;border-bottom:1px solid #eee;font-size:16px;}
        .parent-item{padding:15px;border-bottom:1px solid #eee;cursor:pointer;transition:background 0.2s;text-decoration:none;display:block;}
        .parent-item:hover{background:#f5f5f5;}
        .parent-item.active{background:#e3f2fd;}
        .parent-name{font-weight:bold;color:#333;}
        .parent-child{font-size:11px;color:#666;margin-top:3px;}
        .unread-badge{background:#e74c3c;color:white;border-radius:50%;padding:2px 8px;font-size:10px;margin-left:10px;display:inline-block;}
        .chat-area{flex:1;display:flex;flex-direction:column;background:#f9f9f9;}
        .chat-header-custom{padding:15px;background:white;border-bottom:1px solid #eee;}
        .messages{flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:15px;}
        .message{display:flex;margin-bottom:10px;}
        .message.sent{justify-content:flex-end;}
        .message.received{justify-content:flex-start;}
        .message-bubble{max-width:70%;padding:10px 15px;border-radius:20px;}
        .message.sent .message-bubble{background:#2E8BFF;color:white;border-bottom-right-radius:5px;}
        .message.received .message-bubble{background:white;color:#333;border-bottom-left-radius:5px;box-shadow:0 1px 2px rgba(0,0,0,0.1);}
        .message-time{font-size:10px;margin-top:5px;opacity:0.7;}
        .message-input{background:white;padding:15px;display:flex;gap:10px;border-top:1px solid #eee;}
        .message-input input{flex:1;padding:12px;border:1px solid #ddd;border-radius:25px;outline:none;font-size:14px;}
        .message-input button{background:#2E8BFF;color:white;border:none;padding:12px 25px;border-radius:25px;cursor:pointer;}
        .no-selection{display:flex;align-items:center;justify-content:center;height:100%;color:#999;flex-direction:column;}
        @media (max-width:768px){.parents-list{width:250px;}}
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-comments"></i> Patient Messages</h1>
        <a href="doctor-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    
    <div class="chat-container">
        <div class="parents-list">
            <h3><i class="fas fa-users"></i> Your Conversations</h3>
            <div style="overflow-y:auto;">
                <?php if($parents_query && mysqli_num_rows($parents_query) > 0): ?>
                    <?php while($parent = mysqli_fetch_assoc($parents_query)): ?>
                    <a href="doctor-chat.php?parent_id=<?php echo $parent['id']; ?>" class="parent-item <?php echo $selected_parent == $parent['id'] ? 'active' : ''; ?>">
                        <div class="parent-name">
                            <?php echo htmlspecialchars($parent['fullname']); ?>
                            <?php if($parent['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $parent['unread_count']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="parent-child">Child: <?php echo htmlspecialchars($parent['child_name']); ?></div>
                    </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding:40px;text-align:center;color:#999;">No conversations yet.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="chat-area">
            <?php if($selected_parent): 
                $parent_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname, child_name FROM parents WHERE id='$selected_parent'"));
            ?>
                <div class="chat-header-custom">
                    <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($parent_info['fullname']); ?></strong>
                    <small style="margin-left:10px;">Child: <?php echo htmlspecialchars($parent_info['child_name']); ?></small>
                </div>
                <div class="messages" id="messagesContainer">
                    <?php foreach($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender']; ?>">
                        <div class="message-bubble">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            <div class="message-time">
                                <?php 
                                if(isset($msg['created_at'])){
                                    echo date('h:i A', strtotime($msg['created_at']));
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($messages)): ?>
                        <div style="text-align:center;padding:40px;color:#999;">No messages yet. Start the conversation!</div>
                    <?php endif; ?>
                </div>
                <form method="POST" class="message-input">
                    <input type="hidden" name="parent_id" value="<?php echo $selected_parent; ?>">
                    <input type="text" name="message" placeholder="Type your reply..." required autocomplete="off">
                    <button type="submit" name="send_message"><i class="fas fa-paper-plane"></i> Send</button>
                </form>
            <?php else: ?>
                <div class="no-selection">
                    <i class="fas fa-comment-dots" style="font-size:60px;margin-bottom:15px;color:#ccc;"></i>
                    <p>Select a conversation from the left to start messaging</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        const container = document.getElementById('messagesContainer');
        if(container) container.scrollTop = container.scrollHeight;
        
        // Auto-refresh every 5 seconds
        let currentParent = <?php echo $selected_parent; ?>;
        if(currentParent > 0){
            setInterval(function() {
                fetch(window.location.href + '&ajax=1')
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newMessages = doc.querySelector('.messages');
                        if(newMessages && container.innerHTML !== newMessages.innerHTML) {
                            container.innerHTML = newMessages.innerHTML;
                            container.scrollTop = container.scrollHeight;
                        }
                    })
                    .catch(err => console.log('Error refreshing:', err));
            }, 5000);
        }
    </script>
</body>
</html>