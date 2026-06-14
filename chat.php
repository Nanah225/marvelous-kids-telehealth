<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Marvelous Kids - Doctor Chat | Voice, Video & Camera</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(145deg, #c0e0ff 0%, #9ac4e4 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 16px;
        }

        /* Phone Frame */
        .phone {
            width: 100%;
            max-width: 390px;
            height: 780px;
            background: #ffffff;
            border-radius: 48px;
            overflow: hidden;
            box-shadow: 0 30px 45px rgba(0, 0, 0, 0.25), 0 0 0 6px #f8faff, 0 0 0 12px #8bb5d1;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Header */
        .chat-header {
            background: linear-gradient(115deg, #1f6eeb 0%, #16b3a3 100%);
            padding: 16px 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 48px 48px 24px 24px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-size: 20px;
            cursor: pointer;
        }

        .doctor-info {
            display: flex;
            flex-direction: column;
        }

        .doctor-name {
            font-weight: 700;
            font-size: 16px;
        }

        .doctor-status {
            font-size: 11px;
            opacity: 0.9;
        }

        .call-buttons {
            display: flex;
            gap: 12px;
        }

        .call-btn {
            background: rgba(255,255,255,0.2);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: 0.2s;
        }

        .call-btn:active {
            transform: scale(0.92);
            background: rgba(255,255,255,0.4);
        }

        /* Chat Area */
        .chat-area {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #f4f9fe;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chat-area::-webkit-scrollbar {
            width: 4px;
        }
        .chat-area::-webkit-scrollbar-track {
            background: #e2ecf5;
        }
        .chat-area::-webkit-scrollbar-thumb {
            background: #9ab3cf;
            border-radius: 10px;
        }

        /* Message Bubbles */
        .message {
            display: flex;
            flex-direction: column;
            max-width: 85%;
            animation: fadeSlide 0.25s ease;
        }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .parent-msg {
            align-self: flex-end;
        }

        .doctor-msg {
            align-self: flex-start;
        }

        .bubble {
            padding: 10px 14px;
            border-radius: 20px;
            font-size: 14px;
            line-height: 1.4;
            word-break: break-word;
        }

        .parent-bubble {
            background: linear-gradient(135deg, #2E8BFF, #1f6ed9);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .doctor-bubble {
            background: white;
            color: #1a2c3e;
            border: 1px solid #e2edf7;
            border-bottom-left-radius: 5px;
        }

        .message-time {
            font-size: 10px;
            color: #7f8c9a;
            margin-top: 4px;
            margin-left: 12px;
            margin-right: 12px;
        }

        /* Voice Message */
        .voice-message {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .play-btn {
            width: 32px;
            height: 32px;
            background: rgba(0,0,0,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .voice-wave {
            flex: 1;
            height: 32px;
            background: linear-gradient(90deg, #2E8BFF 0%, #2E8BFF 100%);
            background-size: 200% 100%;
            border-radius: 16px;
        }
        .voice-duration {
            font-size: 11px;
        }

        /* Image Message */
        .image-message {
            max-width: 200px;
            border-radius: 16px;
            cursor: pointer;
        }
        .image-message img {
            width: 100%;
            border-radius: 16px;
            object-fit: cover;
        }

        /* Input Area */
        .input-area {
            background: #ffffffcc;
            backdrop-filter: blur(12px);
            border-top: 1px solid #dee9f2;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 32px 32px 40px 40px;
        }

        .input-field {
            flex: 1;
            border: 1px solid #cbdde9;
            border-radius: 60px;
            padding: 12px 16px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        .input-field:focus {
            border-color: #2E8BFF;
            box-shadow: 0 0 0 3px rgba(46,139,255,0.2);
        }

        .action-btn {
            background: #eef3fc;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 40px;
            font-size: 20px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .send-btn {
            background: #2E8BFF;
            color: white;
        }

        .action-btn:active {
            transform: scale(0.92);
        }

        /* Recording Indicator */
        .recording-indicator {
            position: fixed;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            background: #ff4444;
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 12px;
            z-index: 1000;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .pulse {
            width: 10px;
            height: 10px;
            background: white;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        /* Modal for fullscreen image/video */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 3000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .modal img, .modal video {
            max-width: 90%;
            max-height: 70%;
            border-radius: 16px;
        }
        .close-modal {
            position: absolute;
            top: 40px;
            right: 30px;
            color: white;
            font-size: 32px;
            cursor: pointer;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e2f3e;
            color: white;
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 12px;
            z-index: 2000;
            opacity: 0;
            transition: 0.2s;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="phone">
    <!-- Header -->
    <div class="chat-header">
        <div class="header-left">
            <a href="dashboard.php" class="back-btn">←</a>
            <div class="doctor-info">
                <div class="doctor-name">🩺 Dr. Marvelous</div>
                <div class="doctor-status">● Online • Pediatrician</div>
            </div>
        </div>
        <div class="call-buttons">
            <div class="call-btn" id="voiceCallBtn">📞</div>
            <div class="call-btn" id="videoCallBtn">🎥</div>
        </div>
    </div>

    <!-- Chat Messages Area -->
    <div class="chat-area" id="chatArea"></div>

    <!-- Recording Indicator -->
    <div id="recordingIndicator" class="recording-indicator">
        <div class="pulse"></div>
        <span>🔴 Recording voice message...</span>
    </div>

    <!-- Input Area -->
    <div class="input-area">
        <button class="action-btn" id="cameraBtn">📷</button>
        <button class="action-btn" id="micBtn">🎤</button>
        <input type="text" id="messageInput" class="input-field" placeholder="Type a message...">
        <button class="action-btn send-btn" id="sendBtn">➤</button>
    </div>
</div>

<!-- Modal for Media Preview -->
<div id="mediaModal" class="modal">
    <span class="close-modal" onclick="closeMediaModal()">&times;</span>
    <img id="modalImage" style="display: none;">
    <video id="modalVideo" controls style="display: none;"></video>
</div>

<div id="toastMsg" class="toast"></div>

<script>
    // ======================== COMPLETE CHAT SYSTEM ========================
    // Message Store
    let messages = [];
    let mediaRecorder = null;
    let audioChunks = [];
    let isRecording = false;
    let currentStream = null;

    // Load messages from localStorage
    function loadMessages() {
        const stored = localStorage.getItem("marvelous_chat_messages");
        if (stored) {
            messages = JSON.parse(stored);
        } else {
            // Welcome messages
            messages = [
                { type: "text", content: "👋 Hello! I'm Dr. Marvelous. How is your little one feeling today?", sender: "doctor", timestamp: Date.now() - 7200000 },
                { type: "text", content: "You can send text, voice messages, or share photos. Feel free to ask anything about your child's health! 🩺", sender: "doctor", timestamp: Date.now() - 7100000 }
            ];
            saveMessages();
        }
        renderMessages();
    }

    function saveMessages() {
        localStorage.setItem("marvelous_chat_messages", JSON.stringify(messages));
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Render messages
    function renderMessages() {
        const chatArea = document.getElementById("chatArea");
        if (!chatArea) return;

        if (messages.length === 0) {
            chatArea.innerHTML = '<div style="text-align:center; margin-top:50px; color:#9aaebf;">✨ Start a conversation with Dr. Marvelous ✨</div>';
            return;
        }

        let html = "";
        for (let msg of messages) {
            const isParent = msg.sender === "parent";
            const bubbleClass = isParent ? "parent-bubble" : "doctor-bubble";
            const alignClass = isParent ? "parent-msg" : "doctor-msg";
            const senderName = isParent ? "You" : "Dr. Marvelous";

            html += `<div class="message ${alignClass}">`;
            html += `<div class="bubble ${bubbleClass}">`;

            if (msg.type === "text") {
                html += `<div>${escapeHtml(msg.content)}</div>`;
            } else if (msg.type === "voice") {
                html += `<div class="voice-message" onclick="playVoiceMessage('${msg.content}')">`;
                html += `<div class="play-btn">▶️</div>`;
                html += `<div class="voice-wave" style="width: 80px; height: 32px; background: ${isParent ? 'rgba(255,255,255,0.3)' : '#2E8BFF20'}; border-radius: 16px;"></div>`;
                html += `<div class="voice-duration">${msg.duration || '0:06'}</div>`;
                html += `</div>`;
            } else if (msg.type === "image") {
                html += `<div class="image-message" onclick="viewImage('${msg.content}')">`;
                html += `<img src="${msg.content}" alt="Shared image">`;
                html += `</div>`;
            }

            html += `</div>`;
            html += `<div class="message-time">${senderName} • ${formatTime(msg.timestamp)}</div>`;
            html += `</div>`;
        }
        chatArea.innerHTML = html;
        chatArea.scrollTop = chatArea.scrollHeight;
    }

    function escapeHtml(str) {
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    // Add message
    function addMessage(type, content, sender, duration = null) {
        const message = {
            type: type,
            content: content,
            sender: sender,
            timestamp: Date.now()
        };
        if (duration) message.duration = duration;
        messages.push(message);
        saveMessages();
        renderMessages();

        // Auto-reply from doctor if parent sent message (simulate intelligent response)
        if (sender === "parent") {
            setTimeout(() => {
                simulateDoctorReply(content);
            }, 1500);
        }
    }

    function simulateDoctorReply(userMsg) {
        const lowerMsg = userMsg.toLowerCase();
        let reply = "🧸 Thank you for sharing! Could you provide more details about the symptoms? I'm here to help guide you with the best pediatric advice.";
        
        if (lowerMsg.includes("fever") || lowerMsg.includes("temperature")) {
            reply = "🌡️ For fever: ensure hydration, monitor every 4 hours. If fever >102°F or persists >24h, please visit clinic. Paracetamol can be used as directed.";
        } else if (lowerMsg.includes("cough")) {
            reply = "🤧 For cough: honey (if >1 year), humidifier, warm fluids. Watch for breathing difficulty. Most viral coughs resolve in 5-7 days.";
        } else if (lowerMsg.includes("rash")) {
            reply = "🩹 Rashes are common. Keep area clean. If spreading rapidly or with fever, please share a photo or visit us.";
        } else if (lowerMsg.includes("thank")) {
            reply = "❤️ You're most welcome! Always here for your little one's wellness journey!";
        }
        
        addMessage("text", reply, "doctor");
    }

    // ======================== VOICE RECORDING ========================
    async function startRecording() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            currentStream = stream;
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = (event) => {
                audioChunks.push(event.data);
            };

            mediaRecorder.onstop = async () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                const audioUrl = URL.createObjectURL(audioBlob);
                const duration = "0:06"; // Simplified duration
                addMessage("voice", audioUrl, "parent", duration);
                showToast("✅ Voice message sent!");
                
                if (currentStream) {
                    currentStream.getTracks().forEach(track => track.stop());
                }
                document.getElementById("recordingIndicator").style.display = "none";
            };

            mediaRecorder.start();
            isRecording = true;
            document.getElementById("recordingIndicator").style.display = "flex";
            showToast("🔴 Recording... Click mic again to stop", 2000);
        } catch (err) {
            console.error("Microphone error:", err);
            showToast("⚠️ Microphone access denied. Please allow microphone permissions.");
        }
    }

    function stopRecording() {
        if (mediaRecorder && isRecording) {
            mediaRecorder.stop();
            isRecording = false;
        }
    }

    // Play voice message
    function playVoiceMessage(audioUrl) {
        const audio = new Audio(audioUrl);
        audio.play();
        showToast("🔊 Playing voice message");
    }

    // ======================== CAMERA & IMAGE PICKER ========================
    async function openCamera() {
        const action = confirm("Choose option:\n• OK - Take photo with camera\n• Cancel - Pick from gallery");
        
        if (action) {
            // Camera capture
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                const videoTrack = stream.getVideoTracks()[0];
                const imageCapture = new ImageCapture(videoTrack);
                const blob = await imageCapture.takePhoto();
                const imageUrl = URL.createObjectURL(blob);
                addMessage("image", imageUrl, "parent");
                showToast("📸 Photo captured and sent!");
                videoTrack.stop();
                stream.getTracks().forEach(track => track.stop());
            } catch (err) {
                showToast("⚠️ Camera access denied. Using file picker instead.");
                pickFromGallery();
            }
        } else {
            pickFromGallery();
        }
    }

    function pickFromGallery() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    addMessage("image", ev.target.result, "parent");
                    showToast("🖼️ Image sent to doctor!");
                };
                reader.readAsDataURL(file);
            }
        };
        input.click();
    }

    function viewImage(imageUrl) {
        const modal = document.getElementById("mediaModal");
        const modalImg = document.getElementById("modalImage");
        const modalVideo = document.getElementById("modalVideo");
        modalImg.style.display = "block";
        modalVideo.style.display = "none";
        modalImg.src = imageUrl;
        modal.style.display = "flex";
    }

    function closeMediaModal() {
        document.getElementById("mediaModal").style.display = "none";
    }

    // ======================== VIDEO & VOICE CALL (WebRTC Simulation) ========================
    async function startVideoCall() {
        showToast("🎥 Starting video call... (Demo - connecting to Dr. Marvelous)");
        
        // Create a modal for video call
        const callModal = document.createElement('div');
        callModal.id = "callModal";
        callModal.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:#000;z-index:4000;display:flex;flex-direction:column;align-items:center;justify-content:center;";
        
        const localVideo = document.createElement('video');
        localVideo.autoplay = true;
        localVideo.muted = true;
        localVideo.style.cssText = "width:90%;height:60%;background:#333;border-radius:20px;object-fit:cover;";
        
        const remoteVideo = document.createElement('video');
        remoteVideo.autoplay = true;
        remoteVideo.style.cssText = "width:90%;height:60%;background:#222;border-radius:20px;object-fit:cover;margin-top:10px;";
        
        const controls = document.createElement('div');
        controls.style.cssText = "position:absolute;bottom:40px;display:flex;gap:20px;";
        
        const endBtn = document.createElement('button');
        endBtn.innerText = "📞 End Call";
        endBtn.style.cssText = "background:#ff4444;color:white;border:none;padding:12px 24px;border-radius:40px;font-size:16px;cursor:pointer;";
        
        controls.appendChild(endBtn);
        callModal.appendChild(localVideo);
        callModal.appendChild(remoteVideo);
        callModal.appendChild(controls);
        document.body.appendChild(callModal);
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            localVideo.srcObject = stream;
            
            // Simulate remote stream (doctor's mock video)
            const mockStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            remoteVideo.srcObject = mockStream;
            
            showToast("📞 Call connected with Dr. Marvelous");
            
            endBtn.onclick = () => {
                stream.getTracks().forEach(track => track.stop());
                mockStream.getTracks().forEach(track => track.stop());
                callModal.remove();
                showToast("Call ended");
            };
        } catch (err) {
            showToast("⚠️ Camera/Microphone access required for video call");
            callModal.remove();
        }
    }
    
    async function startVoiceCall() {
        showToast("📞 Starting voice call... (Connecting to Dr. Marvelous)");
        
        const callModal = document.createElement('div');
        callModal.id = "callModal";
        callModal.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:#1a2a3a;z-index:4000;display:flex;flex-direction:column;align-items:center;justify-content:center;";
        
        const avatar = document.createElement('div');
        avatar.innerHTML = "🩺";
        avatar.style.cssText = "font-size:80px;margin-bottom:30px;";
        
        const status = document.createElement('div');
        status.innerText = "Connected to Dr. Marvelous";
        status.style.cssText = "color:white;font-size:18px;margin-bottom:20px;";
        
        const endBtn = document.createElement('button');
        endBtn.innerText = "📞 End Call";
        endBtn.style.cssText = "background:#ff4444;color:white;border:none;padding:12px 32px;border-radius:40px;font-size:18px;cursor:pointer;";
        
        callModal.appendChild(avatar);
        callModal.appendChild(status);
        callModal.appendChild(endBtn);
        document.body.appendChild(callModal);
        
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            endBtn.onclick = () => {
                stream.getTracks().forEach(track => track.stop());
                callModal.remove();
                showToast("Voice call ended");
            };
        } catch (err) {
            showToast("⚠️ Microphone access required");
            callModal.remove();
        }
    }

    // ======================== SEND TEXT MESSAGE ========================
    function sendTextMessage() {
        const input = document.getElementById("messageInput");
        const text = input.value.trim();
        if (text === "") return;
        
        addMessage("text", text, "parent");
        input.value = "";
    }

    // ======================== TOAST NOTIFICATION ========================
    function showToast(message, duration = 2500) {
        const toast = document.getElementById("toastMsg");
        toast.innerText = message;
        toast.style.opacity = "1";
        setTimeout(() => {
            toast.style.opacity = "0";
        }, duration);
    }

    // ======================== EVENT LISTENERS ========================
    function initEventListeners() {
        const micBtn = document.getElementById("micBtn");
        const cameraBtn = document.getElementById("cameraBtn");
        const sendBtn = document.getElementById("sendBtn");
        const messageInput = document.getElementById("messageInput");
        const voiceCallBtn = document.getElementById("voiceCallBtn");
        const videoCallBtn = document.getElementById("videoCallBtn");
        
        let recordingActive = false;
        
        micBtn.onclick = () => {
            if (!recordingActive) {
                startRecording();
                recordingActive = true;
                micBtn.style.background = "#ff4444";
                micBtn.style.color = "white";
            } else {
                stopRecording();
                recordingActive = false;
                micBtn.style.background = "#eef3fc";
                micBtn.style.color = "initial";
            }
        };
        
        cameraBtn.onclick = openCamera;
        sendBtn.onclick = sendTextMessage;
        voiceCallBtn.onclick = startVoiceCall;
        videoCallBtn.onclick = startVideoCall;
        
        messageInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") sendTextMessage();
        });
    }

    // ======================== INITIALIZATION ========================
    function init() {
        loadMessages();
        initEventListeners();
        
        // Check for media device support
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showToast("⚠️ Your browser doesn't support voice/video calls", 3000);
        } else {
            console.log("Media devices supported");
        }
    }
    
    init();
</script>
</body>
</html>