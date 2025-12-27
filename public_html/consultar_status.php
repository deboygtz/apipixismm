<?php
session_start();

// Carrega configurações do config.json
$config_file = 'config.json';
$config = [];

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config = json_decode($config_content, true);
}

// Token da API - usa do config.json
$trexpay_token = $config['token'] ?? "pk_live_mctqlsa7_f957fe6c5d8088ffe13d0ab8d6cbc9f7a5b1e176a69f51f6614f035c789651ff";
$trexpay_url = "https://app.trexpay.com.br/api/status";

// Recebe dados via POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['idTransaction'])) {
    echo json_encode([
        "status" => "error", 
        "message" => "ID da transação não informado.",
        "received" => $input
    ]);
    exit();
}

$transactionId = $input['idTransaction'];

// Log da requisição
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Recebido: " . json_encode($input) . "\n", FILE_APPEND);
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Transaction ID: $transactionId\n", FILE_APPEND);

// Prepara payload para a API do trexpay
$payload = ["idTransaction" => $transactionId];

$headers = [
    "Content-Type: application/json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $trexpay_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Log completo
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "URL: $trexpay_url\n", FILE_APPEND);
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Payload: " . json_encode($payload) . "\n", FILE_APPEND);
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Headers: " . print_r($headers, true) . "\n", FILE_APPEND);
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "HTTP Code: $http_code\n", FILE_APPEND);
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Curl Error: $curl_error\n", FILE_APPEND);
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Response: $response\n", FILE_APPEND);

if ($curl_error) {
    echo json_encode([
        "status" => "error", 
        "message" => "Erro de conexão: " . $curl_error,
        "curl_error" => $curl_error
    ]);
    exit();
}

if ($http_code !== 200) {
    echo json_encode([
        "status" => "error", 
        "message" => "Erro na requisição à API",
        "http_code" => $http_code,
        "response" => $response
    ]);
    exit();
}

$data = json_decode($response, true);

// Log da estrutura da resposta
file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Data structure: " . print_r($data, true) . "\n", FILE_APPEND);

// Mapeamento de status baseado no seu JavaScript
// Seu JS espera: "PAID_OUT" ou "WAITING_FOR_APPROVAL"
$status_map = [
    // Status da API trexpay -> Status para seu frontend
    "paid" => "PAID_OUT",
    "completed" => "PAID_OUT", 
    "approved" => "PAID_OUT",
    "success" => "PAID_OUT",
    "confirmed" => "PAID_OUT",
    
    "pending" => "WAITING_FOR_APPROVAL",
    "waiting" => "WAITING_FOR_APPROVAL",
    "open" => "WAITING_FOR_APPROVAL",
    "processing" => "WAITING_FOR_APPROVAL",
    
    "cancelled" => "CANCELLED",
    "canceled" => "CANCELLED",
    "failed" => "FAILED",
    "refused" => "FAILED",
    "expired" => "EXPIRED",
    "error" => "ERROR"
];

// Tenta encontrar o status na resposta
$original_status = null;
$mapped_status = "WAITING_FOR_APPROVAL"; // Default

// Verifica diferentes caminhos possíveis
if (isset($data['status'])) {
    $original_status = strtolower($data['status']);
} elseif (isset($data['data']['status'])) {
    $original_status = strtolower($data['data']['status']);
} elseif (isset($data['payment_status'])) {
    $original_status = strtolower($data['payment_status']);
} elseif (isset($data['transaction_status'])) {
    $original_status = strtolower($data['transaction_status']);
} elseif (isset($data['result']['status'])) {
    $original_status = strtolower($data['result']['status']);
}

// Mapeia o status
if ($original_status !== null) {
    $mapped_status = $status_map[$original_status] ?? "WAITING_FOR_APPROVAL";
}

// Se houver indicador direto de sucesso
if (isset($data['paid']) && $data['paid'] === true) {
    $mapped_status = "PAID_OUT";
} elseif (isset($data['success']) && $data['success'] === true) {
    $mapped_status = "PAID_OUT";
}

file_put_contents("status_log.txt", date("[Y-m-d H:i:s] ") . "Original status: " . ($original_status ?? 'null') . " -> Mapped: $mapped_status\n", FILE_APPEND);

// Retorna no formato que seu JavaScript espera
echo json_encode([
    "status" => $mapped_status,
    "original_status" => $original_status,
    "idTransaction" => $transactionId,
    "timestamp" => date("Y-m-d H:i:s")
]);
?>