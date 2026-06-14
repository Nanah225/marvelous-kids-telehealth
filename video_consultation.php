<?php
session_start();
include 'db.php';

if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$fullname = $_SESSION['fullname'];
$child_name = $_SESSION['child_name'];

    
// Handle video consultation booking
if(isset($_POST['book_consultation'])){
    $doctor_id = intval($_POST['doctor_id']);
    $doctor_name = mysqli_real_escape_string($conn, $_POST['doctor_name']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $duration = intval($_POST['duration']);
    
    // Generate unique meeting ID and link
    $meeting_id = strtoupper(substr(md5(uniqid()), 0, 8));
    $meeting_link = "video_call.php?room=" . $meeting_id . "&parent_id=" . $parent_id;
    
    $insert = "INSERT INTO video_consultations (parent_id, doctor_id, doctor_name, appointment_date, appointment_time, duration, meeting_link, meeting_id, reason, status) 
               VALUES ('$parent_id', '$doctor_id', '$doctor_name', '$appointment_date', '$appointment_time', '$duration', '$meeting_link', '$meeting_id', '$reason', 'scheduled')";
    
    if(mysqli_query($conn, $insert)){
        $_SESSION['success'] = "✅ Video consultation booked successfully! Meeting link will be available at scheduled time.";
        header("Location: video_consultation.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to book consultation. Please try again.";
        header("Location: video_consultation.php");
        exit();
    }
}

// Get upcoming video consultations
$upcoming_consults = mysqli_query($conn, "SELECT * FROM video_consultations WHERE parent_id='$parent_id' AND status IN ('scheduled', 'ongoing') AND appointment_date >= CURDATE() ORDER BY appointment_date ASC, appointment_time ASC");

// Get past consultations
$past_consults = mysqli_query($conn, "SELECT * FROM video_consultations WHERE parent_id='$parent_id' AND (status='completed' OR status='cancelled' OR appointment_date < CURDATE()) ORDER BY appointment_date DESC LIMIT 5");

// Get available doctors - fixed query (removed is_available check if column doesn't exist)
$available_doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name");

// Handle start video call
if(isset($_GET['start_call']) && isset($_GET['id'])){
    $consult_id = intval($_GET['id']);
    $get_link = mysqli_query($conn, "SELECT meeting_link, meeting_id FROM video_consultations WHERE id='$consult_id' AND parent_id='$parent_id'");
    if($link_data = mysqli_fetch_assoc($get_link)){
        // Update status to ongoing
        mysqli_query($conn, "UPDATE video_consultations SET status='ongoing' WHERE id='$consult_id'");
        header("Location: " . $link_data['meeting_link']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Video Consultation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: linear-gradient(145deg, #c8e6f5 0%, #b0d4ee 100%);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
        }

        .phone {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            height: 780px;
        }

        /* Header */
        .header {
            background: linear-gradient(115deg, #1f6eeb 0%, #16b3a3 100%);
            padding: 20px;
            color: white;
            border-radius: 48px 48px 28px 28px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .back-btn {
            background: rgba(255,255,255,0.2);
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
            font-size: 20px;
        }
        .header h1 { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .child-info { font-size: 11px; opacity: 0.9; margin-top: 6px; }

        /* Content Area */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Section Title */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f3a5f;
            margin: 20px 0 12px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-title:first-of-type { margin-top: 0; }

        /* Alert Messages */
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            padding: 12px;
            border-radius: 16px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 16px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        /* Consultation Cards */
        .consult-card {
            background: white;
            border-radius: 24px;
            padding: 16px;
            margin-bottom: 14px;
            border: 1px solid #eef2f8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .consult-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .consult-doctor {
            font-weight: 700;
            font-size: 16px;
            color: #1f3a5f;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        .status-scheduled { background: #fef3c7; color: #d97706; }
        .status-ongoing { background: #d1fae5; color: #059669; animation: pulse 1s infinite; }
        .status-completed { background: #e0e7ff; color: #4338ca; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }
        
        .consult-datetime {
            font-size: 13px;
            color: #7f8c9a;
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .consult-reason {
            font-size: 12px;
            color: #4b6b8f;
            margin: 8px 0;
            padding: 8px;
            background: #f9fbfe;
            border-radius: 12px;
        }
        .join-btn {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        .join-btn:active { transform: scale(0.98); }
        .join-btn-disabled {
            background: #9ab3cf;
            cursor: not-allowed;
        }

        /* Booking Form */
        .booking-card {
            background: linear-gradient(135deg, #f9fbfe, #ffffff);
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #eef2f8;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #4b6b8f;
            display: block;
            margin-bottom: 6px;
        }
        .form-group select, .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e2ecf5;
            border-radius: 16px;
            font-size: 14px;
            font-family: inherit;
        }
        .book-btn {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
        }
        .book-btn:active { transform: scale(0.98); }

        /* Doctor Selection */
        .doctor-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1.5px solid #e2ecf5;
            border-radius: 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: 0.2s;
        }
        .doctor-option.selected {
            border-color: #1f6eeb;
            background: #f0f7ff;
        }
        .doctor-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1f6eeb, #16b3a3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        .doctor-details {
            flex: 1;
        }
        .doctor-details h4 { font-size: 14px; color: #1f3a5f; }
        .doctor-details p { font-size: 11px; color: #7f8c9a; margin: 2px 0; }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .feature-card {
            background: #f9fbfe;
            border-radius: 16px;
            padding: 12px;
            text-align: center;
        }
        .feature-icon { font-size: 28px; margin-bottom: 6px; }
        .feature-text { font-size: 11px; color: #4b6b8f; }

        .toast-msg {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e2f3e;
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 12px;
            opacity: 0;
            transition: 0.2s;
            z-index: 1000;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="phone">
    <div class="header">
        <div class="header-top">
            <a href="dashboard.php" class="back-btn">←</a>
            <div>🎥 Video Consultation</div>
        </div>
        <h1>📹 Video Call</h1>
        <div class="child-info">👶 <?php echo htmlspecialchars($child_name); ?> • Secure HD Video</div>
    </div>

    <div class="content">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert-success">✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert-error">❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Features -->
        <div class="features-grid">
            <div class="feature-card"><div class="feature-icon">🔒</div><div class="feature-text">End-to-end Encrypted</div></div>
            <div class="feature-card"><div class="feature-icon">👨‍⚕️</div><div class="feature-text">Expert Pediatricians</div></div>
            <div class="feature-card"><div class="feature-icon">📹</div><div class="feature-text">HD Quality Video</div></div>
            <div class="feature-card"><div class="feature-icon">💬</div><div class="feature-text">Live Chat Support</div></div>
        </div>

        <!-- Upcoming Video Consultations -->
        <?php if($upcoming_consults && mysqli_num_rows($upcoming_consults) > 0): ?>
        <div class="section-title">
            <span>📅 Upcoming Calls</span>
        </div>
        <?php while($consult = mysqli_fetch_assoc($upcoming_consults)): 
            $consult_datetime = strtotime($consult['appointment_date'] . ' ' . $consult['appointment_time']);
            $can_join = (time() >= strtotime('-10 minutes', $consult_datetime) && time() <= strtotime('+30 minutes', $consult_datetime));
        ?>
        <div class="consult-card">
            <div class="consult-header">
                <span class="consult-doctor">🩺 <?php echo htmlspecialchars($consult['doctor_name']); ?></span>
                <span class="status-badge status-<?php echo $consult['status']; ?>"><?php echo ucfirst($consult['status']); ?></span>
            </div>
            <div class="consult-datetime">
                <span>📅 <?php echo date('F j, Y', strtotime($consult['appointment_date'])); ?></span>
                <span>⏰ <?php echo date('g:i A', strtotime($consult['appointment_time'])); ?></span>
                <span>⏱️ <?php echo $consult['duration']; ?> min</span>
            </div>
            <div class="consult-reason">
                💬 <?php echo htmlspecialchars(substr($consult['reason'], 0, 80)); ?>
            </div>
            <?php if($can_join && $consult['status'] != 'completed'): ?>
                <a href="?start_call=1&id=<?php echo $consult['id']; ?>" class="join-btn">🔴 Join Video Call Now</a>
            <?php elseif($consult['status'] == 'scheduled'): ?>
                <button class="join-btn join-btn-disabled" disabled>⏳ Waiting for scheduled time</button>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>

        <!-- Book New Consultation -->
        <div class="section-title">📝 Book Video Consultation</div>
        <form method="POST" class="booking-card" id="bookingForm">
            <div class="form-group">
                <label>👨‍⚕️ Select Pediatrician</label>
                <div id="doctorList">
                    <?php if($available_doctors && mysqli_num_rows($available_doctors) > 0): ?>
                        <?php while($doctor = mysqli_fetch_assoc($available_doctors)): ?>
                        <div class="doctor-option" onclick="selectDoctor(<?php echo $doctor['id']; ?>, '<?php echo addslashes($doctor['name']); ?>')">
                            <div class="doctor-avatar">👨‍⚕️</div>
                            <div class="doctor-details">
                                <h4><?php echo htmlspecialchars($doctor['name']); ?></h4>
                                <p><?php echo htmlspecialchars($doctor['specialty']); ?></p>
                                <p>⭐ <?php echo $doctor['experience_years']; ?>+ years • 💰 $<?php echo $doctor['consultation_fee']; ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center; padding: 20px;">Loading doctors...</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <input type="hidden" name="doctor_id" id="selectedDoctorId" required>
            <input type="hidden" name="doctor_name" id="selectedDoctorName" required>
            
            <div class="form-group">
                <label>📅 Select Date</label>
                <input type="date" name="appointment_date" id="appointmentDate" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label>⏰ Select Time</label>
                <select name="appointment_time" id="appointmentTime" required>
                    <option value="">Select time slot</option>
                    <option value="09:00:00">09:00 AM</option>
                    <option value="10:00:00">10:00 AM</option>
                    <option value="11:00:00">11:00 AM</option>
                    <option value="14:00:00">02:00 PM</option>
                    <option value="15:00:00">03:00 PM</option>
                    <option value="16:00:00">04:00 PM</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>⏱️ Duration</label>
                <select name="duration" required>
                    <option value="30">30 minutes - Standard ($<?php echo $doctor['consultation_fee'] ?? 50; ?>)</option>
                    <option value="45">45 minutes - Extended (+$25)</option>
                    <option value="60">60 minutes - Comprehensive (+$50)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>💬 Reason for Consultation</label>
                <textarea name="reason" rows="3" placeholder="Describe your child's symptoms or concerns..." required></textarea>
            </div>
            
            <button type="submit" name="book_consultation" class="book-btn">🎥 Book Video Consultation</button>
        </form>

        <!-- Past Consultations -->
        <?php if($past_consults && mysqli_num_rows($past_consults) > 0): ?>
        <div class="section-title">📜 Past Consultations</div>
        <?php while($past = mysqli_fetch_assoc($past_consults)): ?>
        <div class="consult-card" style="opacity: 0.7;">
            <div class="consult-header">
                <span class="consult-doctor">🩺 <?php echo htmlspecialchars($past['doctor_name']); ?></span>
                <span class="status-badge status-<?php echo $past['status']; ?>"><?php echo ucfirst($past['status']); ?></span>
            </div>
            <div class="consult-datetime">
                <span>📅 <?php echo date('F j, Y', strtotime($past['appointment_date'])); ?></span>
                <span>⏰ <?php echo date('g:i A', strtotime($past['appointment_time'])); ?></span>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="booking-card" style="background: #e8f3fe;">
            <div style="font-weight:700; margin-bottom:8px;">📋 Before Your Video Call</div>
            <ul style="font-size:12px; color:#4b6b8f; margin-left:20px;">
                <li>Ensure stable internet connection</li>
                <li>Allow camera and microphone access</li>
                <li>Find a quiet, well-lit room</li>
                <li>Have child's medical records ready</li>
                <li>Join 5 minutes before scheduled time</li>
            </ul>
        </div>
    </div>
</div>

<div id="toastMsg" class="toast-msg"></div>

<script>
    let selectedDoctorId = null;
    let selectedDoctorName = null;
    
    function selectDoctor(id, name) {
        selectedDoctorId = id;
        selectedDoctorName = name;
        
        // Update UI
        document.querySelectorAll('.doctor-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        document.getElementById('selectedDoctorId').value = id;
        document.getElementById('selectedDoctorName').value = name;
    }
    
    function showToast(msg) {
        const toast = document.getElementById('toastMsg');
        toast.innerText = msg;
        toast.style.opacity = '1';
        setTimeout(() => toast.style.opacity = '0', 2500);
    }
    
    // Set minimum date for booking
    const dateInput = document.getElementById('appointmentDate');
    if(dateInput){
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }
    
    // Validate form before submit
    document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
        if(!selectedDoctorId){
            e.preventDefault();
            showToast('Please select a doctor first');
            return false;
        }
        const timeSlot = document.getElementById('appointmentTime').value;
        if(!timeSlot){
            e.preventDefault();
            showToast('Please select a time slot');
            return false;
        }
        showToast('Booking video consultation...');
    });
    
    // Check for upcoming calls reminder
    setTimeout(() => {
        const ongoingCalls = document.querySelectorAll('.status-ongoing');
        if(ongoingCalls.length > 0){
            showToast('🔔 You have an ongoing video consultation! Join now.');
        }
    }, 1000);
</script>
</body>
</html>