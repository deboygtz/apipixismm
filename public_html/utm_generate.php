<?php
header("Content-Type: application/json");

// Carrega config.json
$configContent = file_get_contents("config.json");
if (!$configContent) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Erro ao carregar config.json"
    ]);
    exit;
}

$config = json_decode($configContent, true);
if (!isset($config['utmifyToken'])) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Token da UTMify não encontrado na configuração"
    ]);
    exit;
}

$utmifyToken = $config['utmifyToken'];

// Recebe dados do frontend
$input = json_decode(file_get_contents("php://input"), true);
$valorOriginal = floatval($input['valor']);  // Valor recebido

$taxaPercentual = 0.0397;
$valorComTaxa = $valorOriginal * (1 - $taxaPercentual);

// Comissão e gateway
$gatewayFeeInCents = 100;
$totalPriceInCents = intval($valorOriginal * 100);
$userCommissionInCents = $totalPriceInCents - $gatewayFeeInCents;

$data = [
    "orderId" => uniqid(),
    "platform" => "MinhaFintech",
    "paymentMethod" => "pix",
    "status" => "waiting_payment",
    "createdAt" => date("Y-m-d H:i:s"),
    "approvedDate" => null,
    "refundedAt" => null,
    "customer" => [
        "name" => $input['nome'],
        "email" => $input['email'],
        "phone" => $input['telefone'],
        "document" => $input['documento'],
        "country" => "BR",
        "ip" => $input['ip'] ?? "127.0.0.1"
    ],
    "products" => [[
        "id" => uniqid(),
        "name" => "Depósito via Pix",
        "planId" => null,
        "planName" => null,
        "quantity" => 1,
        "priceInCents" => intval($input['valor'] * 100)
    ]],
    "trackingParameters" => [
        "utm_source" => $input['utm']['utm_source'] ?? null,
        "utm_campaign" => $input['utm']['utm_campaign'] ?? null,
        "utm_medium" => $input['utm']['utm_medium'] ?? null,
        "utm_content" => $input['utm']['utm_content'] ?? null,
        "utm_term" => $input['utm']['utm_term'] ?? null
    ],
    "commission" => [
        "totalPriceInCents" => $totalPriceInCents,
        "gatewayFeeInCents" => $gatewayFeeInCents,
        "userCommissionInCents" => $totalPriceInCents
    ],
    "isTest" => false
];

// Envia para UTMify
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.utmify.com.br/api-credentials/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-token: ' . $utmifyToken
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$resposta = curl_exec($ch);
curl_close($ch);

echo json_encode([
    "status" => true,
    "mensagem" => "Depósito gerado e enviado para UTMify",
    "utmify_resposta" => json_decode($resposta, true)
]);
