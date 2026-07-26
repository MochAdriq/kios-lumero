<?php
/**
 * SIM Resto Sempurna - Midtrans Payment Gateway Helper Service
 * Mendukung Sandbox maupun Production berdasarkan .env
 */
class MidtransService
{
    private static ?array $dbConfig = null;
    
    private static function getDbConfig(): array
    {
        if (self::$dbConfig !== null) return self::$dbConfig;
        self::$dbConfig = [];
        try {
            if (class_exists('Database')) {
                $pdo = Database::connection();
                $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
                $stmt = $pdo->prepare("SELECT * FROM payment_gateway_configs WHERE provider = 'midtrans' AND outlet_id = ? AND is_active = 1 LIMIT 1");
                $stmt->execute([$outletId]);
                if ($row = $stmt->fetch()) {
                    self::$dbConfig = $row;
                }
            }
        } catch (Throwable $e) {}
        return self::$dbConfig;
    }

    public static function isProduction(): bool
    {
        $db = self::getDbConfig();
        if (!empty($db['mode'])) {
            return $db['mode'] === 'production';
        }
        return app_bool(app_env('MIDTRANS_IS_PRODUCTION', 'false'));
    }

    public static function getServerKey(): string
    {
        $db = self::getDbConfig();
        if (!empty($db['client_secret'])) return trim($db['client_secret']);
        return trim((string)app_env('MIDTRANS_SERVER_KEY', ''));
    }

    public static function getClientKey(): string
    {
        $db = self::getDbConfig();
        if (!empty($db['client_id'])) return trim($db['client_id']);
        return trim((string)app_env('MIDTRANS_CLIENT_KEY', ''));
    }

    public static function getMerchantId(): string
    {
        $db = self::getDbConfig();
        if (!empty($db['merchant_id'])) return trim($db['merchant_id']);
        return trim((string)app_env('MIDTRANS_MERCHANT_ID', ''));
    }

    public static function snapApiUrl(): string
    {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    public static function snapJsUrl(): string
    {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * Meminta Snap Token ke API Midtrans Snap
     * @throws RuntimeException
     */
    public static function createSnapToken(array $orderData): array
    {
        $serverKey = self::getServerKey();
        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi di .env');
        }

        $url = self::snapApiUrl();

        $paymentMethod = strtolower((string)($orderData['payment_method'] ?? ''));
        $enabledPayments = $orderData['enabled_payments'] ?? null;
        if (!is_array($enabledPayments)) {
            if ($paymentMethod === 'qris') {
                $enabledPayments = ['qris', 'gopay', 'shopeepay'];
            } elseif ($paymentMethod === 'ewallet') {
                $enabledPayments = ['gopay', 'shopeepay', 'qris'];
            } elseif (in_array($paymentMethod, ['debit', 'credit', 'card'], true)) {
                $enabledPayments = ['credit_card'];
            } elseif ($paymentMethod === 'bank_transfer') {
                $enabledPayments = ['bca_va', 'bni_va', 'bri_va', 'permata_va', 'other_va'];
            }
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderData['order_number'] . '-' . time(), // unique order ID di Midtrans
                'gross_amount' => (int)round((float)$orderData['grand_total']),
            ],
            'customer_details' => [
                'first_name' => $orderData['customer_name'] ?? 'Pelanggan SIM Resto',
                'email' => $orderData['customer_email'] ?? 'customer@simresto.local',
            ],
            'item_details' => $orderData['items'] ?? [],
        ];

        if (!empty($enabledPayments)) {
            $payload['enabled_payments'] = $enabledPayments;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new RuntimeException('Koneksi ke server Midtrans gagal: ' . $err);
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded) || empty($decoded['token'])) {
            $msg = $decoded['error_messages'][0] ?? ($decoded['message'] ?? 'Gagal membuat Snap Token Midtrans.');
            throw new RuntimeException('Midtrans Error: ' . $msg);
        }

        return [
            'token' => $decoded['token'],
            'redirect_url' => $decoded['redirect_url'] ?? '',
        ];
    }

    /**
     * Verifikasi Signature Key SHA512 dari Webhook Notifikasi Midtrans
     */
    public static function verifySignature(array $payload): bool
    {
        $serverKey = self::getServerKey();
        if ($serverKey === '' || empty($payload['signature_key'])) {
            return false;
        }

        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        return hash_equals($expectedSignature, (string)$payload['signature_key']);
    }

    /**
     * Konversi transaction_status Midtrans ke status internal tabel payments
     */
    public static function mapStatus(string $transactionStatus, string $fraudStatus = 'accept'): string
    {
        if ($transactionStatus === 'capture') {
            return ($fraudStatus === 'challenge') ? 'challenge' : 'paid';
        }
        if ($transactionStatus === 'settlement') {
            return 'paid';
        }
        if (in_array($transactionStatus, ['pending'], true)) {
            return 'pending';
        }
        if (in_array($transactionStatus, ['deny', 'cancel', 'expire'], true)) {
            return 'failed';
        }
        return $transactionStatus;
    }

    /**
     * Meminta pembuatan transaksi QRIS langsung ke Midtrans Core API (/v2/charge)
     * Mengembalikan URL gambar QR Code siap scan tanpa Snap popup
     */
    public static function createQrisCharge(array $orderData): array
    {
        $serverKey = self::getServerKey();
        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi di .env');
        }

        $url = self::isProduction()
            ? 'https://api.midtrans.com/v2/charge'
            : 'https://api.sandbox.midtrans.com/v2/charge';

        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderData['order_number'] . '-' . time(),
                'gross_amount' => (int)round((float)$orderData['grand_total']),
            ],
            'customer_details' => [
                'first_name' => $orderData['customer_name'] ?? 'Pelanggan SIM Resto',
                'email' => $orderData['customer_email'] ?? 'customer@simresto.local',
            ],
            'qris' => [
                'acquirer' => 'gopay'
            ],
            'item_details' => $orderData['items'] ?? [],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new RuntimeException('Koneksi ke server Midtrans gagal: ' . $err);
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded) || !in_array((string)($decoded['status_code'] ?? ''), ['200','201'], true)) {
            $msg = $decoded['status_message'] ?? ($decoded['message'] ?? 'Gagal membuat tagihan QRIS Midtrans.');
            throw new RuntimeException('Midtrans Core API Error: ' . $msg);
        }

        $qrUrl = '';
        if (!empty($decoded['actions']) && is_array($decoded['actions'])) {
            foreach ($decoded['actions'] as $act) {
                if (($act['name'] ?? '') === 'generate-qr-code') {
                    $qrUrl = $act['url'];
                    break;
                }
            }
        }

        return [
            'qr_url' => $qrUrl,
            'qr_string' => $decoded['qr_string'] ?? '',
            'transaction_id' => $decoded['transaction_id'] ?? '',
            'order_id' => $payload['transaction_details']['order_id'],
            'gross_amount' => $payload['transaction_details']['gross_amount'],
        ];
    }
}
