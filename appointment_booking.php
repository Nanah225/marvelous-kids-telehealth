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

// Get child profile info
$child_age = "";
$child_dob = "";
$query = mysqli_query($conn, "SELECT child_name, dob FROM child_profiles WHERE parent_id='$parent_id'");
$child = mysqli_fetch_assoc($query);
if($child){
    $child_name = $child['child_name'] ?? $child_name;
    $child_dob = $child['dob'] ?? '';
    if(!empty($child_dob)){
        $birth = new DateTime($child_dob);
        $today = new DateTime();
        $age = $birth->diff($today);
        $child_age = $age->y . " years " . $age->m . " months";
    }
}

// Handle booking submission via AJAX
if(isset($_POST['ajax_booking'])){
    header('Content-Type: application/json');
    
    $doctor_id = intval($_POST['doctor_id']);
    $slot_id = intval($_POST['slot_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Check if slot is still available
    $check_slot = mysqli_query($conn, "SELECT * FROM doctor_slots WHERE id='$slot_id' AND is_booked=0 AND slot_date='$appointment_date'");
    
    if(mysqli_num_rows($check_slot) > 0){
        $insert = "INSERT INTO appointments (parent_id, doctor_id, slot_id, appointment_date, appointment_time, reason, status, created_at) 
                    VALUES ('$parent_id', '$doctor_id', '$slot_id', '$appointment_date', '$appointment_time', '$reason', 'confirmed', NOW())";
        
        if(mysqli_query($conn, $insert)){
            // Mark slot as booked
            mysqli_query($conn, "UPDATE doctor_slots SET is_booked=1 WHERE id='$slot_id'");
            
            // Get doctor name
            $doc_query = mysqli_query($conn, "SELECT name FROM doctors WHERE id='$doctor_id'");
            $doctor = mysqli_fetch_assoc($doc_query);
            
            echo json_encode([
                'success' => true,
                'message' => "✅ Appointment booked successfully!\n\n📅 Date: " . date('F j, Y', strtotime($appointment_date)) . "\n⏰ Time: " . date('g:i A', strtotime($appointment_time)) . "\n👨‍⚕️ Doctor: " . $doctor['name'] . "\n\nWe look forward to seeing you and your child! A reminder will be sent before the appointment.",
                'doctor_name' => $doctor['name'],
                'date' => $appointment_date,
                'time' => $appointment_time
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to book appointment. Please try again.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sorry, this time slot is no longer available. Please select another slot.']);
    }
    exit();
}

// Get all active doctors
$doctors_query = mysqli_query($conn, "SELECT * FROM doctors WHERE is_active=1 ORDER BY name");

// Get upcoming appointments for display
$upcoming_appointments = mysqli_query($conn, "SELECT a.*, d.name as doctor_name, d.specialty 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.parent_id='$parent_id' AND a.status IN ('confirmed', 'pending') AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC");

// Get past appointments
$past_appointments = mysqli_query($conn, "SELECT a.*, d.name as doctor_name, d.specialty 
    FROM appointments a 
    JOIN doctors d ON a.doctor_id = d.id 
    WHERE a.parent_id='$parent_id' AND (a.status='completed' OR a.appointment_date < CURDATE())
    ORDER BY a.appointment_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids | Book Appointment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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
            max-width: 450px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
            height: 780px;
            position: relative;
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
            transition: 0.2s;
        }
        .back-btn:active { transform: scale(0.95); }
        .header h1 { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .child-info { font-size: 12px; opacity: 0.9; margin-top: 6px; }

        /* Scrollable Content */
        .content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        /* Section Titles */
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

        /* Doctor Cards */
        .doctor-card {
            background: #f9fbfe;
            border-radius: 24px;
            margin-bottom: 16px;
            overflow: hidden;
            border: 1px solid #eef2f8;
            transition: all 0.2s;
        }
        .doctor-header {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
        }
        .doctor-avatar {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #1f6eeb, #16b3a3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        .doctor-info {
            flex: 1;
        }
        .doctor-name {
            font-weight: 700;
            font-size: 16px;
            color: #1f3a5f;
        }
        .doctor-specialty {
            font-size: 12px;
            color: #7f8c9a;
            margin-top: 3px;
        }
        .doctor-exp {
            font-size: 11px;
            color: #10b981;
            margin-top: 3px;
        }
        .expand-icon {
            font-size: 20px;
            color: #9ab3cf;
            transition: 0.2s;
        }
        .doctor-slots {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
            background: white;
            border-top: 1px solid #eef2f8;
            padding: 0 16px;
        }
        .doctor-slots.active {
            max-height: 500px;
            padding: 16px;
        }
        .slots-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }
        .slot-btn {
            background: #eef3fc;
            border: none;
            padding: 10px 14px;
            border-radius: 30px;
            font-size: 12px;
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
            min-width: 85px;
        }
        .slot-btn:hover { background: #e0e8f3; }
        .slot-btn.selected {
            background: #1f6eeb;
            color: white;
        }
        .slot-date {
            font-weight: 600;
            font-size: 11px;
            color: #4b6b8f;
        }
        .slot-time {
            font-size: 13px;
            font-weight: 600;
        }

        /* Booking Form */
        .booking-panel {
            background: linear-gradient(135deg, #f0f7ff, #e8f3fe);
            border-radius: 28px;
            padding: 20px;
            margin-top: 24px;
            display: none;
            border: 1px solid #cde3f5;
        }
        .booking-panel h3 {
            color: #1f6eeb;
            margin-bottom: 16px;
            font-size: 18px;
        }
        .booking-info {
            background: white;
            padding: 12px;
            border-radius: 20px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            font-size: 13px;
            font-weight: 600;
            color: #4b6b8f;
            display: block;
            margin-bottom: 6px;
        }
        textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e2ecf5;
            border-radius: 16px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
        }
        textarea:focus {
            outline: none;
            border-color: #1f6eeb;
        }
        .btn-book {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 30px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-book:active { transform: scale(0.98); }

        /* Appointment Cards */
        .appointment-card {
            background: white;
            border-radius: 20px;
            padding: 14px;
            margin-bottom: 12px;
            border-left: 4px solid #1f6eeb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .appointment-doctor {
            font-weight: 700;
            color: #1f3a5f;
        }
        .appointment-datetime {
            font-size: 12px;
            color: #6b8aae;
            margin: 6px 0;
        }
        .status-confirmed {
            background: #d1fae5;
            color: #059669;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }

        /* Success Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 3000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 32px;
            width: 85%;
            max-width: 340px;
            text-align: center;
            padding: 28px 24px;
            animation: modalPop 0.3s ease;
        }
        @keyframes modalPop {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .modal-icon { font-size: 64px; margin-bottom: 16px; }
        .modal-title { font-size: 22px; font-weight: 700; color: #1f3a5f; margin-bottom: 12px; }
        .modal-message { color: #4b6b8f; font-size: 14px; line-height: 1.5; margin-bottom: 20px; }
        .modal-btn {
            background: linear-gradient(115deg, #1f6eeb, #16b3a3);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 20px;
            color: #9ab3cf;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e2f3e;
            color: white;
            padding: 10px 18px;
            border-radius: 40px;
            font-size: 13px;
            z-index: 2000;
            opacity: 0;
            transition: 0.2s;
            pointer-events: none;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<div class="phone">
    <div class="header">
        <div class="header-top">
            <a href="dashboard.php" class="back-btn">←</a>
            <div>📅 Appointment Center</div>
        </div>
        <h1>📋 Book Appointment</h1>
        <div class="child-info">👶 <?php echo htmlspecialchars($child_name); ?> • <?php echo $child_age ?: 'Set age in profile'; ?></div>
    </div>

    <div class="content">
        <!-- Upcoming Appointments Section -->
        <?php if(mysqli_num_rows($upcoming_appointments) > 0): ?>
        <div class="section-title">📌 Your Upcoming Appointments</div>
        <?php while($apt = mysqli_fetch_assoc($upcoming_appointments)): ?>
        <div class="appointment-card">
            <div class="appointment-doctor">🩺 <?php echo htmlspecialchars($apt['doctor_name']); ?> • <?php echo htmlspecialchars($apt['specialty']); ?></div>
            <div class="appointment-datetime">📅 <?php echo date('F j, Y', strtotime($apt['appointment_date'])); ?> • ⏰ <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></div>
            <div><span class="status-confirmed">✓ <?php echo ucfirst($apt['status']); ?></span></div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>

        <!-- Doctors Section -->
        <div class="section-title">👨‍⚕️ Our Pediatric Specialists</div>
        <div id="doctorsContainer">
            <?php while($doctor = mysqli_fetch_assoc($doctors_query)): ?>
            <div class="doctor-card" data-doctor-id="<?php echo $doctor['id']; ?>">
                <div class="doctor-header" onclick="toggleDoctorSlots(<?php echo $doctor['id']; ?>)">
                    <div class="doctor-avatar"><?php echo substr($doctor['name'], 0, 2); ?></div>
                    <div class="doctor-info">
                        <div class="doctor-name"><?php echo htmlspecialchars($doctor['name']); ?></div>
                        <div class="doctor-specialty"><?php echo htmlspecialchars($doctor['specialty']); ?></div>
                        <div class="doctor-exp">⭐ <?php echo $doctor['experience_years'] ?? 8; ?>+ years experience</div>
                    </div>
                    <div class="expand-icon" id="expandIcon<?php echo $doctor['id']; ?>">▼</div>
                </div>
                <div class="doctor-slots" id="slots<?php echo $doctor['id']; ?>">
                    <div class="loading">Loading available time slots...</div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Booking Panel -->
        <div class="booking-panel" id="bookingPanel">
            <h3>📝 Confirm Your Appointment</h3>
            <div class="booking-info" id="bookingInfo">
                <div>👨‍⚕️ <strong id="selectedDoctorName">-</strong></div>
                <div>📅 <strong id="selectedDateDisplay">-</strong></div>
                <div>⏰ <strong id="selectedTimeDisplay">-</strong></div>
            </div>
            <div class="form-group">
                <label>💬 Reason for Visit</label>
                <textarea id="appointmentReason" rows="3" placeholder="Please describe symptoms or reason for consultation..."></textarea>
            </div>
            <button class="btn-book" id="confirmBookingBtn">✅ Confirm Booking</button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <div class="modal-icon">🎉</div>
        <div class="modal-title">Appointment Confirmed!</div>
        <div class="modal-message" id="modalMessage"></div>
        <button class="modal-btn" onclick="closeModal()">Great, Thanks!</button>
    </div>
</div>

<div id="toastMsg" class="toast"></div>

<script>
    // Global variables
    let selectedSlot = null;
    let selectedDoctor = null;
    let selectedSlotData = null;
    
    // Doctor slots data (populated via AJAX)
    const slotsCache = {};
    
    // Toggle doctor slots and load available slots
    async function toggleDoctorSlots(doctorId) {
        const slotsDiv = document.getElementById(`slots${doctorId}`);
        const icon = document.getElementById(`expandIcon${doctorId}`);
        const isOpen = slotsDiv.classList.contains('active');
        
        // Close all other slots
        document.querySelectorAll('.doctor-slots').forEach(slot => {
            if(slot.id !== `slots${doctorId}`) {
                slot.classList.remove('active');
                const otherIcon = document.getElementById(`expandIcon${slot.id.replace('slots', '')}`);
                if(otherIcon) otherIcon.innerHTML = '▼';
            }
        });
        
        if(!isOpen) {
            slotsDiv.classList.add('active');
            icon.innerHTML = '▲';
            
            // Load slots if not cached
            if(!slotsCache[doctorId]) {
                await loadDoctorSlots(doctorId);
            } else {
                renderSlots(doctorId, slotsCache[doctorId]);
            }
        } else {
            slotsDiv.classList.remove('active');
            icon.innerHTML = '▼';
            // Hide booking panel if open
            document.getElementById('bookingPanel').style.display = 'none';
            selectedSlot = null;
            selectedSlotData = null;
        }
    }
    
    // Load available slots for a doctor via AJAX
    function loadDoctorSlots(doctorId) {
        return new Promise((resolve) => {
            fetch(`get_doctor_slots.php?doctor_id=${doctorId}`)
                .then(response => response.json())
                .then(data => {
                    slotsCache[doctorId] = data.slots || [];
                    renderSlots(doctorId, slotsCache[doctorId]);
                    resolve();
                })
                .catch(error => {
                    console.error('Error loading slots:', error);
                    // Fallback: generate sample slots
                    const fallbackSlots = generateSampleSlots();
                    slotsCache[doctorId] = fallbackSlots;
                    renderSlots(doctorId, fallbackSlots);
                    resolve();
                });
        });
    }
    
    // Generate sample slots (fallback if no data)
    function generateSampleSlots() {
        const slots = [];
        const today = new Date();
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const times = ['09:00 AM', '10:30 AM', '02:00 PM', '03:30 PM'];
        
        for(let i = 1; i <= 5; i++) {
            let date = new Date();
            date.setDate(today.getDate() + i);
            let dateStr = date.toISOString().split('T')[0];
            let dayName = days[date.getDay()];
            let month = date.getMonth() + 1;
            let day = date.getDate();
            let displayDate = `${dayName}, ${month}/${day}`;
            
            for(let t of times) {
                slots.push({
                    id: `${dateStr}_${t}`,
                    date: dateStr,
                    date_display: displayDate,
                    time: t,
                    time_24: convertTo24Hour(t)
                });
            }
        }
        return slots;
    }
    
    function convertTo24Hour(time12) {
        if(time12.includes('09:00 AM')) return '09:00:00';
        if(time12.includes('10:30 AM')) return '10:30:00';
        if(time12.includes('02:00 PM')) return '14:00:00';
        if(time12.includes('03:30 PM')) return '15:30:00';
        return '10:00:00';
    }
    
    // Render slots for a doctor
    function renderSlots(doctorId, slots) {
        const slotsDiv = document.getElementById(`slots${doctorId}`);
        if(!slotsDiv) return;
        
        if(slots.length === 0) {
            slotsDiv.innerHTML = '<div style="padding:12px; text-align:center; color:#999;">No available slots for this week</div>';
            return;
        }
        
        let html = '<div class="slots-grid">';
        slots.forEach(slot => {
            html += `
                <button class="slot-btn" onclick="selectSlot(event, ${doctorId}, '${slot.id}', '${slot.date}', '${slot.time}', '${slot.date_display}')">
                    <div class="slot-date">${slot.date_display}</div>
                    <div class="slot-time">${slot.time}</div>
                </button>
            `;
        });
        html += '</div>';
        slotsDiv.innerHTML = html;
    }
    
    // Select a time slot
    function selectSlot(event, doctorId, slotId, date, time, dateDisplay) {
        // Remove selected class from all slots in this doctor
        const slotsDiv = document.getElementById(`slots${doctorId}`);
        slotsDiv.querySelectorAll('.slot-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Get doctor info
        const doctorCard = document.querySelector(`.doctor-card[data-doctor-id="${doctorId}"]`);
        const doctorName = doctorCard.querySelector('.doctor-name').innerText;
        const doctorSpecialty = doctorCard.querySelector('.doctor-specialty').innerText;
        
        selectedSlot = slotId;
        selectedDoctor = { id: doctorId, name: doctorName, specialty: doctorSpecialty };
        selectedSlotData = { date: date, time: time, dateDisplay: dateDisplay };
        
        // Show booking panel
        const panel = document.getElementById('bookingPanel');
        panel.style.display = 'block';
        
        document.getElementById('selectedDoctorName').innerHTML = `${doctorName} (${doctorSpecialty})`;
        document.getElementById('selectedDateDisplay').innerHTML = dateDisplay;
        document.getElementById('selectedTimeDisplay').innerHTML = time;
        
        // Scroll to panel
        panel.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Confirm booking via AJAX
    function confirmBooking() {
        if(!selectedSlot || !selectedDoctor) {
            showToast('Please select a doctor and time slot first');
            return;
        }
        
        const reason = document.getElementById('appointmentReason').value.trim();
        if(!reason) {
            showToast('Please describe the reason for visit');
            return;
        }
        
        // Disable button to prevent double submit
        const btn = document.getElementById('confirmBookingBtn');
        btn.disabled = true;
        btn.innerText = '⏳ Booking...';
        
        const formData = new URLSearchParams();
        formData.append('ajax_booking', '1');
        formData.append('doctor_id', selectedDoctor.id);
        formData.append('slot_id', selectedSlot);
        formData.append('appointment_date', selectedSlotData.date);
        formData.append('appointment_time', convertTo24HourForDB(selectedSlotData.time));
        formData.append('reason', reason);
        
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Show success modal
                document.getElementById('modalMessage').innerHTML = data.message.replace(/\n/g, '<br>');
                document.getElementById('successModal').style.display = 'flex';
                
                // Reset form
                document.getElementById('bookingPanel').style.display = 'none';
                document.getElementById('appointmentReason').value = '';
                selectedSlot = null;
                
                // Reload page after modal closes to show updated appointments
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                showToast(data.message);
                btn.disabled = false;
                btn.innerText = '✅ Confirm Booking';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error. Please try again.');
            btn.disabled = false;
            btn.innerText = '✅ Confirm Booking';
        });
    }
    
    function convertTo24HourForDB(time12) {
        if(time12.includes('09:00 AM')) return '09:00:00';
        if(time12.includes('10:30 AM')) return '10:30:00';
        if(time12.includes('02:00 PM')) return '14:00:00';
        if(time12.includes('03:30 PM')) return '15:30:00';
        return '10:00:00';
    }
    
    function showToast(message) {
        const toast = document.getElementById('toastMsg');
        toast.innerText = message;
        toast.style.opacity = '1';
        setTimeout(() => {
            toast.style.opacity = '0';
        }, 3000);
    }
    
    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
    }
    
    // Event listeners
    document.getElementById('confirmBookingBtn').addEventListener('click', confirmBooking);
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('successModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    // Auto-load slots for first doctor on page load (optional)
    document.addEventListener('DOMContentLoaded', () => {
        const firstDoctor = document.querySelector('.doctor-card');
        if(firstDoctor) {
            const firstId = firstDoctor.dataset.doctorId;
            // Preload slots in background
            loadDoctorSlots(firstId);
        }
    });
</script>
</body>
</html>

<?php
/* 
   Create this separate file: get_doctor_slots.php
   
   
*/
?>