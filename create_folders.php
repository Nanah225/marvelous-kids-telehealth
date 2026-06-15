<?php
$folders = ['uploads', 'uploads/attachments', 'uploads/voice_notes'];
foreach($folders as $folder) {
    if(!is_dir($folder)) {
        mkdir($folder, 0777, true);
        echo "Created: $folder<br>";
    } else {
        echo "Exists: $folder<br>";
    }
}
echo "<br>All folders ready!";
?>