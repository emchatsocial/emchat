<?php
declare(strict_types=1);

namespace App;

/**
 * Minimal mailer: Resend HTTPS API, SMTP (SSL/STARTTLS), PHP mail(), or log-to-file.
 * No external dependencies — safe for Namecheap shared hosting.
 */
final class Mailer
{
    public string $lastError = '';

    public function __construct(private array $cfg, private string $storagePath) {}

    public function send(string $toEmail, string $subject, string $html, string $text = ''): bool
    {
        $text = $text !== '' ? $text : trim(html_entity_decode(strip_tags($html)));
        $driver = $this->cfg['driver'] ?? 'log';

        return match ($driver) {
            'resend' => $this->sendResend($toEmail, $subject, $html, $text),
            'smtp'   => $this->sendSmtp($toEmail, $subject, $html, $text),
            'mail'   => $this->sendMail($toEmail, $subject, $html, $text),
            default  => $this->sendLog($toEmail, $subject, $html, $text),
        };
    }

    private function sendResend(string $to, string $subject, string $html, string $text): bool
    {
        $key = trim((string) ($this->cfg['resend']['api_key'] ?? ''));
        if ($key === '') {
            $this->lastError = 'RESEND_API_KEY is not set.';
            error_log('EMChat mail: ' . $this->lastError);
            return $this->sendLog($to, $subject, $html, $text);
        }

        $fromEmail = $this->cfg['from_email'] ?? 'no-reply@localhost';
        $fromName  = $this->cfg['from_name'] ?? 'EMChat Media';
        $payload = json_encode([
            'from'    => sprintf('%s <%s>', $fromName, $fromEmail),
            'to'      => [$to],
            'subject' => $subject,
            'html'    => $html,
            'text'    => $text,
        ], JSON_UNESCAPED_SLASHES);

        [$status, $body] = $this->httpsPostJson('https://api.resend.com/emails', $payload, [
            'Authorization: Bearer ' . $key,
        ]);

        if ($status >= 200 && $status < 300) {
            $this->lastError = '';
            return true;
        }

        $this->lastError = 'Resend API returned HTTP ' . $status . ': ' . trim((string) $body);
        error_log('EMChat mail: ' . $this->lastError);
        // Keep a copy locally so nothing is silently lost.
        $this->sendLog($to, $subject, $html, $text);
        return false;
    }

