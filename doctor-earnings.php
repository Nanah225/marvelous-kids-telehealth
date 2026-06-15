<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$doctor_query = mysqli_query($conn, "SELECT id, name, consultation_fee FROM doctors WHERE email='$email'");
$doctor = mysqli_fetch_assoc($doctor_query);

if(!$doctor){
    header("Location: login.php");
    exit();
}

$doctor_id = $doctor['id'];
$consultation_fee = $doctor['consultation_fee'] ?? 150000;

// Use appointments table instead of consultations
$completed_count = 0;
$total_earnings = 0;

// Get completed appointments
$completed = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='$doctor_id' AND status='completed'");
if($completed && $row = mysqli_fetch_assoc($completed)){
    $completed_count = $row['count'];
}

$total_earnings = $completed_count * $consultation_fee;

// Get monthly breakdown from appointments
$monthly_query = mysqli_query($conn, "SELECT 
    DATE_FORMAT(appointment_date, '%Y-%m') as month,
    COUNT(*) as count
    FROM appointments 
    WHERE doctor_id='$doctor_id' AND status='completed'
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month DESC");

// Get all appointments count
$appointments_count = 0;
$appointments_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='$doctor_id'");
if($appointments_result && $row = mysqli_fetch_assoc($appointments_result)){
    $appointments_count = $row['count'];
}

// Get pending appointments
$pending_count = 0;
$pending_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE doctor_id='$doctor_id' AND status='pending'");
if($pending_result && $row = mysqli_fetch_assoc($pending_result)){
    $pending_count = $row['count'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Earnings Report - Doctor</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:1000px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:20px;border-radius:15px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
        .header h1{font-size:24px;}
        .back-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;text-decoration:none;}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px;}
        .stat-card{background:white;padding:25px;border-radius:15px;text-align:center;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .stat-card i{font-size:50px;color:#2E8BFF;margin-bottom:10px;}
        .stat-card h3{font-size:32px;color:#333;margin-bottom:5px;}
        .stat-card p{color:#666;font-size:14px;}
        .card{background:white;border-radius:15px;padding:20px;margin-bottom:20px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .card h3{color:#2E8BFF;margin-bottom:15px;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:15px;text-align:left;border-bottom:1px solid #eee;}
        th{background:#f8f9fa;color:#2E8BFF;font-weight:600;}
        .total{font-weight:bold;color:#28a745;font-size:18px;}
        .no-data{text-align:center;padding:40px;color:#999;}
        @media (max-width:768px){.header{flex-direction:column;text-align:center;gap:15px;}}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fas fa-chart-bar"></i> Earnings Report</h1>
            <p>Dr. <?php echo htmlspecialchars($doctor['name'] ?? ''); ?></p>
        </div>
        <a href="doctor-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <i class="fas fa-calendar-check"></i>
            <h3><?php echo $appointments_count; ?></h3>
            <p>Total Appointments</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <h3><?php echo $pending_count; ?></h3>
            <p>Pending Appointments</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-check-circle"></i>
            <h3><?php echo $completed_count; ?></h3>
            <p>Completed</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-money-bill-wave"></i>
            <h3>SLL <?php echo number_format($consultation_fee); ?></h3>
            <p>Fee per Session</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-chart-line"></i>
            <h3>SLL <?php echo number_format($total_earnings); ?></h3>
            <p>Total Earnings</p>
        </div>
    </div>
    
    <div class="card">
        <h3><i class="fas fa-calendar-alt"></i> Monthly Breakdown</h3>
        <?php if($monthly_query && mysqli_num_rows($monthly_query) > 0): ?>
        <table>
            <thead>
                <tr><th>Month</th><th>Completed</th><th>Earnings</th></tr>
            </thead>
            <tbody>
                <?php while($month = mysqli_fetch_assoc($monthly_query)): 
                    $month_earnings = $month['count'] * $consultation_fee;
                ?>
                <tr>
                    <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                    <td><?php echo $month['count']; ?></td>
                    <td>SLL <?php echo number_format($month_earnings); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="background:#f0f7ff;">
                    <td><strong>Total</strong></td>
                    <td><strong><?php echo $completed_count; ?></strong></td>
                    <td class="total"><strong>SLL <?php echo number_format($total_earnings); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-chart-line" style="font-size:50px;margin-bottom:15px;color:#ccc;"></i>
                <p>No earnings data available yet.</p>
                <p style="font-size:12px;margin-top:10px;">Complete appointments to see your earnings.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>