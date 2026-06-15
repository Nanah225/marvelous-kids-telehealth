<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login.php");
    exit();
}

$doctor_email = $_SESSION['user_email'];
$doctor_query = mysqli_query($conn, "SELECT id, name FROM doctors WHERE email='$doctor_email'");
$doctor = mysqli_fetch_assoc($doctor_query);
$doctor_id = $doctor['id'];

// Get all parents who have messaged
$parents_query = mysqli_query($conn, "SELECT DISTINCT p.id, p.fullname, p.child_name, p.profile_pic 
                                      FROM parents p 
                                      JOIN messages m ON m.parent_id = p.id 
                                      WHERE m.doctor_id='$doctor_id' 
                                      ORDER BY m.created_at DESC");

// Handle message sending
$selected_parent = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
$messages = [];
if($selected_parent){
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
    mysqli_query($conn, "INSERT INTO messages (parent_id, doctor_id, message, sender, created_at) 
                         VALUES ('$parent_id', '$doctor_id', '$message', 'doctor', NOW())");
    header("Location: doctor-messages.php?parent_id=$parent_id");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Messages - Doctor</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:1200px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:20px;border-radius:15px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
        .back-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;text-decoration:none;}
        .chat-container{display:flex;gap:20px;flex-wrap:wrap;}
        .parents-list{flex:1;background:white;border-radius:15px;overflow:hidden;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .parent-item{padding:15px;border-bottom:1px solid #eee;cursor:pointer;transition:background 0.2s;}
        .parent-item:hover{background:#f5f5f5;}
        .parent-item.active{background:#e3f2fd;border-left:3px solid #2E8BFF;}
        .chat-area{flex:2;background:white;border-radius:15px;overflow:hidden;display:flex;flex-direction:column;height:500px;}
        .chat-header{background:#f8f9fa;padding:15px;border-bottom:1px solid #eee;}
        .messages{flex:1;overflow-y:auto;padding:20px;}
        .message{margin-bottom:15px;display:flex;}
        .message.doctor{justify-content:flex-end;}
        .message.parent{justify-content:flex-start;}
        .message-bubble{max-width:70%;padding:10px 15px;border-radius:20px;}
        .message.doctor .message-bubble{background:#2E8BFF;color:white;}
        .message.parent .message-bubble{background:#f0f0f0;color:#333;}
        .message-time{font-size:11px;margin-top:5px;color:#999;}
        .message-input{display:flex;padding:15px;border-top:1px solid #eee;}
        .message-input input{flex:1;padding:12px;border:1px solid #ddd;border-radius:25px;outline:none;}
        .message-input button{background:#2E8BFF;color:white;border:none;padding:12px 25px;border-radius:25px;margin-left:10px;cursor:pointer;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-comments"></i> Patient Messages</h1>
        <a href="doctor-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <div class="chat-container">
        <div class="parents-list">
            <div style="padding:15px;background:#f8f9fa;font-weight:bold;border-bottom:1px solid #eee;">
                <i class="fas fa-users"></i> Your Patients
            </div>
            <?php while($parent = mysqli_fetch_assoc($parents_query)): ?>
            <a href="doctor-messages.php?parent_id=<?php echo $parent['id']; ?>" style="text-decoration:none;">
                <div class="parent-item <?php echo $selected_parent == $parent['id'] ? 'active' : ''; ?>">
                    <strong><?php echo htmlspecialchars($parent['fullname']); ?></strong><br>
                    <small style="color:#666;">Child: <?php echo htmlspecialchars($parent['child_name']); ?></small>
                </div>
            </a>
            <?php endwhile; ?>
            <?php if(mysqli_num_rows($parents_query) == 0): ?>
            <div style="padding:40px;text-align:center;color:#999;">No messages yet.</div>
            <?php endif; ?>
        </div>
        
        <div class="chat-area">
            <?php if($selected_parent): 
                $parent_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT fullname FROM parents WHERE id='$selected_parent'"));
            ?>
            <div class="chat-header">
                <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($parent_info['fullname']); ?></strong>
            </div>
            <div class="messages">
                <?php foreach($messages as $msg): ?>
                <div class="message <?php echo $msg['sender']; ?>">
                    <div class="message-bubble">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        <div class="message-time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <form method="POST" class="message-input">
                <input type="hidden" name="parent_id" value="<?php echo $selected_parent; ?>">
                <input type="text" name="message" placeholder="Type your message..." required>
                <button type="submit" name="send_message"><i class="fas fa-paper-plane"></i> Send</button>
            </form>
            <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;">
                <i class="fas fa-comment-dots" style="font-size:50px;margin-bottom:10px;"></i>
                <p>Select a patient to start messaging</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto scroll to bottom of messages
const messagesDiv = document.querySelector('.messages');
if(messagesDiv){
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
</script>
</body>
</html>