<?php
session_start();
include 'db.php';

// Check if admin is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin'){
    header("Location: login.php");
    exit();
}

if(isset($_POST['doctor_id']) && isset($_POST['action'])){
    $doctor_id = intval($_POST['doctor_id']);
    $action = $_POST['action'];
    
    if($action == 'approve'){
        $status = 'approved';
        $message = "Doctor account approved successfully!";
    } else {
        $status = 'rejected';
        $message = "Doctor account rejected!";
    }
    
    $update = mysqli_query($conn, "UPDATE doctors SET verification_status='$status', verified_at=NOW() WHERE user_id='$doctor_id'");
    
    if($update){
        // Also update users table is_verified
        mysqli_query($conn, "UPDATE users SET is_verified=1 WHERE id='$doctor_id'");
        header("Location: admin-dashboard.php?msg=" . urlencode($message));
    } else {
        header("Location: admin-dashboard.php?msg=Error updating status");
    }
} else {
    header("Location: admin-dashboard.php");
}
exit();
?>