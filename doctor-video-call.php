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

// Get today's confirmed appointments for video calls
$appointments_query = mysqli_query($conn, "SELECT a.*, p.fullname as parent_name, p.child_name 
                                           FROM appointments a 
                                           JOIN parents p ON a.parent_id = p.id 
                                           WHERE a.doctor_id='{$doctor['id']}' 
                                           AND a.status='confirmed' 
                                           AND a.type='video'
                                           AND a.appointment_date = CURDATE()
                                           ORDER BY a.appointment_time");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Video Consultation - Doctor</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:1200px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:20px;border-radius:15px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
        .back-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;text-decoration:none;}
        .video-room{background:#1a1a2e;border-radius:20px;padding:20px;margin-bottom:20px;}
        .video-container{display:flex;gap:20px;flex-wrap:wrap;}
        .video-box{flex:1;background:#16213e;border-radius:15px;padding:20px;text-align:center;color:white;}
        .video-box video{width:100%;max-height:400px;background:#0f3460;border-radius:10px;}
        .controls{display:flex;justify-content:center;gap:15px;margin-top:20px;}
        .control-btn{background:#e94560;color:white;padding:12px 25px;border:none;border-radius:50px;cursor:pointer;}
        .appointment-list{background:white;border-radius:15px;padding:20px;}
        .appointment-item{display:flex;justify-content:space-between;align-items:center;padding:15px;border-bottom:1px solid #eee;}
        .join-btn{background:#2E8BFF;color:white;padding:8px 20px;border:none;border-radius:5px;cursor:pointer;text-decoration:none;}
        .room-id{font-size:12px;color:#666;margin-top:5px;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-video"></i> Video Consultation</h1>
        <a href="doctor-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <div class="appointment-list">
        <h3 style="margin-bottom:15px;">📅 Today's Video Appointments</h3>
        <?php while($apt = mysqli_fetch_assoc($appointments_query)): ?>
        <div class="appointment-item">
            <div>
                <strong><?php echo htmlspecialchars($apt['parent_name']); ?></strong><br>
                <small>Child: <?php echo htmlspecialchars($apt['child_name']); ?> | Time: <?php echo date('h:i A', strtotime($apt['appointment_time'])); ?></small>
                <div class="room-id">Room ID: call_<?php echo $apt['id']; ?>_<?php echo $doctor['id']; ?></div>
            </div>
            <a href="video-call-room.php?appointment_id=<?php echo $apt['id']; ?>&room=call_<?php echo $apt['id']; ?>_<?php echo $doctor['id']; ?>&doctor=<?php echo urlencode($doctor['name']); ?>" class="join-btn">
                <i class="fas fa-video"></i> Join Call
            </a>
        </div>
        <?php endwhile; ?>
        <?php if(mysqli_num_rows($appointments_query) == 0): ?>
        <p style="text-align:center;padding:40px;color:#999;">No video appointments scheduled for today.</p>
        <?php endif; ?>
    </div>
    
    <div class="info-box" style="background:white;border-radius:15px;padding:20px;margin-top:20px;">
        <h3><i class="fas fa-info-circle"></i> Instructions</h3>
        <ul style="margin-left:20px;color:#666;">
            <li>Click "Join Call" to start a video consultation with the patient</li>
            <li>Ensure your camera and microphone are working</li>
            <li>Use a quiet, well-lit room for the consultation</li>
            <li>After the call, you can add consultation notes in patient records</li>
        </ul>
    </div>
</div>
</body>
</html>