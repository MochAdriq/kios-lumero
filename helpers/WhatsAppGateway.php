<?php
class WhatsAppGateway
{
    public static function sendOtp(string $phone, string $otp): array
    {
        $normalizedPhone = self::normalizePhone($phone);
        $message = sprintf(
            "Kode OTP verifikasi Member Lumero POS Anda adalah: *%s*\n\nBerlaku selama 5 menit. Jangan berikan kode ini kepada siapa pun demi keamanan akun Anda.",
            $otp
        );

        // Simpan ke session untuk kemudahan debugging saat testing lokal
        $_SESSION['debug_wa_otp'] = $otp;

        $apiUrl = app_env('WA_GATEWAY_URL', 'https://api.fonnte.com/send');
        $apiToken = app_env('WA_GATEWAY_TOKEN', '');

        if (trim($apiToken) === '') {
            error_log("[WA-DEBUG] Token kosong. OTP untuk {$normalizedPhone} adalah {$otp}");
            return [
                'success' => true,
                'mode' => 'local_debug',
                'message' => 'OTP dicatat di session debug (Token WA belum diisi di .env)'
            ];
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'target' => $normalizedPhone,
                'message' => $message,
            ]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $apiToken
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                error_log("[WA-ERROR] cURL Error: " . $err);
                return ['success' => false, 'message' => $err];
            }

            $decoded = json_decode((string)$response, true);
            if (isset($decoded['status']) && $decoded['status'] === false) {
                return ['success' => false, 'message' => $decoded['reason'] ?? 'Gagal kirim WA'];
            }

            return ['success' => true, 'mode' => 'live_api', 'message' => 'OTP terkirim via WhatsApp'];
        } catch (Throwable $e) {
            error_log("[WA-EXCEPTION] " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function normalizePhone(string $phone): string
    {
        $p = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($p, '08')) {
            $p = '62' . substr($p, 1);
        }
        return $p;
    }
}
