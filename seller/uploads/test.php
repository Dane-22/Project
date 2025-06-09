<?php
$test_file = 'test/test_write.txt';
if (file_put_contents($test_file, 'test') !== false) {
    echo "Directory is writable.";
    unlink($test_file); // Remove the test file after checking
} else {
    echo "Directory is not writable.";
}
?>