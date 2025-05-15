<?php
// Save as filetest.php in your public folder

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>File Upload Test</h1>';

// Only process if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug PHP settings
    echo '<h2>PHP Upload Settings</h2>';
    echo '<pre>';
    echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . "\n";
    echo 'post_max_size: ' . ini_get('post_max_size') . "\n";
    echo 'max_file_uploads: ' . ini_get('max_file_uploads') . "\n";
    echo '</pre>';
    
    // Directory information
    $uploadDir = '../storage/app/uploads/test/';
    $fullPath = realpath(dirname(__FILE__) . '/../storage/app/uploads/test/');
    
    echo '<h2>Directory Information</h2>';
    echo '<pre>';
    echo 'Target Directory: ' . $uploadDir . "\n";
    echo 'Full Path: ' . $fullPath . "\n";
    echo 'Directory exists: ' . (file_exists($fullPath) ? 'Yes' : 'No') . "\n";
    echo 'Directory writable: ' . (is_writable($fullPath) ? 'Yes' : 'No') . "\n";
    echo '</pre>';
    
    // Create directory if it doesn't exist
    if (!file_exists($fullPath)) {
        echo '<p>Creating directory...</p>';
        mkdir($fullPath, 0775, true);
    }
    
    // Process uploaded file
    if (isset($_FILES['testfile']) && $_FILES['testfile']['error'] === UPLOAD_ERR_OK) {
        echo '<h2>File Information</h2>';
        echo '<pre>';
        print_r($_FILES['testfile']);
        echo '</pre>';
        
        $fileTmpPath = $_FILES['testfile']['tmp_name'];
        $fileName = $_FILES['testfile']['name'];
        $targetFilePath = $fullPath . '/' . $fileName;
        
        echo '<h2>Upload Attempt</h2>';
        $result = move_uploaded_file($fileTmpPath, $targetFilePath);
        
        if ($result) {
            echo '<p style="color: green">File uploaded successfully to: ' . $targetFilePath . '</p>';
            echo '<p>File exists: ' . (file_exists($targetFilePath) ? 'Yes' : 'No') . '</p>';
            echo '<p>File size: ' . filesize($targetFilePath) . ' bytes</p>';
        } else {
            echo '<p style="color: red">Upload failed!</p>';
            echo '<p>Last error: ' . error_get_last()['message'] . '</p>';
        }
    } else if (isset($_FILES['testfile'])) {
        echo '<h2>Upload Error</h2>';
        echo '<p>Error code: ' . $_FILES['testfile']['error'] . '</p>';
        
        // Translate error code
        $uploadErrors = [
            UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        
        echo '<p>Error explanation: ' . $uploadErrors[$_FILES['testfile']['error']] . '</p>';
    }
}
?>

<form action="" method="post" enctype="multipart/form-data">
    <p>
        <label for="testfile">Select a file:</label>
        <input type="file" name="testfile" id="testfile">
    </p>
    <p>
        <input type="submit" value="Upload">
    </p>
</form>

<h2>Server Environment</h2>
<pre>
<?php
echo 'PHP Version: ' . phpversion() . "\n";
echo 'Server Software: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo 'User: ' . exec('whoami') . "\n";
echo 'Temp Directory: ' . sys_get_temp_dir() . "\n";
echo 'Disk Free Space: ' . disk_free_space('/') . ' bytes' . "\n";
?>
</pre>