<?php
$directories = [
    'uploads',
    'uploads/attachments', 
    'uploads/voice_notes'
];

foreach($directories as $dir) {
    if(!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created: $dir<br>";
    } else {
        echo "Exists: $dir<br>";
    }
}
echo "<br>All directories are ready!";
?><?php
$directories = [
    'uploads',
    'uploads/attachments', 
    'uploads/voice_notes'
];

foreach($directories as $dir) {
    if(!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created: $dir<br>";
    } else {
        echo "Exists: $dir<br>";
    }
}
echo "<br>All directories are ready!";
?>