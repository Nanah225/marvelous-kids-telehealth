<?php
session_start();
include 'db.php';

// Increase memory and execution time for file uploads
ini_set('memory_limit', '128M');
ini_set('max_execution_time', 300);
ini_set('post_max_size', '20M');
ini_set('upload_max_filesize', '20M');

// Check if parent is logged in
if(!isset($_SESSION['parent_id'])){
    header("Location: login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['fullname'];

// Get doctor info
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$doctor_name = isset($_GET['doctor_name']) ? $_GET['doctor_name'] : 'Doctor';

if($doctor_id == 0){
    header("Location: dashboard.php");
    exit();
}

// Verify doctor exists and is verified
$doctor_check = mysqli_query($conn, "SELECT * FROM doctors WHERE id='$doctor_id' AND verification_status='approved'");
if(mysqli_num_rows($doctor_check) == 0){
    header("Location: dashboard.php");
    exit();
}

$doctor = mysqli_fetch_assoc($doctor_check);

// Create directories if they don't exist
$upload_dirs = ['uploads', 'uploads/attachments', 'uploads/voice_notes'];
foreach($upload_dirs as $dir) {
    if(!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Handle sending text message
if(isset($_POST['send_message'])){
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    if(!empty($message)){
        mysqli_query($conn, "INSERT INTO messages (parent_id, doctor_id, message, sender, created_at) 
                            VALUES ('$parent_id', '$doctor_id', '$message', 'parent', NOW())");
        echo json_encode(['success' => true]);
        exit();
    }
}

// Handle image/attachment upload
if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0){
    $upload_dir = 'uploads/attachments/';
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $_FILES['attachment']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if(in_array($ext, $allowed)){
        $new_filename = 'attachment_' . $parent_id . '_' . $doctor_id . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
        $filepath = $upload_dir . $new_filename;
        
        if(move_uploaded_file($_FILES['attachment']['tmp_name'], $filepath)){
            $message_text = '[Image]';
            mysqli_query($conn, "INSERT INTO messages (parent_id, doctor_id, message, attachment, sender, created_at) 
                                VALUES ('$parent_id', '$doctor_id', '$message_text', '$filepath', 'parent', NOW())");
            echo json_encode(['success' => true, 'file' => $filepath]);
            exit();
        }
    }
    echo json_encode(['success' => false, 'error' => 'Invalid file']);
    exit();
}

// Handle voice note upload
if(isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] == 0){
    $upload_dir = 'uploads/voice_notes/';
    
    $file_tmp = $_FILES['voice_note']['tmp_name'];
    $file_size = $_FILES['voice_note']['size'];
    
    if($file_size < 1000) {
        echo json_encode(['success' => false, 'error' => 'Recording too short']);
        exit();
    }
    
    $filename = 'voice_' . $parent_id . '_' . $doctor_id . '_' . time() . '_' . rand(100, 999) . '.webm';
    $filepath = $upload_dir . $filename;
    
    if(move_uploaded_file($file_tmp, $filepath)){
        $message_text = '[Voice Note]';
        mysqli_query($conn, "INSERT INTO messages (parent_id, doctor_id, message, attachment, sender, created_at) 
                            VALUES ('$parent_id', '$doctor_id', '$message_text', '$filepath', 'parent', NOW())");
        echo json_encode(['success' => true, 'file' => $filepath]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
        exit();
    }
}

// AJAX request for new messages
if(isset($_GET['ajax'])){
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    $messages_query = mysqli_query($conn, "SELECT * FROM messages 
                                           WHERE (parent_id='$parent_id' AND doctor_id='$doctor_id') AND id > '$last_id'
                                           ORDER BY created_at ASC");
    $messages = [];
    while($msg = mysqli_fetch_assoc($messages_query)){
        $messages[] = $msg;
    }
    header('Content-Type: application/json');
    echo json_encode($messages);
    exit();
}

// Get all messages
$messages_query = mysqli_query($conn, "SELECT * FROM messages 
                                       WHERE (parent_id='$parent_id' AND doctor_id='$doctor_id')
                                       ORDER BY created_at ASC");
$all_messages = [];
while($msg = mysqli_fetch_assoc($messages_query)){
    $all_messages[] = $msg;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Chat with Dr. <?php echo htmlspecialchars($doctor_name); ?> - Marvelous Kids</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: #1a1a2e;
            height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .chat-header {
            background: linear-gradient(135deg, #2E8BFF, #00C896);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
            font-size: 18px;
        }

        .doctor-avatar {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .doctor-info {
            flex: 1;
        }

        .doctor-name {
            font-size: 17px;
            font-weight: 600;
            color: white;
        }

        .doctor-status {
            font-size: 11px;
            opacity: 0.9;
            color: white;
        }

        .call-buttons {
            display: flex;
            gap: 15px;
        }

        .call-btn {
            background: rgba(255,255,255,0.2);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #f5f7fb;
        }

        .message {
            display: flex;
            margin-bottom: 4px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message-bubble {
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 20px;
            position: relative;
            word-wrap: break-word;
        }

        .message.sent .message-bubble {
            background: #2E8BFF;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.received .message-bubble {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .message-time {
            font-size: 9px;
            margin-top: 5px;
            opacity: 0.6;
        }

        .message.sent .message-time {
            text-align: right;
        }

        .message-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 5px;
        }

        .voice-message {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            min-width: 180px;
        }

        .voice-play-btn {
            width: 36px;
            height: 36px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .message.sent .voice-play-btn {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .voice-wave {
            flex: 1;
            height: 30px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .voice-wave span {
            width: 3px;
            background: currentColor;
            border-radius: 2px;
        }

        .input-area {
            background: white;
            padding: 12px 16px;
            display: flex;
            gap: 10px;
            align-items: center;
            border-top: 1px solid #eef2f8;
            position: sticky;
            bottom: 0;
        }

        .attach-btn, .voice-record-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #2E8BFF;
            padding: 8px;
        }

        .voice-record-btn {
            color: #e74c3c;
        }

        .message-input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            outline: none;
            font-size: 15px;
            background: #f5f7fb;
        }

        .send-btn {
            background: #2E8BFF;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .recording-modal {
            position: fixed;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: #1a1a2e;
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 200;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        .recording-timer {
            font-size: 18px;
            font-weight: bold;
            font-family: monospace;
        }

        .stop-recording {
            background: #e74c3c;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
        }

        .no-messages {
            text-align: center;
            color: #999;
            padding: 60px 20px;
        }

        .image-preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .image-preview-modal img {
            max-width: 90%;
            max-height: 90%;
        }

        .close-preview {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }

        .recording-active {
            animation: pulse 0.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <div class="chat-header">
        <a href="my-messages.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="doctor-avatar">
            <i class="fas fa-user-md"></i>
        </div>
        <div class="doctor-info">
            <div class="doctor-name">Dr. <?php echo htmlspecialchars($doctor_name); ?></div>
            <div class="doctor-status">
                <i class="fas fa-circle" style="font-size:8px;color:#4ade80;"></i> Online
            </div>
        </div>
        <div class="call-buttons">
            <button onclick="makeVoiceCall()" class="call-btn"><i class="fas fa-phone"></i></button>
            <button onclick="makeVideoCall()" class="call-btn"><i class="fas fa-video"></i></button>
        </div>
    </div>
    
    <div class="messages-container" id="messagesContainer">
        <?php if(!empty($all_messages)): ?>
            <?php foreach($all_messages as $msg): ?>
            <div class="message <?php echo $msg['sender'] == 'parent' ? 'sent' : 'received'; ?>" data-message-id="<?php echo $msg['id']; ?>">
                <div class="message-bubble">
                    <?php if(strpos($msg['message'], '[Voice Note]') !== false && !empty($msg['attachment'])): ?>
                        <div class="voice-message" data-audio="<?php echo $msg['attachment']; ?>">
                            <div class="voice-play-btn"><i class="fas fa-play"></i></div>
                            <div class="voice-wave" style="display: none;">
                                <span></span><span></span><span></span><span></span><span></span>
                            </div>
                            <span class="voice-duration">Voice note</span>
                        </div>
                    <?php elseif(strpos($msg['message'], '[Image]') !== false && !empty($msg['attachment'])): ?>
                        <img src="<?php echo $msg['attachment']; ?>" class="message-image" onclick="showImagePreview('<?php echo $msg['attachment']; ?>')">
                        <div class="message-time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                    <?php else: ?>
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        <div class="message-time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-messages">
                <i class="fas fa-comments" style="font-size:50px;margin-bottom:15px;color:#ccc;"></i>
                <p>No messages yet. Start a conversation with Dr. <?php echo htmlspecialchars($doctor_name); ?>!</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="input-area">
        <button class="attach-btn" id="attachBtn">
            <i class="fas fa-paperclip"></i>
        </button>
        <button class="voice-record-btn" id="voiceRecordBtn">
            <i class="fas fa-microphone"></i>
        </button>
        <input type="text" class="message-input" id="messageInput" placeholder="Type a message..." autocomplete="off">
        <button class="send-btn" id="sendBtn">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
    
    <input type="file" id="fileInput" style="display: none;" accept="image/*">
    
    <div id="recordingModal" class="recording-modal" style="display: none;">
        <i class="fas fa-microphone" style="color: #e74c3c;"></i>
        <span class="recording-timer" id="recordingTimer">0:00</span>
        <span style="font-size:12px;">Recording... Release to send</span>
        <button class="stop-recording" id="stopRecordingBtn">
            <i class="fas fa-trash"></i>
        </button>
    </div>
    
    <div id="imagePreviewModal" class="image-preview-modal" onclick="closeImagePreview()">
        <span class="close-preview">&times;</span>
        <img id="previewImage" src="">
    </div>
    
    <script>
        let mediaRecorder;
        let audioChunks = [];
        let recordingTimer;
        let recordingSeconds = 0;
        let isRecording = false;
        let lastMessageId = <?php echo !empty($all_messages) ? end($all_messages)['id'] : 0; ?>;
        
        const messagesContainer = document.getElementById('messagesContainer');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const voiceRecordBtn = document.getElementById('voiceRecordBtn');
        const recordingModal = document.getElementById('recordingModal');
        const recordingTimerEl = document.getElementById('recordingTimer');
        const stopRecordingBtn = document.getElementById('stopRecordingBtn');
        const attachBtn = document.getElementById('attachBtn');
        const fileInput = document.getElementById('fileInput');
        
        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        scrollToBottom();
        
        function initVoiceMessages() {
            document.querySelectorAll('.voice-message').forEach(voiceMsg => {
                voiceMsg.removeEventListener('click', voiceClickHandler);
                voiceMsg.addEventListener('click', voiceClickHandler);
            });
        }
        
        function voiceClickHandler(e) {
            const audioSrc = this.getAttribute('data-audio');
            const playBtn = this.querySelector('.voice-play-btn i');
            const waveElement = this.querySelector('.voice-wave');
            
            if(window.currentAudio && !window.currentAudio.paused) {
                window.currentAudio.pause();
                if(window.currentAudio.src === audioSrc) {
                    playBtn.className = 'fas fa-play';
                    waveElement.style.display = 'none';
                    window.currentAudio = null;
                    return;
                }
            }
            
            const audio = new Audio(audioSrc);
            window.currentAudio = audio;
            
            audio.onplay = () => {
                playBtn.className = 'fas fa-pause';
                waveElement.style.display = 'flex';
                
                const waves = waveElement.querySelectorAll('span');
                const waveInterval = setInterval(() => {
                    if(audio.paused) {
                        clearInterval(waveInterval);
                        return;
                    }
                    waves.forEach(wave => {
                        const height = Math.random() * 20 + 5;
                        wave.style.height = height + 'px';
                    });
                }, 150);
                
                audio.onended = () => {
                    clearInterval(waveInterval);
                    playBtn.className = 'fas fa-play';
                    waveElement.style.display = 'none';
                    waves.forEach(wave => wave.style.height = '10px');
                    window.currentAudio = null;
                };
            };
            
            audio.play();
        }
        
        async function sendMessage() {
            const message = messageInput.value.trim();
            if(message === '') return;
            
            try {
                const formData = new FormData();
                formData.append('send_message', '1');
                formData.append('message', message);
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if(result.success) {
                    messageInput.value = '';
                    checkNewMessages();
                }
            } catch(err) {
                console.error('Error:', err);
                alert('Failed to send message');
            }
        }
        
        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', (e) => {
            if(e.key === 'Enter') sendMessage();
        });
        
        attachBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if(file && file.type.startsWith('image/')) {
                const formData = new FormData();
                formData.append('attachment', file);
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if(result.success) {
                        checkNewMessages();
                    } else {
                        alert('Failed to send image');
                    }
                } catch(err) {
                    alert('Network error');
                }
                fileInput.value = '';
            }
        });
        
        // FIXED VOICE RECORDING
        let currentStream = null;
        
        async function startRecording() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                currentStream = stream;
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                
                mediaRecorder.ondataavailable = (event) => {
                    if(event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                };
                
                mediaRecorder.onstop = async () => {
                    if(audioChunks.length === 0) {
                        if(currentStream) {
                            currentStream.getTracks().forEach(track => track.stop());
                            currentStream = null;
                        }
                        return;
                    }
                    
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    
                    if(audioBlob.size < 1000) {
                        alert('Recording too short. Please speak for at least 1 second.');
                        if(currentStream) {
                            currentStream.getTracks().forEach(track => track.stop());
                            currentStream = null;
                        }
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('voice_note', audioBlob, 'voice_note.webm');
                    
                    voiceRecordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    
                    try {
                        const response = await fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if(result.success) {
                            checkNewMessages();
                        } else {
                            alert('Failed to send voice note: ' + (result.error || 'Unknown error'));
                        }
                    } catch(err) {
                        console.error('Upload error:', err);
                        alert('Network error. Please try again.');
                    }
                    
                    voiceRecordBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                    
                    if(currentStream) {
                        currentStream.getTracks().forEach(track => track.stop());
                        currentStream = null;
                    }
                };
                
                mediaRecorder.start(100);
                isRecording = true;
                recordingSeconds = 0;
                recordingTimerEl.textContent = '0:00';
                recordingModal.style.display = 'flex';
                voiceRecordBtn.classList.add('recording-active');
                
                recordingTimer = setInterval(() => {
                    recordingSeconds++;
                    const minutes = Math.floor(recordingSeconds / 60);
                    const seconds = recordingSeconds % 60;
                    recordingTimerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    
                    if(recordingSeconds >= 60) {
                        stopRecording();
                    }
                }, 1000);
                
            } catch(err) {
                console.error('Microphone error:', err);
                alert('Unable to access microphone. Please check your permissions.');
            }
        }
        
        function stopRecording() {
            if(mediaRecorder && isRecording && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                clearInterval(recordingTimer);
                recordingModal.style.display = 'none';
                voiceRecordBtn.classList.remove('recording-active');
                isRecording = false;
            }
        }
        
        function cancelRecording() {
            if(mediaRecorder && isRecording) {
                mediaRecorder.onstop = null;
                mediaRecorder.stop();
                clearInterval(recordingTimer);
                recordingModal.style.display = 'none';
                voiceRecordBtn.classList.remove('recording-active');
                isRecording = false;
                audioChunks = [];
                
                if(currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                    currentStream = null;
                }
            }
        }
        
        voiceRecordBtn.addEventListener('mousedown', (e) => {
            e.preventDefault();
            startRecording();
        });
        
        voiceRecordBtn.addEventListener('mouseup', () => {
            if(isRecording) {
                stopRecording();
            }
        });
        
        voiceRecordBtn.addEventListener('mouseleave', () => {
            if(isRecording) {
                stopRecording();
            }
        });
        
        voiceRecordBtn.addEventListener('touchstart', (e) => {
            e.preventDefault();
            startRecording();
        });
        
        voiceRecordBtn.addEventListener('touchend', (e) => {
            e.preventDefault();
            if(isRecording) {
                stopRecording();
            }
        });
        
        stopRecordingBtn.addEventListener('click', () => {
            if(isRecording) {
                cancelRecording();
            }
        });
        
        function showImagePreview(src) {
            document.getElementById('previewImage').src = src;
            document.getElementById('imagePreviewModal').style.display = 'flex';
        }
        
        function closeImagePreview() {
            document.getElementById('imagePreviewModal').style.display = 'none';
        }
        
        function makeVoiceCall() {
            const doctorPhone = '<?php echo $doctor['phone'] ?? ''; ?>';
            if(doctorPhone) {
                window.location.href = `tel:${doctorPhone}`;
            } else {
                alert('Doctor phone number not available');
            }
        }
        
        function makeVideoCall() {
            alert('Video call feature coming soon');
        }
        
        async function checkNewMessages() {
            try {
                const response = await fetch(window.location.href + '&ajax=1&last_id=' + lastMessageId);
                const newMessages = await response.json();
                
                if(newMessages.length > 0) {
                    for(const msg of newMessages) {
                        addMessageToChat(msg);
                        if(msg.id > lastMessageId) {
                            lastMessageId = msg.id;
                        }
                    }
                    scrollToBottom();
                    initVoiceMessages();
                }
            } catch(err) {
                console.error('Error checking messages:', err);
            }
        }
        
        function addMessageToChat(msg) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.sender == 'parent' ? 'sent' : 'received'}`;
            messageDiv.setAttribute('data-message-id', msg.id);
            
            let content = '';
            const timeStr = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
            
            if(msg.message && msg.message.includes('[Voice Note]') && msg.attachment) {
                content = `
                    <div class="voice-message" data-audio="${msg.attachment}">
                        <div class="voice-play-btn"><i class="fas fa-play"></i></div>
                        <div class="voice-wave" style="display: none;">
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>
                        <span class="voice-duration">Voice note</span>
                    </div>
                    <div class="message-time">${timeStr}</div>
                `;
            } else if(msg.message && msg.message.includes('[Image]') && msg.attachment) {
                content = `<img src="${msg.attachment}" class="message-image" onclick="showImagePreview('${msg.attachment}')">
                           <div class="message-time">${timeStr}</div>`;
            } else if(msg.message) {
                content = `${msg.message.replace(/\n/g, '<br>')}<div class="message-time">${timeStr}</div>`;
            }
            
            messageDiv.innerHTML = `<div class="message-bubble">${content}</div>`;
            messagesContainer.appendChild(messageDiv);
        }
        
        setInterval(checkNewMessages, 3000);
        initVoiceMessages();
        
        async function testMicrophone() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                console.log('Microphone working');
                stream.getTracks().forEach(track => track.stop());
            } catch(err) {
                console.log('Microphone test failed:', err);
            }
        }
        testMicrophone();
    </script>
</body>
</html>