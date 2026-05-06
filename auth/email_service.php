<?php

function uiu_load_env_file(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($envPath)) {
        $loaded = true;
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        $loaded = true;
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        $val = trim($val, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }

    $loaded = true;
}

function uiu_env(string $key, string $default = ''): string
{
    uiu_load_env_file();
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return (string)$value;
}

function uiu_smtp_read($socket): string
{
    $response = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    return $response;
}

function uiu_smtp_code_ok(string $response, array $allowedCodes): bool
{
    $code = (int)substr($response, 0, 3);
    return in_array($code, $allowedCodes, true);
}

function uiu_smtp_send($socket, string $command, array $expectedCodes, string &$error): bool
{
    if (fwrite($socket, $command . "\r\n") === false) {
        $error = 'SMTP write failed.';
        return false;
    }

    $response = uiu_smtp_read($socket);
    if (!uiu_smtp_code_ok($response, $expectedCodes)) {
        $error = 'SMTP command failed: ' . trim($response);
        return false;
    }

    return true;
}

function uiu_send_smtp_mail(string $toEmail, string $subject, string $htmlBody, string $textBody, string &$error): bool
{
    $smtpHost = uiu_env('EMAIL_HOST', 'smtp.gmail.com');
    $smtpPort = (int)uiu_env('EMAIL_PORT', '587');
    $smtpUser = uiu_env('EMAIL_USER', '');
    $smtpPass = uiu_env('EMAIL_PASS', '');
    $fromName = uiu_env('EMAIL_FROM_NAME', 'UIU ScholarNet');

    if ($smtpUser === '' || $smtpPass === '') {
        $error = 'Email credentials are missing. Set EMAIL_USER and EMAIL_PASS in .env';
        return false;
    }

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ]);

    $socket = @stream_socket_client(
        'tcp://' . $smtpHost . ':' . $smtpPort,
        $errno,
        $errstr,
        20,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!$socket) {
        $error = 'SMTP connection failed: ' . $errstr;
        return false;
    }

    stream_set_timeout($socket, 20);
    $greeting = uiu_smtp_read($socket);
    if (!uiu_smtp_code_ok($greeting, [220])) {
        fclose($socket);
        $error = 'SMTP greeting failed: ' . trim($greeting);
        return false;
    }

    if (!uiu_smtp_send($socket, 'EHLO localhost', [250], $error)) {
        fclose($socket);
        return false;
    }

    if (!uiu_smtp_send($socket, 'STARTTLS', [220], $error)) {
        fclose($socket);
        return false;
    }

    $cryptoEnabled = stream_socket_enable_crypto(
        $socket,
        true,
        STREAM_CRYPTO_METHOD_TLS_CLIENT
    );
    if ($cryptoEnabled !== true) {
        fclose($socket);
        $error = 'Could not enable TLS for SMTP.';
        return false;
    }

    if (!uiu_smtp_send($socket, 'EHLO localhost', [250], $error)) {
        fclose($socket);
        return false;
    }

    if (!uiu_smtp_send($socket, 'AUTH LOGIN', [334], $error)) {
        fclose($socket);
        return false;
    }

    if (!uiu_smtp_send($socket, base64_encode($smtpUser), [334], $error)) {
        fclose($socket);
        return false;
    }

    if (!uiu_smtp_send($socket, base64_encode($smtpPass), [235], $error)) {
        fclose($socket);
        return false;
    }

    if (!uiu_smtp_send($socket, 'MAIL FROM:<' . $smtpUser . '>', [250], $error)) {
        fclose($socket);
        return false;
    }

    if (!uiu_smtp_send($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251], $error)) {
        fclose($socket);
        return false;
    }

    if (!uiu_smtp_send($socket, 'DATA', [354], $error)) {
        fclose($socket);
        return false;
    }

    $boundary = 'uiu-boundary-' . bin2hex(random_bytes(8));
    $safeSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $safeFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = [];
    $headers[] = 'From: ' . $safeFromName . ' <' . $smtpUser . '>';
    $headers[] = 'To: <' . $toEmail . '>';
    $headers[] = 'Subject: ' . $safeSubject;
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $message = implode("\r\n", $headers) . "\r\n\r\n";
    $message .= '--' . $boundary . "\r\n";
    $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $textBody . "\r\n\r\n";
    $message .= '--' . $boundary . "\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";
    $message .= '--' . $boundary . "--\r\n";

    // Dot-stuffing for SMTP DATA mode.
    $message = preg_replace('/^\./m', '..', $message);

    if (fwrite($socket, $message . "\r\n.\r\n") === false) {
        fclose($socket);
        $error = 'SMTP DATA write failed.';
        return false;
    }

    $sendResponse = uiu_smtp_read($socket);
    if (!uiu_smtp_code_ok($sendResponse, [250])) {
        fclose($socket);
        $error = 'SMTP send failed: ' . trim($sendResponse);
        return false;
    }

    uiu_smtp_send($socket, 'QUIT', [221], $error);
    fclose($socket);
    return true;
}

function uiu_send_password_reset_code(string $toEmail, string $code, int $expiresMinutes, string &$error): bool
{
    $subject = 'UIU ScholarNet Password Reset Code';
    $textBody = "UIU ScholarNet Password Reset\n\n"
        . "Your password reset code is:\n"
        . $code . "\n\n"
        . "This code will expire in " . $expiresMinutes . " minutes.\n\n"
        . "If you did not request this, you can ignore this email.";

    $spacedCode = implode(' ', str_split($code));
    $htmlBody = '<!doctype html><html><body style="margin:0;padding:0;background:#ffffff;color:#111827;font-family:Arial,sans-serif;">'
        . '<div style="max-width:640px;margin:0 auto;padding:28px 22px;">'
        . '<h2 style="margin:0 0 24px 0;font-size:36px;line-height:1.2;font-weight:700;color:#111827;">UIU ScholarNet Password Reset</h2>'
        . '<p style="margin:0 0 18px 0;font-size:22px;line-height:1.45;color:#111827;">Your password reset code is:</p>'
        . '<p style="margin:0 0 30px 0;font-size:48px;line-height:1.1;letter-spacing:10px;font-weight:700;color:#111827;">' . htmlspecialchars($spacedCode, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p style="margin:0 0 16px 0;font-size:26px;line-height:1.45;color:#111827;">This code will expire in <strong>' . $expiresMinutes . ' minutes</strong>.</p>'
        . '<p style="margin:0;font-size:26px;line-height:1.45;color:#111827;">If you did not request this, you can ignore this email.</p>'
        . '</div></body></html>';

    return uiu_send_smtp_mail($toEmail, $subject, $htmlBody, $textBody, $error);
}

