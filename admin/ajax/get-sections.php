<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
if (!is_logged_in()) { http_response_code(403); exit; }

header('Content-Type: application/json');
$class_id = sanitize_int($_GET['class_id'] ?? 0);
if (!$class_id) { echo '[]'; exit; }

$sections = get_sections_by_class($class_id);
echo json_encode($sections);
