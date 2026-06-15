<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'doctor'){
    header("Location: login.php");
    exit();
}

$doctor_email = $_SESSION['user_email'];
$doctor_query = mysqli_query($conn, "SELECT id FROM doctors WHERE email='$doctor_email'");
$doctor = mysqli_fetch_assoc($doctor_query);
$doctor_id = $doctor['id'];

// Get all parents who have consulted with this doctor
$patients_query = mysqli_query($conn, "SELECT DISTINCT p.id, p.fullname, p.child_name, p.child_age, p.phone, p.address 
                                       FROM parents p 
                                       JOIN appointments a ON a.parent_id = p.id 
                                       WHERE a.doctor_id='$doctor_id' 
                                       ORDER BY a.created_at DESC");

$selected_patient = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$consultations = [];
if($selected_patient){
    $consultations_query = mysqli_query($conn, "SELECT * FROM consultations 
                                                WHERE parent_id='$selected_patient' AND doctor_id='$doctor_id' 
                                                ORDER BY consultation_date DESC");
    while($cons = mysqli_fetch_assoc($consultations_query)){
        $consultations[] = $cons;
    }
}

// Add consultation notes
if(isset($_POST['add_note'])){
    $parent_id = intval($_POST['parent_id']);
    $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
    $prescription = mysqli_real_escape_string($conn, $_POST['prescription']);
    $doctor_notes = mysqli_real_escape_string($conn, $_POST['doctor_notes']);
    
    mysqli_query($conn, "INSERT INTO consultations (parent_id, doctor_id, consultation_date, diagnosis, prescription, doctor_notes, status) 
                         VALUES ('$parent_id', '$doctor_id', NOW(), '$diagnosis', '$prescription', '$doctor_notes', 'completed')");
    header("Location: doctor-patient-records.php?patient_id=$parent_id");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Records - Doctor</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;padding:20px;}
        .container{max-width:1200px;margin:0 auto;}
        .header{background:linear-gradient(135deg,#2E8BFF,#00C896);color:white;padding:20px;border-radius:15px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
        .back-btn{background:rgba(255,255,255,0.2);padding:10px 20px;border:none;border-radius:10px;color:white;cursor:pointer;text-decoration:none;}
        .records-container{display:flex;gap:20px;flex-wrap:wrap;}
        .patients-list{flex:1;background:white;border-radius:15px;overflow:hidden;}
        .patient-item{padding:15px;border-bottom:1px solid #eee;cursor:pointer;transition:background 0.2s;}
        .patient-item:hover{background:#f5f5f5;}
        .patient-item.active{background:#e3f2fd;}
        .records-area{flex:2;background:white;border-radius:15px;padding:20px;}
        .consultation-card{background:#f8f9fa;padding:15px;border-radius:10px;margin-bottom:15px;}
        .form-group{margin-bottom:15px;}
        .form-group label{display:block;margin-bottom:5px;font-weight:bold;}
        .form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;}
        .btn{background:#2E8BFF;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;}
        .btn-green{background:#28a745;}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-chart-line"></i> Patient Records</h1>
        <a href="doctor-dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    
    <div class="records-container">
        <div class="patients-list">
            <div style="padding:15px;background:#f8f9fa;font-weight:bold;border-bottom:1px solid #eee;">
                <i class="fas fa-users"></i> Your Patients
            </div>
            <?php while($patient = mysqli_fetch_assoc($patients_query)): ?>
            <a href="doctor-patient-records.php?patient_id=<?php echo $patient['id']; ?>" style="text-decoration:none;color:inherit;">
                <div class="patient-item <?php echo $selected_patient == $patient['id'] ? 'active' : ''; ?>">
                    <strong><?php echo htmlspecialchars($patient['fullname']); ?></strong><br>
                    <small>Child: <?php echo htmlspecialchars($patient['child_name']); ?> (<?php echo $patient['child_age']; ?> yrs)</small>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        
        <div class="records-area">
            <?php if($selected_patient): 
                $patient_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM parents WHERE id='$selected_patient'"));
            ?>
            <h3><?php echo htmlspecialchars($patient_info['fullname']); ?>'s Medical Records</h3>
            <p><strong>Child:</strong> <?php echo htmlspecialchars($patient_info['child_name']); ?> | <strong>Age:</strong> <?php echo $patient_info['child_age']; ?> years</p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($patient_info['phone']); ?> | <strong>Address:</strong> <?php echo htmlspecialchars($patient_info['address']); ?></p>
            
            <hr style="margin:15px 0;">
            
            <h4>Previous Consultations</h4>
            <?php foreach($consultations as $cons): ?>
            <div class="consultation-card">
                <strong><?php echo date('F j, Y g:i A', strtotime($cons['consultation_date'])); ?></strong>
                <p><strong>Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($cons['diagnosis'])); ?></p>
                <p><strong>Prescription:</strong> <?php echo nl2br(htmlspecialchars($cons['prescription'])); ?></p>
                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($cons['doctor_notes'])); ?></p>
            </div>
            <?php endforeach; ?>
            <?php if(empty($consultations)): ?>
            <p style="color:#999;">No previous consultations.</p>
            <?php endif; ?>
            
            <hr style="margin:15px 0;">
            
            <h4>Add New Consultation Note</h4>
            <form method="POST">
                <input type="hidden" name="parent_id" value="<?php echo $selected_patient; ?>">
                <div class="form-group">
                    <label>Diagnosis</label>
                    <textarea name="diagnosis" rows="3" placeholder="Enter diagnosis..."></textarea>
                </div>
                <div class="form-group">
                    <label>Prescription</label>
                    <textarea name="prescription" rows="3" placeholder="Enter prescription..."></textarea>
                </div>
                <div class="form-group">
                    <label>Doctor's Notes</label>
                    <textarea name="doctor_notes" rows="3" placeholder="Additional notes..."></textarea>
                </div>
                <button type="submit" name="add_note" class="btn btn-green">Save Record</button>
            </form>
            <?php else: ?>
            <div style="text-align:center;padding:60px;color:#999;">
                <i class="fas fa-folder-open" style="font-size:50px;margin-bottom:10px;"></i>
                <p>Select a patient to view records</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>