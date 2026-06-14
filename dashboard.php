<?php
session_start();
include 'db.php';

// Check if parent is logged in
if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$fullname = $_SESSION['fullname'];
$child_name = $_SESSION['child_name'];
$email = $_SESSION['email'] ?? '';

// Get fresh data from database including profile picture
$query = mysqli_query($conn, "SELECT * FROM parents WHERE id = '$parent_id'");
$parent_data = mysqli_fetch_assoc($query);

// Set default values for missing columns
$subscription_plan = 'Basic';
$subscription_expiry = null;

if($parent_data) {
    $fullname = $parent_data['fullname'];
    $email = $parent_data['email'];
    $child_name = $parent_data['child_name'];
    $child_age = $parent_data['child_age'] ?? 'Not specified';
    $child_dob = $parent_data['child_dob'] ?? '';
    $profile_pic = $parent_data['profile_pic'] ?? '';
    $phone = $parent_data['phone'] ?? '';
    $address = $parent_data['address'] ?? '';
    $subscription_plan = $parent_data['subscription_plan'] ?? 'Basic';
    $subscription_expiry = $parent_data['subscription_expiry'] ?? null;
}

// Calculate age from DOB if available
if(!empty($child_dob)) {
    $birthDate = new DateTime($child_dob);
    $today = new DateTime();
    $age = $birthDate->diff($today);
    $child_age = $age->y . ' years ' . $age->m . ' months';
}

// Handle profile picture upload
if(isset($_POST['upload_profile_pic'])) {
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = 'parent_' . $parent_id . '_' . time() . '.' . $ext;
            $upload_path = 'uploads/' . $new_filename;
            
            if(!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                if(!empty($profile_pic) && file_exists('uploads/' . $profile_pic)) {
                    unlink('uploads/' . $profile_pic);
                }
                
                $update_query = "UPDATE parents SET profile_pic = '$new_filename' WHERE id = '$parent_id'";
                if(mysqli_query($conn, $update_query)) {
                    $profile_pic = $new_filename;
                    $_SESSION['success'] = "Profile picture updated successfully!";
                } else {
                    $_SESSION['error'] = "Database update failed.";
                }
            } else {
                $_SESSION['error'] = "Failed to upload image.";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Allowed: JPG, PNG, GIF, WEBP";
        }
    } else {
        $_SESSION['error'] = "Please select a valid image file.";
    }
    header("Location: dashboard.php");
    exit();
}

// Handle profile update
if(isset($_POST['update_profile'])) {
    $new_fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $new_phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $new_address = mysqli_real_escape_string($conn, $_POST['address']);
    $new_child_name = mysqli_real_escape_string($conn, $_POST['child_name']);
    $new_child_dob = mysqli_real_escape_string($conn, $_POST['child_dob']);
    
    $update_query = "UPDATE parents SET 
        fullname = '$new_fullname',
        phone = '$new_phone',
        address = '$new_address',
        child_name = '$new_child_name',
        child_dob = '$new_child_dob'
        WHERE id = '$parent_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['fullname'] = $new_fullname;
        $_SESSION['child_name'] = $new_child_name;
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }
}

// Get upcoming appointments count
$appointments_count = 0;
$appointments_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE parent_id='$parent_id' AND status='confirmed' AND appointment_date >= CURDATE()");
if($appointments_data = mysqli_fetch_assoc($appointments_query)){
    $appointments_count = $appointments_data['count'];
}

