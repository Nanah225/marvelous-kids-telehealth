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

// Get child's date of birth for personalized vaccine schedule
$child_dob = null;
$child_age_months = 0;
$query = mysqli_query($conn, "SELECT child_name, dob FROM child_profiles WHERE parent_id='$parent_id'");
$child_data = mysqli_fetch_assoc($query);
if($child_data){
    $child_name = $child_data['child_name'] ?? $child_name;
    $child_dob = $child_data['dob'] ?? null;
    
    if($child_dob){
        $birth = new DateTime($child_dob);
        $today = new DateTime();
        $age_diff = $birth->diff($today);
        $child_age_months = ($age_diff->y * 12) + $age_diff->m;
    }
}

// Create tables if they don't exist


// Handle vaccine reminder subscription
if(isset($_POST['subscribe_reminder'])){
    $vaccine_name = mysqli_real_escape_string($conn, $_POST['vaccine_name']);
    $reminder_date = mysqli_real_escape_string($conn, $_POST['reminder_date']);
    
    $insert = "INSERT INTO vaccine_reminders (parent_id, vaccine_name, reminder_date, created_at) 
               VALUES ('$parent_id', '$vaccine_name', '$reminder_date', NOW())";
    if(mysqli_query($conn, $insert)){
        $_SESSION['success'] = "✅ Reminder set for $vaccine_name on " . date('F j, Y', strtotime($reminder_date));
    } else {
        $_SESSION['error'] = "Failed to set reminder. Please try again.";
    }
    header("Location: vaccine.php");
    exit();
}

// Mark vaccine as completed
if(isset($_GET['complete']) && isset($_GET['vaccine'])){
    $vaccine_complete = mysqli_real_escape_string($conn, $_GET['vaccine']);
    $complete_date = date('Y-m-d');
    
    // Check if already completed
    $check = mysqli_query($conn, "SELECT * FROM vaccine_history WHERE parent_id='$parent_id' AND vaccine_name='$vaccine_complete'");
    if(mysqli_num_rows($check) == 0){
        $insert = "INSERT INTO vaccine_history (parent_id, vaccine_name, date_given) 
                   VALUES ('$parent_id', '$vaccine_complete', '$complete_date')";
        if(mysqli_query($conn, $insert)){
            $_SESSION['success'] = "✅ $vaccine_complete marked as completed on " . date('F j, Y');
        } else {
            $_SESSION['error'] = "Failed to mark vaccine as completed.";
        }
    } else {
        $_SESSION['error'] = "This vaccine has already been marked as completed.";
    }
    header("Location: vaccine.php");
    exit();
}

