<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

echo json_encode([
    'success' => true,
    'message' => 'Archivo PHP funcionando correctamente',
    'post_data' => $_POST,
    'files_data' => array_keys($_FILES),
    'server_info' => [
        'php_version' => phpversion(),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ]
]);
?>