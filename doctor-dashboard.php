<?php
session_start();
include 'db.php';

// Check if user is logged in as doctor
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];

// Get doctor information
$query = mysqli_query($conn, "SELECT * FROM doctors WHERE email='$email'");
$doctor = mysqli_fetch_assoc($query);

if(!$doctor){
    header("Location: login.php");
    exit();
}

// Get statistics
$total_appointments = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='{$doctor['id']}'");
if($result && $row = mysqli_fetch_assoc($result)){
    $total_appointments = $row['count'];
}

$pending_appointments = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='{$doctor['id']}' AND status='pending'");
if($result && $row = mysqli_fetch_assoc($result)){
    $pending_appointments = $row['count'];
}

$today_appointments = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='{$doctor['id']}' AND appointment_date = CURDATE()");
if($result && $row = mysqli_fetch_assoc($result)){
    $today_appointments = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Doctor Dashboard - Marvelous Kids</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .dashboard{max-width:1200px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:25px;border-radius:20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
        .header h1{font-size:24px;}
        .logout-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;}
        .stat-card{background:white;padding:20px;border-radius:15px;text-align:center;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .stat-card i{font-size:40px;color:#2E8BFF;margin-bottom:10px;}
        .stat-card h3{font-size:28px;color:#333;}
        .card{background:white;border-radius:15px;padding:20px;margin-bottom:20px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .card h2{color:#2E8BFF;margin-bottom:15px;font-size:20px;}
        .info-row{display:flex;padding:10px 0;border-bottom:1px solid #eee;}
        .info-label{width:150px;font-weight:bold;color:#666;}
        .info-value{flex:1;color:#333;}
        .quick-actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;}
        .action-btn{background:#f0f7ff;padding:15px;border-radius:12px;text-align:center;text-decoration:none;color:#2E8BFF;transition:transform 0.2s;display:block;cursor:pointer;}
        .action-btn:hover{transform:translateY(-3px);background:#e0efff;}
        .action-btn i{font-size:30px;display:block;margin-bottom:10px;}
        .pending-badge{background:#fff3cd;color:#856404;padding:15px;border-radius:12px;margin-bottom:20px;}
        @media (max-width:768px){.header{flex-direction:column;text-align:center;gap:15px;}}
    </style>
</head>
<body>
<div class="dashboard">
    <div class="header">
        <div>
            <h1><i class="fas fa-stethoscope"></i> Doctor Dashboard</h1>
            <p>Welcome back, Dr. <?php echo htmlspecialchars($doctor['name'] ?? $_SESSION['user_name']); ?>!</p>
        </div>
        <a href="logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
    </div>
    
    <?php if($doctor['verification_status'] == 'pending'): ?>
    <div class="pending-badge">
        <i class="fas fa-clock"></i> <strong>Account Pending Verification</strong><br>
        Your account is waiting for admin approval. You'll be notified once verified.
    </div>
    <?php endif; ?>
    
    <div class="stats">
        <div class="stat-card">
            <i class="fas fa-calendar-check"></i>
            <h3><?php echo $total_appointments; ?></h3>
            <p>Total Appointments</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <h3><?php echo $pending_appointments; ?></h3>
            <p>Pending</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-day"></i>
            <h3><?php echo $today_appointments; ?></h3>
            <p>Today's Appointments</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-star"></i>
            <h3><?php echo number_format($doctor['rating'] ?? 4.5, 1); ?></h3>
            <p>Rating</p>
        </div>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="quick-actions">
            <a href="doctor-appointments.php" class="action-btn">
                <i class="fas fa-calendar-plus"></i> 
                Manage Appointments
            </a>
            <a href="doctor-video-call.php" class="action-btn">
                <i class="fas fa-video"></i> 
                Start Video Call
            </a>
            <a href="doctor-messages.php" class="action-btn">
                <i class="fas fa-comments"></i> 
                Patient Messages
            </a>
            <a href="doctor-patient-records.php" class="action-btn">
                <i class="fas fa-chart-line"></i> 
                Patient Records
            </a>
            <a href="doctor-profile-settings.php" class="action-btn">
                <i class="fas fa-cog"></i> 
                Profile Settings
            </a>
            <a href="doctor-earnings.php" class="action-btn">
                <i class="fas fa-chart-bar"></i> 
                Earnings Report
            </a>
        </div>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-user-md"></i> Professional Information</h2>
        <div class="info-row"><div class="info-label">Full Name:</div><div class="info-value">Dr. <?php echo htmlspecialchars($doctor['name'] ?? ''); ?></div></div>
        <div class="info-row"><div class="info-label">Email:</div><div class="info-value"><?php echo htmlspecialchars($doctor['email'] ?? ''); ?></div></div>
        <div class="info-row"><div class="info-label">Phone:</div><div class="info-value"><?php echo htmlspecialchars($doctor['phone'] ?? ''); ?></div></div>
        <div class="info-row"><div class="info-label">Specialty:</div><div class="info-value"><?php echo htmlspecialchars($doctor['specialty'] ?? ''); ?></div></div>
        <div class="info-row"><div class="info-label">Experience:</div><div class="info-value"><?php echo $doctor['experience_years'] ?? '0'; ?> years</div></div>
        <div class="info-row"><div class="info-label">Consultation Fee:</div><div class="info-value">SLL <?php echo number_format($doctor['consultation_fee'] ?? 0); ?></div></div>
        <div class="info-row"><div class="info-label">Status:</div><div class="info-value">
            <?php if(($doctor['verification_status'] ?? 'pending') == 'approved'): ?>
                <span style="color:green;"><i class="fas fa-check-circle"></i> Verified</span>
            <?php elseif(($doctor['verification_status'] ?? 'pending') == 'pending'): ?>
                <span style="color:orange;"><i class="fas fa-clock"></i> Pending</span>
            <?php else: ?>
                <span style="color:red;"><i class="fas fa-times-circle"></i> Rejected</span>
            <?php endif; ?>
        </div></div>
    </div>
</div>
</body>
</html>