    /**
     * @param string[] $headers
     * @return array{0:int,1:string}  [httpStatus, responseBody]
     */
    private function httpsPostJson(string $url, string $json, array $headers): array
    {
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_USERAGENT      => 'EMChatMedia/1.0',
            ]);
            $res = curl_exec($ch);
            if ($res === false) {
                $err = curl_error($ch);
                curl_close($ch);
                return [0, 'curl error: ' . $err];
            }
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$status, (string) $res];
        }

        // Fallback for hosts without curl.
        $ctx = stream_context_create(['http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", $headers),
            'content'       => $json,
            'timeout'       => 15,
            'ignore_errors' => true,
        ], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $res = @file_get_contents($url, false, $ctx);
        $status = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $h, $m)) {
                $status = (int) $m[1];
            }
        }
        return [$status, (string) $res];
    }

    private function headers(string $subject): array
    {
        $fromEmail = $this->cfg['from_email'] ?? 'no-reply@localhost';
        $fromName  = $this->cfg['from_name'] ?? 'EMChat Media';
        return [
            'from_email' => $fromEmail,
            'from_name'  => $fromName,
            'subject'    => $subject,
            'date'       => date('r'),
            'message_id' => sprintf('<%s@%s>', bin2hex(random_bytes(12)), $this->hostFromEmail($fromEmail)),
        ];
    }

    private function hostFromEmail(string $email): string
    {
        return substr(strrchr($email, '@') ?: '@localhost', 1);
    }

    private function mimeBody(array $h, string $html, string $text): string
    {
        $boundary = 'emc_' . bin2hex(random_bytes(8));
        $lines = [];
        $lines[] = 'From: ' . $this->encodeName($h['from_name']) . ' <' . $h['from_email'] . '>';
        $lines[] = 'Date: ' . $h['date'];
        $lines[] = 'Message-ID: ' . $h['message_id'];
        $lines[] = 'MIME-Version: 1.0';
        $lines[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $lines[] = '';
        $lines[] = '--' . $boundary;
        $lines[] = 'Content-Type: text/plain; charset=UTF-8';
        $lines[] = 'Content-Transfer-Encoding: base64';
        $lines[] = '';
        $lines[] = chunk_split(base64_encode($text));
        $lines[] = '--' . $boundary;
        $lines[] = 'Content-Type: text/html; charset=UTF-8';
        $lines[] = 'Content-Transfer-Encoding: base64';
        $lines[] = '';
        $lines[] = chunk_split(base64_encode($html));
        $lines[] = '--' . $boundary . '--';
        return implode("\r\n", $lines);
    }

    private function encodeName(string $name): string
    {
        return preg_match('/[^\x20-\x7e]/', $name)
            ? '=?UTF-8?B?' . base64_encode($name) . '?='
            : '"' . str_replace('"', '', $name) . '"';
    }

    private function sendLog(string $to, string $subject, string $html, string $text): bool
    {
        $dir = $this->storagePath . '/mail';
        @mkdir($dir, 0775, true);
        $h = $this->headers($subject);
        $eml = "To: {$to}\r\nSubject: {$subject}\r\n" . $this->mimeBody($h, $html, $text);
        file_put_contents($dir . '/' . date('Ymd-His') . '-' . substr(md5($to . microtime()), 0, 8) . '.eml', $eml);
        return true;
    }

    private function sendMail(string $to, string $subject, string $html, string $text): bool
    {
        $h = $this->headers($subject);
        $body = $this->mimeBody($h, $html, $text);
        // Split first blank line: everything before it are headers for mail().
        [$headerBlock, $bodyBlock] = explode("\r\n\r\n", $body, 2);
        return @mail(
            $to,
            '=?UTF-8?B?' . base64_encode($subject) . '?=',
            $bodyBlock,
            $headerBlock,
            '-f' . ($this->cfg['from_email'] ?? '')
        );
    }

    private function sendSmtp(string $to, string $subject, string $html, string $text): bool
    {
        $s = $this->cfg['smtp'] ?? [];
        $host = $s['host'] ?? 'localhost';
        $port = (int) ($s['port'] ?? 587);
        $enc  = $s['encryption'] ?? 'tls';
        $transport = $enc === 'ssl' ? "ssl://{$host}" : $host;

        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = @stream_socket_client("{$transport}:{$port}", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            error_log("EMChat SMTP connect failed: {$errstr} ({$errno})");
            return $this->sendLog($to, $subject, $html, $text);
        }
        stream_set_timeout($fp, 20);

        $read = function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $data;
        };
        $cmd = function (string $c) use ($fp, $read): string {
            fwrite($fp, $c . "\r\n");
            return $read();
        };

        try {
            $read();
            $ehloHost = $this->hostFromEmail($this->cfg['from_email'] ?? 'localhost');
            $cmd("EHLO {$ehloHost}");
            if ($enc === 'tls') {
                $cmd('STARTTLS');
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                    throw new \RuntimeException('STARTTLS failed');
                }
                $cmd("EHLO {$ehloHost}");
            }
            if (!empty($s['username'])) {
                $cmd('AUTH LOGIN');
                $cmd(base64_encode((string) $s['username']));
                $auth = $cmd(base64_encode((string) ($s['password'] ?? '')));
                if (strncmp($auth, '235', 3) !== 0) {
                    throw new \RuntimeException('SMTP auth rejected: ' . trim($auth));
                }
            }
            $from = $this->cfg['from_email'] ?? 'no-reply@localhost';
            $cmd("MAIL FROM:<{$from}>");
            $cmd("RCPT TO:<{$to}>");
            $data = $cmd('DATA');
            if (strncmp($data, '354', 3) !== 0) {
                throw new \RuntimeException('SMTP DATA rejected: ' . trim($data));
            }
            $h = $this->headers($subject);
            $payload = "To: {$to}\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
                . $this->mimeBody($h, $html, $text);
            $payload = preg_replace('/^\./m', '..', $payload);
            $done = $cmd($payload . "\r\n.");
            $cmd('QUIT');
            fclose($fp);
            return strncmp($done, '250', 3) === 0;
        } catch (\Throwable $ex) {
            @fclose($fp);
            error_log('EMChat SMTP error: ' . $ex->getMessage());
            return $this->sendLog($to, $subject, $html, $text);
        }
    }
}