// Get all doctors - fixed query with proper column check
$doctors_query = mysqli_query($conn, "SELECT id, name, specialty, rating, total_reviews, experience_years, consultation_fee, bio FROM doctors WHERE is_active=1 ORDER BY rating DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Parent Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: linear-gradient(145deg, #c8e6f5 0%, #b0d4ee 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            padding: 16px;
        }

        .phone {
            width: 100%;
            max-width: 390px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            height: 780px;
        }

        .profile-header {
            background: linear-gradient(115deg, #1f6eeb 0%, #16b3a3 100%);
            padding: 20px;
            border-radius: 48px 48px 32px 32px;
            text-align: center;
            color: white;
        }

        .avatar-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 12px;
            cursor: pointer;
        }

        .avatar-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,0.9);
        }

        .upload-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }

        .profile-header h3 { font-size: 18px; margin-top: 8px; }
        .profile-header p { font-size: 11px; opacity: 0.9; }

        .subscription-banner {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            margin: 16px;
            padding: 14px 16px;
            border-radius: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .plan-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 0 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 12px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .stat-value { font-size: 24px; font-weight: 700; color: #1f6eeb; }
        .stat-label { font-size: 10px; color: #7f8c9a; margin-top: 4px; }

        .dashboard-content {
            flex: 1;
            overflow-y: auto;
            padding: 0 16px 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1f3a5f;
            margin: 16px 0 12px 0;
            display: flex;
            justify-content: space-between;
        }

        .doctors-table {
            background: #f9fbfe;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid #eef2f8;
        }
        .doctor-row {
            display: flex;
            align-items: center;
            padding: 14px;
            border-bottom: 1px solid #eef2f8;
            gap: 12px;
        }
        .doctor-avatar-sm {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1f6eeb, #16b3a3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
        }
        .doctor-info {
            flex: 1;
        }
        .doctor-name {
            font-weight: 700;
            font-size: 14px;
            color: #1f3a5f;
        }
        .doctor-specialty {
            font-size: 11px;
            color: #7f8c9a;
            margin: 2px 0;
        }
        .doctor-rating {
            font-size: 10px;
            color: #f59e0b;
        }
        .doctor-actions {
            display: flex;
            gap: 8px;
        }
        .action-icon {
            background: #eef3fc;
            border: none;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            cursor: pointer;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }
        .service-card {
            background: white;
            border-radius: 20px;
            padding: 12px 6px;
            text-align: center;
            text-decoration: none;
            border: 1px solid #eef2f8;
            cursor: pointer;
        }
        .service-icon { font-size: 28px; margin-bottom: 6px; }
        .service-title { font-size: 11px; font-weight: 600; color: #1f3a5f; }

        .tip-card {
            background: linear-gradient(105deg, #e5f4ff, #d9efff);
            border-radius: 20px;
            padding: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #1f6eeb;
        }

        .bottom-nav {
            background: white;
            border-top: 1px solid #eef2f8;
            display: flex;
            justify-content: space-around;
            padding: 10px 12px;
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            font-size: 11px;
            color: #9ab3cf;
            gap: 3px;
        }
        .nav-item.active { color: #1f6eeb; }
        .nav-item span:first-child { font-size: 20px; }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 28px;
            padding: 24px;
            width: 90%;
            max-width: 340px;
        }
        .modal-buttons { display: flex; gap: 12px; margin-top: 20px; }
        .save-btn { background: #1f6eeb; color: white; border: none; padding: 10px; border-radius: 30px; flex: 1; cursor: pointer; }
        .cancel-btn { background: #e2e8f0; border: none; padding: 10px; border-radius: 30px; flex: 1; cursor: pointer; }
        .alert-success { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 16px; margin-bottom: 16px; font-size: 13px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 16px; margin-bottom: 16px; }

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
            pointer-events: none;
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="phone">
    <div class="profile-header">
        <div class="avatar-wrapper">
            <?php 
            $profile_img = !empty($profile_pic) && file_exists('uploads/' . $profile_pic) 
                ? 'uploads/' . $profile_pic 
                : 'https://ui-avatars.com/api/?background=2E8BFF&color=fff&bold=true&size=80&name=' . urlencode($fullname);
            ?>
            <img id="profileImage" class="avatar-img" src="<?php echo $profile_img; ?>" alt="Profile">
            <div class="upload-overlay" id="uploadIconBtn">📷</div>
        </div>
        <h3>Hello, <?php echo htmlspecialchars($fullname); ?> 👋</h3>
        <p><?php echo htmlspecialchars($child_name); ?> • <?php echo $child_age; ?></p>
    </div>

    <div class="subscription-banner" onclick="window.location.href='subscription.php'">
        <div>
            <div style="font-weight:700; font-size:13px;">🎯 <?php echo htmlspecialchars($subscription_plan); ?> Plan</div>
            <div style="font-size:10px; opacity:0.9;"><?php echo $subscription_expiry ? "Valid until: " . date('M d, Y', strtotime($subscription_expiry)) : "Upgrade for more features"; ?></div>
        </div>
        <div class="plan-badge">Upgrade →</div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-value"><?php echo $appointments_count; ?></div><div class="stat-label">Appointments</div></div>
        <div class="stat-card"><div class="stat-value">3</div><div class="stat-label">Health Records</div></div>
        <div class="stat-card"><div class="stat-value">12</div><div class="stat-label">Articles Read</div></div>
    </div>

    <div class="dashboard-content">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert-success">✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert-error">❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="section-title">
            <span>👨‍⚕️ Our Doctors</span>
            <span style="font-size:11px;"><?php echo mysqli_num_rows($doctors_query); ?> specialists</span>
        </div>
        <div class="doctors-table">
            <?php if($doctors_query && mysqli_num_rows($doctors_query) > 0): ?>
                <?php while($doctor = mysqli_fetch_assoc($doctors_query)): ?>
                <div class="doctor-row">
                    <div class="doctor-avatar-sm">👨‍⚕️</div>
                    <div class="doctor-info">
                        <div class="doctor-name"><?php echo htmlspecialchars($doctor['name']); ?></div>
                        <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty']); ?></div>
                        <div class="doctor-rating">⭐ <?php echo number_format($doctor['rating'], 1); ?> (<?php echo $doctor['total_reviews']; ?> reviews)</div>
                    </div>
                    <div class="doctor-actions">
                        <button class="action-icon" onclick="viewDoctorProfile(<?php echo $doctor['id']; ?>)">Profile</button>
                        <button class="action-icon" onclick="chatWithDoctor(<?php echo $doctor['id']; ?>, '<?php echo addslashes($doctor['name']); ?>')">💬 Chat</button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 20px; text-align: center; color: #999;">Loading doctors...</div>
            <?php endif; ?>
        </div>

        <div class="section-title">✨ Quick Services</div>
        <div class="services-grid">
            <a href="chat.php" class="service-card"><div class="service-icon">💬</div><div class="service-title">Chat Doctor</div></a>
            <a href="symptoms_checker.php" class="service-card"><div class="service-icon">🤒</div><div class="service-title">Symptoms AI</div></a>
            <a href="appointment_booking.php" class="service-card"><div class="service-icon">📅</div><div class="service-title">Book</div></a>
            <a href="health_library.php" class="service-card"><div class="service-icon">📚</div><div class="service-title">Library</div></a>
            <a href="video_consultation.php" class="service-card"><div class="service-icon">🎥</div><div class="service-title">Video Call</div></a>
            <a href="view_child_profile.php" class="service-card"><div class="service-icon">📋</div><div class="service-title">Records</div></a>
            <a href="growth.php" class="service-card"><div class="service-icon">📈</div><div class="service-title">Growth</div></a>
            <a href="vaccine.php" class="service-card"><div class="service-icon">💉</div><div class="service-title">Vaccines</div></a>
        </div>

        <div class="tip-card">
            <div style="font-weight:700; margin-bottom:6px;">💡 Daily Wellness Tip</div>
            <p id="tipText">Ensure your child drinks enough water and gets proper sleep daily.</p>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item active"><span>🏠</span><span>Home</span></a>
        <a href="chat.php" class="nav-item"><span>💬</span><span>Chat</span></a>
        <div class="nav-item" id="bookNav"><span>📅</span><span>Book</span></div>
        <div class="nav-item" id="editProfileNav"><span>✏️</span><span>Edit</span></div>
        <a href="logout.php" class="nav-item"><span>🚪</span><span>Exit</span></a>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="modal">
    <div class="modal-content">
        <h3>✏️ Edit Profile</h3>
        <form method="POST">
            <input type="text" name="fullname" placeholder="Full Name" value="<?php echo htmlspecialchars($fullname); ?>" required style="width:100%; padding:10px; margin:6px 0; border-radius:16px; border:1px solid #ddd;">
            <input type="text" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($phone); ?>" style="width:100%; padding:10px; margin:6px 0; border-radius:16px; border:1px solid #ddd;">
            <textarea name="address" placeholder="Address" rows="2" style="width:100%; padding:10px; margin:6px 0; border-radius:16px; border:1px solid #ddd;"><?php echo htmlspecialchars($address); ?></textarea>
            <input type="text" name="child_name" placeholder="Child's Name" value="<?php echo htmlspecialchars($child_name); ?>" required style="width:100%; padding:10px; margin:6px 0; border-radius:16px; border:1px solid #ddd;">
            <input type="date" name="child_dob" value="<?php echo htmlspecialchars($child_dob); ?>" style="width:100%; padding:10px; margin:6px 0; border-radius:16px; border:1px solid #ddd;">
            <div class="modal-buttons">
                <button type="submit" name="update_profile" class="save-btn">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="toastMsg" class="toast-msg"></div>

<script>
    function showToast(msg) {
        const toast = document.getElementById('toastMsg');
        toast.innerText = msg;
        toast.style.opacity = '1';
        setTimeout(() => toast.style.opacity = '0', 2500);
    }

    document.getElementById('uploadIconBtn')?.addEventListener('click', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = (e) => {
            const file = e.target.files[0];
            if(file) {
                const formData = new FormData();
                formData.append('profile_pic', file);
                formData.append('upload_profile_pic', '1');
                fetch(window.location.href, { method: 'POST', body: formData })
                    .then(() => window.location.reload());
            }
        };
        input.click();
    });

    function openModal() { document.getElementById('editProfileModal').style.display = 'flex'; }
    function closeModal() { document.getElementById('editProfileModal').style.display = 'none'; }
    document.getElementById('editProfileNav')?.addEventListener('click', openModal);
    
    function viewDoctorProfile(doctorId) {
        window.location.href = `doctor_profile.php?id=${doctorId}`;
    }

    function chatWithDoctor(doctorId, doctorName) {
        window.location.href = `chat.php?doctor_id=${doctorId}&doctor_name=${encodeURIComponent(doctorName)}`;
    }

    document.getElementById('bookNav')?.addEventListener('click', () => window.location.href = 'appointment_booking.php');

    window.onclick = (event) => {
        if(event.target.classList.contains('modal')) event.target.style.display = 'none';
    };

    const tips = [
        "💧 Hydration boosts immunity! Encourage water throughout the day.",
        "🧸 Regular hand washing prevents 50% of infections.",
        "🥦 Vitamin C rich foods strengthen immune system.",
        "🌙 10-12 hours sleep helps growth and development.",
        "🏃‍♂️ 60 minutes active play daily for healthy bones."
    ];
    let tipIndex = 0;
    setInterval(() => {
        document.getElementById('tipText').innerHTML = tips[tipIndex % tips.length];
        tipIndex++;
    }, 20000);

    setTimeout(() => showToast(`🌟 Welcome back, <?php echo htmlspecialchars($fullname); ?>!`), 500);
</script>
</body>
</html>