<?php
// Simple test script to simulate unenroll POST request

$student_id = 'STU001'; // Replace with a valid student_id for testing
$class_id = 'CLASS001'; // Replace with a valid class_id to unenroll from

$url = 'http://localhost/GRC/php/unenroll_student.php';

$data = json_encode(['class_id' => $class_id, 'test_student_id' => $student_id]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nCookie: PHPSESSID=" . session_id() . "\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true,
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error making request\n";
} else {
    echo "Response:\n";
    echo $result;
}
?>
