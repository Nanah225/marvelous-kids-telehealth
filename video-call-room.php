<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'doctor' && $_SESSION['user_role'] != 'parent')){
    header("Location: login.php");
    exit();
}

$room_id = isset($_GET['room']) ? $_GET['room'] : '';
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$doctor_name = isset($_GET['doctor']) ? $_GET['doctor'] : 'Doctor';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Video Call Room - Marvelous Kids</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#1a1a2e;font-family:'Segoe UI',sans-serif;}
        .container{max-width:1200px;margin:0 auto;padding:20px;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:20px;border-radius:15px;margin-bottom:20px;text-align:center;}
        .video-container{background:#16213e;border-radius:20px;padding:20px;min-height:500px;display:flex;align-items:center;justify-content:center;flex-direction:column;}
        .waiting-message{text-align:center;color:white;}
        .waiting-message i{font-size:80px;margin-bottom:20px;color:#2E8BFF;}
        .room-info{background:white;border-radius:15px;padding:15px;margin-top:20px;text-align:center;}
        .back-btn{background:#2E8BFF;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;text-decoration:none;display:inline-block;margin-top:10px;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-video"></i> Video Consultation Room</h1>
        <p>Connected with: <?php echo htmlspecialchars($doctor_name); ?></p>
    </div>
    
    <div class="video-container">
        <div class="waiting-message">
            <i class="fas fa-video-slash"></i>
            <h2>Waiting for connection...</h2>
            <p>Room ID: <?php echo htmlspecialchars($room_id); ?></p>
            <p style="margin-top:20px;">Share this room ID with the other participant to connect.</p>
        </div>
    </div>
    
    <div class="room-info">
        <p><strong>Room ID:</strong> <?php echo htmlspecialchars($room_id); ?></p>
        <p><strong>Appointment ID:</strong> <?php echo $appointment_id; ?></p>
        <a href="doctor-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Leave Call</a>
    </div>
</div>
</body>
</html>