<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header untuk CORS dan JSON response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../../config/database.php';
// Pastikan environment variables termuat
require_once __DIR__ . '/../../../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Baca input JSON
        $input = json_decode(file_get_contents('php://input'), true);

        $pertanyaan = $input['pertanyaan'] ?? '';

        if (empty($pertanyaan)) {
            throw new Exception('Pertanyaan tidak boleh kosong');
        }

        // Ambil konfigurasi dari environment
        $apiKey = $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY') ?? '';
        $model = $_ENV['OPENAI_MODEL'] ?? getenv('OPENAI_MODEL') ?? 'gpt-3.5-turbo';
        if (!$apiKey) {
            throw new Exception('OPENAI_API_KEY tidak terkonfigurasi');
        }

        // Siapkan data untuk dikirim ke OpenAI API
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah seorang dokter spesialis obgin berpengalaman. Berikan jawaban edukasi yang komprehensif, dan mudah dipahami oleh pasien dalam bahasa Indonesia, menggunakan format markdown. POV edukasi yang diberikan dari dokter Spesialis obgin ke pasiennya. Format jawaban dengan struktur berikut: Judul/Pengantar singkat (langsung, tanpa tulisan "judul"), 1) Penjelasan utama dengan poin-poin jika diperlukan, 2) Kesimpulan singkat. Fokus pada topik kesehatan reproduksi, kehamilan, persalinan, dan perawatan pasca persalinan.'
                ],
                [
                    'role' => 'user',
                    'content' => $pertanyaan
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7
        ];

        // Kirim request ke OpenAI API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Curl error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $errorResponse = json_decode($response, true);
            throw new Exception('OpenAI API error: ' . ($errorResponse['error']['message'] ?? 'Unknown error'));
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenAI API');
        }

        $jawaban = $responseData['choices'][0]['message']['content'];

        echo json_encode([
            'success' => true,
            'jawaban' => $jawaban
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method tidak diizinkan'
    ]);
    exit;
}
