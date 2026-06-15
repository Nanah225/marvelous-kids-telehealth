<?php
$dirs = ['uploads', 'uploads/attachments', 'uploads/voice_notes'];
foreach($dirs as $dir) {
    if(!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created: $dir<br>";
    } else {
        echo "Already exists: $dir<br>";
    }
}

// Check if uploads are working
echo "<hr>";
echo "<h3>Testing file upload...</h3>";
$test_file = 'uploads/test.txt';
if(file_put_contents($test_file, 'test')) {
    echo "✅ Upload directory is writable<br>";
    unlink($test_file);
} else {
    echo "❌ Upload directory is NOT writable. Please check permissions.<br>";
}

echo "<br><a href='parent-chat.php?doctor_id=" . ($_GET['doctor_id'] ?? '1') . "&doctor_name=Test'>Go to Chat</a>";
?>