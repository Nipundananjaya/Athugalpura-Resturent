<?php
$files = glob("*.php");
foreach($files as $file) {
    $content = file_get_contents($file);
    if(strpos($content, '`') !== false) {
        echo "$file contains backticks.\n";
    }
}
?>
