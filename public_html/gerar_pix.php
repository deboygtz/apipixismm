<?php
header("Content-Type: application/json");

class PixGenerator {
    private $apiUrl = "https://app.trexpay.com.br/api/wallet/deposit/payment";
    private $token = "token";
    private $secret = "secret";
    private $postback = "https://seusite.com/callback";

    public function gerarPix($valor) {
        $dados = [
            "token" => $this->token,
            "secret" => $this->secret,
            "postback" => $this->postback,
            "amount" => floatval($valor),
            "debtor_name" => "Cliente Pix",
            "email" => "cliente@email.com",
            "debtor_document_number" => "12345678900",
            "phone" => "11999999999",
            "method_pay" => "pix"
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($dados),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Accept: application/json"
            ]
        ]);

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ["success" => false, "response" => $resposta];
        }

        $json = json_decode($resposta, true);

        return [
            "success" => true,
            "idTransaction" => $json["idTransaction"] ?? null,
            "pixPayload" => $json["qrcode"] ?? null,
            "pixImage" => $json["qr_code_image_url"] ?? null
        ];
    }
}

/* RECEBE VALOR */
if (!isset($_GET["valor"])) {
    echo json_encode(["success" => false, "error" => "Valor não informado"]);
    exit;
}

$valor = str_replace(",", ".", $_GET["valor"]);

if (!is_numeric($valor)) {
    echo json_encode(["success" => false, "error" => "Valor inválido"]);
    exit;
}

$pix = new PixGenerator();
echo json_encode($pix->gerarPix($valor));
