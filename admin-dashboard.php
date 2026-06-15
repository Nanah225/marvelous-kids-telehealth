<?php
session_start();
include 'db.php';

// Check if user is logged in as admin
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// Get statistics
$total_parents = 0;
$total_doctors = 0;
$pending_doctors = 0;
$total_users = 0;
$total_admins = 0;

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM parents");
if($result && $row = mysqli_fetch_assoc($result)){
    $total_parents = $row['count'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM doctors");
if($result && $row = mysqli_fetch_assoc($result)){
    $total_doctors = $row['count'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM doctors WHERE verification_status='pending'");
if($result && $row = mysqli_fetch_assoc($result)){
    $pending_doctors = $row['count'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
if($result && $row = mysqli_fetch_assoc($result)){
    $total_users = $row['count'];
}

$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM admins");
if($result && $row = mysqli_fetch_assoc($result)){
    $total_admins = $row['count'];
}

// Get pending doctors list
$pending_list = mysqli_query($conn, "SELECT * FROM doctors WHERE verification_status='pending' ORDER BY created_at DESC");

// Get all admins list
$admins_list = mysqli_query($conn, "SELECT a.*, u.full_name, u.email, u.phone_number 
                                    FROM admins a 
                                    JOIN users u ON a.user_id = u.id 
                                    ORDER BY a.created_at DESC");

// Handle doctor verification
$success_msg = "";
if(isset($_POST['verify_doctor'])){
    $doctor_id = intval($_POST['doctor_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    
    if($action == 'approve'){
        $update = mysqli_query($conn, "UPDATE doctors SET verification_status='approved', verified_at=NOW() WHERE id='$doctor_id'");
        if($update){
            $doc_result = mysqli_query($conn, "SELECT email FROM doctors WHERE id='$doctor_id'");
            if($doc_result && $doc = mysqli_fetch_assoc($doc_result)){
                mysqli_query($conn, "UPDATE users SET is_verified=1 WHERE email='{$doc['email']}'");
            }
            $success_msg = "Doctor approved successfully!";
        }
    } elseif($action == 'reject'){
        $update = mysqli_query($conn, "UPDATE doctors SET verification_status='rejected', rejected_at=NOW() WHERE id='$doctor_id'");
        $success_msg = "Doctor rejected.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Marvelous Kids</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .dashboard{max-width:1400px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:25px;border-radius:20px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
        .header h1{font-size:24px;}
        .logout-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:30px;}
        .stat-card{background:white;padding:20px;border-radius:15px;text-align:center;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .stat-card i{font-size:40px;color:#2E8BFF;margin-bottom:10px;}
        .stat-card h3{font-size:28px;color:#333;}
        .stat-card p{color:#666;font-size:14px;}
        .card{background:white;border-radius:15px;padding:20px;margin-bottom:20px;box-shadow:0 5px 15px rgba(0,0,0,0.1);}
        .card h2{color:#2E8BFF;margin-bottom:15px;font-size:20px;display:flex;align-items:center;gap:10px;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:12px;text-align:left;border-bottom:1px solid #eee;}
        th{background:#f8f9fa;color:#666;font-weight:600;}
        .status-pending{background:#fff3cd;color:#856404;padding:4px 12px;border-radius:20px;font-size:12px;display:inline-block;}
        .status-approved{background:#d4edda;color:#155724;padding:4px 12px;border-radius:20px;font-size:12px;display:inline-block;}
        .btn-approve{background:#28a745;color:white;padding:5px 15px;border:none;border-radius:5px;cursor:pointer;}
        .btn-reject{background:#dc3545;color:white;padding:5px 15px;border:none;border-radius:5px;cursor:pointer;}
        .action-buttons{display:flex;gap:8px;}
        .success-msg{background:#d4edda;color:#155724;padding:12px;border-radius:10px;margin-bottom:20px;}
        @media (max-width:768px){.header{flex-direction:column;text-align:center;gap:15px;}table{font-size:12px;}th,td{padding:8px;}}
    </style>
</head>
<body>
<div class="dashboard">
    <div class="header">
        <div>
            <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>!</p>
        </div>
        <a href="logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
    </div>
    
    <?php if(!empty($success_msg)): ?>
        <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    
    <div class="stats">
        <div class="stat-card"><i class="fas fa-users"></i><h3><?php echo $total_users; ?></h3><p>Total Users</p></div>
        <div class="stat-card"><i class="fas fa-baby"></i><h3><?php echo $total_parents; ?></h3><p>Parents</p></div>
        <div class="stat-card"><i class="fas fa-stethoscope"></i><h3><?php echo $total_doctors; ?></h3><p>Doctors</p></div>
        <div class="stat-card"><i class="fas fa-clock"></i><h3><?php echo $pending_doctors; ?></h3><p>Pending Verification</p></div>
        <div class="stat-card"><i class="fas fa-user-shield"></i><h3><?php echo $total_admins; ?></h3><p>Admins</p></div>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-stethoscope"></i> Pending Doctor Verifications</h2>
        <?php if($pending_list && mysqli_num_rows($pending_list) > 0): ?>
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Specialty</th><th>Experience</th><th>Registered</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while($doctor = mysqli_fetch_assoc($pending_list)): ?>
                <tr>
                    <td>Dr. <?php echo htmlspecialchars($doctor['name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($doctor['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($doctor['specialty'] ?? ''); ?></td>
                    <td><?php echo isset($doctor['experience_years']) ? $doctor['experience_years'] : '0'; ?> yrs</td>
                    <td><?php echo isset($doctor['created_at']) ? date('M d, Y', strtotime($doctor['created_at'])) : 'N/A'; ?></td>
                    <td>
                        <div class="action-buttons">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" name="verify_doctor" class="btn-approve"><i class="fas fa-check"></i> Approve</button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" name="verify_doctor" class="btn-reject"><i class="fas fa-times"></i> Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align:center;color:#999;padding:20px;">No pending doctor verifications.</p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-chart-line"></i> System Overview</h2>
        <p><strong>Total Revenue:</strong> SLL 0</p>
        <p><strong>Total Consultations:</strong> 0</p>
        <p><strong>Active Doctors:</strong> <?php echo max(0, $total_doctors - $pending_doctors); ?></p>
        <p><strong>System Status:</strong> <span style="color:green;">● Operational</span></p>
    </div>
    
    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Quick Links</h2>
        <p>
            <a href="register.php" style="color:#2E8BFF;">➕ Register New User</a> | 
            <a href="login.php" style="color:#2E8BFF;">🔐 Login Page</a>
        </p>
    </div>
</div>
</body>
</html>