// Get completed vaccines
$completed_vaccines = [];
$completed_query = mysqli_query($conn, "SELECT vaccine_name FROM vaccine_history WHERE parent_id='$parent_id'");
while($row = mysqli_fetch_assoc($completed_query)){
    $completed_vaccines[] = $row['vaccine_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Vaccine Center</title>
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

        /* Age Alert */
        .age-alert {
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            border-left: 4px solid #f59e0b;
            padding: 14px;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        /* Section Title */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f3a5f;
            margin: 20px 0 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title:first-of-type { margin-top: 0; }

        /* Vaccine Schedule Table */
        .vaccine-schedule {
            background: #f9fbfe;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid #eef2f8;
        }
        .schedule-header {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            padding: 12px 16px;
            font-weight: 700;
            font-size: 14px;
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr 1fr;
            gap: 8px;
        }
        .schedule-row {
            padding: 14px 16px;
            display: grid;
            grid-template-columns: 2fr 1fr 1.5fr 1fr;
            gap: 8px;
            border-bottom: 1px solid #eef2f8;
            align-items: center;
            font-size: 13px;
        }
        .vaccine-name { font-weight: 600; color: #1f3a5f; }
        .vaccine-age { color: #7f8c9a; font-size: 11px; }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }
        .status-due { background: #fee2e2; color: #dc2626; }
        .status-upcoming { background: #fef3c7; color: #d97706; }
        .status-completed { background: #d1fae5; color: #059669; }
        .action-btn {
            background: #1f6eeb;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            cursor: pointer;
        }
        .complete-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            cursor: pointer;
        }

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

        /* Vaccine Details Cards */
        .vaccine-card {
            background: white;
            border-radius: 20px;
            margin-bottom: 16px;
            overflow: hidden;
            border: 1px solid #eef2f8;
        }
        .vaccine-header {
            padding: 16px;
            background: #f9fbfe;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .vaccine-icon { font-size: 32px; }
        .vaccine-header h3 { font-size: 16px; color: #1f3a5f; flex: 1; }
        .expand-icon { font-size: 20px; color: #9ab3cf; }
        .vaccine-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding: 0 16px;
            background: white;
        }
        .vaccine-details.active {
            max-height: 400px;
            padding: 16px;
        }
        .detail-section {
            margin-bottom: 12px;
        }
        .detail-label {
            font-weight: 700;
            color: #1f6eeb;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .detail-text {
            font-size: 13px;
            color: #4b6b8f;
            line-height: 1.4;
        }

        /* Reminder Modal */
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
            border-radius: 32px;
            padding: 24px;
            width: 90%;
            max-width: 320px;
        }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 16px;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .btn-primary {
            background: #1f6eeb;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 30px;
            flex: 1;
            cursor: pointer;
        }
        .btn-secondary {
            background: #e2e8f0;
            border: none;
            padding: 12px;
            border-radius: 30px;
            flex: 1;
            cursor: pointer;
        }

        /* FAQ Section */
        .faq-item {
            background: #f9fbfe;
            border-radius: 16px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .faq-question {
            padding: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s;
            padding: 0 14px;
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }
        .faq-answer.active {
            max-height: 200px;
            padding: 0 14px 14px;
        }

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
            <div>💉 Vaccine Center</div>
        </div>
        <h1>💉 Vaccine Schedule</h1>
        <div class="child-info">👶 <?php echo htmlspecialchars($child_name); ?> • Age: <?php echo floor($child_age_months/12); ?> years <?php echo $child_age_months%12; ?> months</div>
    </div>

    <div class="content">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert-success">✅ <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert-error">❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Age-Based Alert -->
        <?php if($child_age_months >= 0 && $child_age_months <= 24): ?>
        <div class="age-alert">
            <div style="font-weight:700; margin-bottom:4px;">📢 Important Vaccine Window</div>
            <div style="font-size:12px;">Your child is in the critical vaccination period. Keep track of all due vaccines below.</div>
        </div>
        <?php endif; ?>

        <!-- Vaccine Schedule Table -->
        <div class="section-title">📋 Recommended Schedule</div>
        <div class="vaccine-schedule">
            <div class="schedule-header">
                <span>Vaccine</span>
                <span>Age</span>
                <span>Status</span>
                <span>Action</span>
            </div>
            
            <?php
            $vaccines = [
                ['name' => 'Hepatitis B', 'age' => 'Birth', 'due_age_months' => 0, 'description' => 'First dose within 24 hours of birth'],
                ['name' => 'BCG', 'age' => 'Birth', 'due_age_months' => 0, 'description' => 'Tuberculosis prevention'],
                ['name' => 'OPV (Polio)', 'age' => '6 weeks', 'due_age_months' => 1.5, 'description' => 'Oral Polio Vaccine - Dose 1'],
                ['name' => 'Pentavalent', 'age' => '6 weeks', 'due_age_months' => 1.5, 'description' => 'DPT + Hep B + Hib'],
                ['name' => 'PCV (Pneumococcal)', 'age' => '6 weeks', 'due_age_months' => 1.5, 'description' => 'Pneumonia prevention'],
                ['name' => 'Rotavirus', 'age' => '6 weeks', 'due_age_months' => 1.5, 'description' => 'Prevents severe diarrhea'],
                ['name' => 'OPV (Polio)', 'age' => '10 weeks', 'due_age_months' => 2.5, 'description' => 'Dose 2'],
                ['name' => 'Pentavalent', 'age' => '10 weeks', 'due_age_months' => 2.5, 'description' => 'Dose 2'],
                ['name' => 'Measles & Rubella', 'age' => '9 months', 'due_age_months' => 9, 'description' => 'First dose'],
                ['name' => 'Vitamin A', 'age' => '9 months', 'due_age_months' => 9, 'description' => 'First dose'],
                ['name' => 'MMR', 'age' => '12-15 months', 'due_age_months' => 13, 'description' => 'Measles, Mumps, Rubella'],
                ['name' => 'Varicella', 'age' => '12-15 months', 'due_age_months' => 13, 'description' => 'Chickenpox vaccine'],
                ['name' => 'Hepatitis A', 'age' => '12 months', 'due_age_months' => 12, 'description' => 'First dose'],
                ['name' => 'DPT Booster', 'age' => '16-18 months', 'due_age_months' => 17, 'description' => 'Diphtheria, Pertussis, Tetanus'],
                ['name' => 'MMR Booster', 'age' => '4-6 years', 'due_age_months' => 60, 'description' => 'Second dose'],
                ['name' => 'HPV', 'age' => '9-14 years', 'due_age_months' => 120, 'description' => 'Human Papillomavirus']
            ];
            
            foreach($vaccines as $vaccine):
                $is_completed = in_array($vaccine['name'], $completed_vaccines);
                
                if($is_completed){
                    $status_text = "Completed";
                    $status_class = "status-completed";
                } elseif($child_age_months >= $vaccine['due_age_months'] + 2){
                    $status_text = "Due Now!";
                    $status_class = "status-due";
                } elseif($child_age_months >= $vaccine['due_age_months'] - 1){
                    $status_text = "Due Soon";
                    $status_class = "status-due";
                } else {
                    $status_text = "Upcoming";
                    $status_class = "status-upcoming";
                }
            ?>
            <div class="schedule-row">
                <div class="vaccine-name"><?php echo $vaccine['name']; ?></div>
                <div class="vaccine-age"><?php echo $vaccine['age']; ?></div>
                <div><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></div>
                <div>
                    <?php if($is_completed): ?>
                        <span style="font-size:11px; color:#059669;">✓ Completed</span>
                    <?php else: ?>
                        <button class="action-btn" onclick="setReminder('<?php echo addslashes($vaccine['name']); ?>')">Remind</button>
                        <button class="complete-btn" onclick="completeVaccine('<?php echo addslashes($vaccine['name']); ?>')" style="margin-left:5px;">✓ Done</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Vaccine Details (Accordion) -->
        <div class="section-title">📖 Vaccine Guide</div>
        
        <div class="vaccine-card">
            <div class="vaccine-header" onclick="toggleVaccine('hepB')">
                <div class="vaccine-icon">💉</div>
                <h3>Hepatitis B</h3>
                <div class="expand-icon" id="icon-hepB">▼</div>
            </div>
            <div class="vaccine-details" id="details-hepB">
                <div class="detail-section"><div class="detail-label">📅 Schedule</div><div class="detail-text">Birth, 1 month, 6 months</div></div>
                <div class="detail-section"><div class="detail-label">🛡️ Protects Against</div><div class="detail-text">Hepatitis B virus that can cause liver disease and liver cancer</div></div>
                <div class="detail-section"><div class="detail-label">⚠️ Side Effects</div><div class="detail-text">Mild fever, soreness at injection site</div></div>
            </div>
        </div>

        <div class="vaccine-card">
            <div class="vaccine-header" onclick="toggleVaccine('mmr')">
                <div class="vaccine-icon">🛡️</div>
                <h3>MMR (Measles, Mumps, Rubella)</h3>
                <div class="expand-icon" id="icon-mmr">▼</div>
            </div>
            <div class="vaccine-details" id="details-mmr">
                <div class="detail-section"><div class="detail-label">📅 Schedule</div><div class="detail-text">12-15 months, 4-6 years</div></div>
                <div class="detail-section"><div class="detail-label">🛡️ Protects Against</div><div class="detail-text">Measles, Mumps, Rubella - highly contagious diseases</div></div>
                <div class="detail-section"><div class="detail-label">⚠️ Side Effects</div><div class="detail-text">Fever, mild rash, swollen glands</div></div>
                <div class="detail-section"><div class="detail-label">💡 Fact</div><div class="detail-text">MMR does NOT cause autism - extensively studied and proven safe</div></div>
            </div>
        </div>

        <div class="vaccine-card">
            <div class="vaccine-header" onclick="toggleVaccine('dtap')">
                <div class="vaccine-icon">🏥</div>
                <h3>DTaP (Diphtheria, Tetanus, Pertussis)</h3>
                <div class="expand-icon" id="icon-dtap">▼</div>
            </div>
            <div class="vaccine-details" id="details-dtap">
                <div class="detail-section"><div class="detail-label">📅 Schedule</div><div class="detail-text">2, 4, 6 months, 15-18 months, 4-6 years</div></div>
                <div class="detail-section"><div class="detail-label">🛡️ Protects Against</div><div class="detail-text">Diphtheria (throat infection), Tetanus (lockjaw), Pertussis (whooping cough)</div></div>
                <div class="detail-section"><div class="detail-label">⚠️ Side Effects</div><div class="detail-text">Fever, fussiness, tiredness, soreness</div></div>
            </div>
        </div>

        <div class="vaccine-card">
            <div class="vaccine-header" onclick="toggleVaccine('pcv')">
                <div class="vaccine-icon">🫁</div>
                <h3>PCV (Pneumococcal)</h3>
                <div class="expand-icon" id="icon-pcv">▼</div>
            </div>
            <div class="vaccine-details" id="details-pcv">
                <div class="detail-section"><div class="detail-label">📅 Schedule</div><div class="detail-text">2, 4, 6, 12-15 months</div></div>
                <div class="detail-section"><div class="detail-label">🛡️ Protects Against</div><div class="detail-text">Pneumonia, meningitis, blood infections</div></div>
                <div class="detail-section"><div class="detail-label">⚠️ Side Effects</div><div class="detail-text">Redness, swelling, mild fever</div></div>
            </div>
        </div>

        <div class="vaccine-card">
            <div class="vaccine-header" onclick="toggleVaccine('rotavirus')">
                <div class="vaccine-icon">🤢</div>
                <h3>Rotavirus</h3>
                <div class="expand-icon" id="icon-rotavirus">▼</div>
            </div>
            <div class="vaccine-details" id="details-rotavirus">
                <div class="detail-section"><div class="detail-label">📅 Schedule</div><div class="detail-text">2, 4 months (some schedules include 6 months)</div></div>
                <div class="detail-section"><div class="detail-label">🛡️ Protects Against</div><div class="detail-text">Severe diarrhea and vomiting caused by rotavirus</div></div>
                <div class="detail-section"><div class="detail-label">⚠️ Side Effects</div><div class="detail-text">Irritability, mild diarrhea, vomiting</div></div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="section-title">❓ Common Questions</div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">Are vaccines safe for my child? <span>▼</span></div>
            <div class="faq-answer">Yes, vaccines are thoroughly tested and monitored for safety. They undergo rigorous clinical trials before approval and continuous safety monitoring afterward. The benefits far outweigh any rare risks.</div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">What are common vaccine side effects? <span>▼</span></div>
            <div class="faq-answer">Common side effects are mild and temporary: soreness at injection site, mild fever, fussiness. Serious side effects are extremely rare (less than 1 in million doses).</div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">What if my child misses a vaccine dose? <span>▼</span></div>
            <div class="faq-answer">Catch-up vaccination is possible. Consult your pediatrician to create a catch-up schedule. It's never too late to protect your child!</div>
        </div>
    </div>
</div>

<!-- Reminder Modal -->
<div id="reminderModal" class="modal">
    <div class="modal-content">
        <h3>⏰ Set Vaccine Reminder</h3>
        <form method="POST">
            <input type="hidden" name="vaccine_name" id="reminderVaccine">
            <label>Reminder Date</label>
            <input type="date" name="reminder_date" id="reminderDate" required>
            <div class="modal-buttons">
                <button type="submit" name="subscribe_reminder" class="btn-primary">Set Reminder</button>
                <button type="button" class="btn-secondary" onclick="closeReminderModal()">Cancel</button>
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

    function setReminder(vaccineName) {
        document.getElementById('reminderVaccine').value = vaccineName;
        const defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + 7);
        document.getElementById('reminderDate').value = defaultDate.toISOString().split('T')[0];
        document.getElementById('reminderModal').style.display = 'flex';
    }

    function closeReminderModal() {
        document.getElementById('reminderModal').style.display = 'none';
    }

    function completeVaccine(vaccineName) {
        if(confirm(`Mark "${vaccineName}" as completed?`)) {
            window.location.href = `?complete=1&vaccine=${encodeURIComponent(vaccineName)}`;
        }
    }

    function toggleVaccine(id) {
        const details = document.getElementById(`details-${id}`);
        const icon = document.getElementById(`icon-${id}`);
        if(details.classList.contains('active')) {
            details.classList.remove('active');
            icon.innerHTML = '▼';
        } else {
            details.classList.add('active');
            icon.innerHTML = '▲';
        }
    }

    function toggleFAQ(element) {
        const answer = element.nextElementSibling;
        const icon = element.querySelector('span');
        if(answer.classList.contains('active')) {
            answer.classList.remove('active');
            icon.innerHTML = '▼';
        } else {
            answer.classList.add('active');
            icon.innerHTML = '▲';
        }
    }

    window.onclick = function(event) {
        const modal = document.getElementById('reminderModal');
        if(event.target == modal) {
            modal.style.display = 'none';
        }
    }

    setTimeout(() => showToast(`💡 Tip: Keep track of your child's vaccine schedule for full protection!`), 2000);
</script>
</body>
</html>