<?php
// run this once to create directories
$directories = ['uploads', 'uploads/attachments', 'uploads/voice_notes'];
foreach($directories as $dir) {
    if(!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir<br>";
    }
}
echo "All directories created successfully!";
?>