<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Account Pending - Marvelous Kids</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;}
.pending-box{background:white;padding:40px;border-radius:20px;max-width:450px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
.pending-box i{font-size:80px;color:#ffc107;margin-bottom:20px;}
.pending-box h1{color:#333;margin-bottom:10px;}
.pending-box p{color:#666;margin-bottom:20px;line-height:1.6;}
.btn{display:inline-block;padding:12px 30px;background:#dc3545;color:white;text-decoration:none;border-radius:10px;margin-top:10px;}
.info-box{background:#e3f2fd;padding:15px;border-radius:12px;margin:20px 0;text-align:left;}
</style>
</head>
<body>
<div class="pending-box">
    <i class="fas fa-clock"></i>
    <h1>Account Pending Verification</h1>
    <p>Dear Dr. <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Doctor'); ?>,</p>
    <p>Thank you for registering with Marvelous Kids TeleHealth.</p>
    
    <div class="info-box">
        <i class="fas fa-info-circle"></i> 
        <strong>What happens next?</strong><br><br>
        1. Our admin team will review your credentials<br>
        2. You will receive an email notification once verified<br>
        3. After approval, you can start accepting consultations<br><br>
        This usually takes 24-48 hours.
    </div>
    
    <a href="logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
</body>
</html>