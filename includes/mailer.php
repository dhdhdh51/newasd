<?php
/**
 * School ERP - Pure PHP SMTP Mailer (no Composer, no dependencies)
 * Supports SSL/TLS, STARTTLS, HTML emails
 * PHP 8.1+
 */

if (!defined('ROOT_PATH')) die('Direct access not allowed.');

class SchoolMailer
{
    private string  $host;
    private int     $port;
    private string  $username;
    private string  $password;
    private string  $encryption;   // 'tls', 'ssl', or ''
    private string  $fromEmail;
    private string  $fromName;
    private array   $recipients = [];
    private string  $subject    = '';
    private string  $body       = '';
    private string  $altBody    = '';
    private array   $errors     = [];

    public function __construct()
    {
        $this->host       = get_setting('smtp_host', 'smtp.gmail.com');
        $this->port       = (int) get_setting('smtp_port', '587');
        $this->username   = get_setting('smtp_user', '');
        $this->password   = get_setting('smtp_pass', '');
        $this->encryption = strtolower(get_setting('smtp_encryption', 'tls'));
        $this->fromEmail  = get_setting('smtp_from', 'noreply@school.com');
        $this->fromName   = get_setting('smtp_from_name', get_setting('site_name', 'School ERP'));
    }

    public function addAddress(string $email, string $name = ''): void
    {
        $this->recipients[] = ['email' => $email, 'name' => $name];
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function setBody(string $html, string $plain = ''): void
    {
        $this->body    = $html;
        $this->altBody = $plain ?: strip_tags($html);
    }

    public function send(): bool
    {
        if (empty($this->username) || empty($this->host)) {
            $this->errors[] = 'SMTP not configured.';
            return false;
        }

        foreach ($this->recipients as $to) {
            $result = $this->sendOne($to['email'], $to['name']);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function sendOne(string $toEmail, string $toName): bool
    {
        $socket = $this->connect();
        if ($socket === false) {
            return false;
        }

        try {
            $boundary = md5(uniqid((string)time(), true));
            $message  = $this->buildMessage($toEmail, $toName, $boundary);

            // EHLO
            $this->sendCommand($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $resp = $this->readResponse($socket);

            // STARTTLS upgrade
            if ($this->encryption === 'tls' && $this->port !== 465) {
                $this->sendCommand($socket, "STARTTLS");
                $this->readResponse($socket);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        throw new RuntimeException('STARTTLS failed');
                    }
                }
                $this->sendCommand($socket, "EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                $this->readResponse($socket);
            }

            // AUTH LOGIN
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->readResponse($socket);
            $this->sendCommand($socket, base64_encode($this->username));
            $this->readResponse($socket);
            $this->sendCommand($socket, base64_encode($this->password));
            $authResp = $this->readResponse($socket);
            if (!str_starts_with($authResp, '2')) {
                throw new RuntimeException('Authentication failed: ' . $authResp);
            }

            // MAIL FROM
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>");
            $this->readResponse($socket);

            // RCPT TO
            $this->sendCommand($socket, "RCPT TO:<{$toEmail}>");
            $this->readResponse($socket);

            // DATA
            $this->sendCommand($socket, "DATA");
            $this->readResponse($socket);
            fwrite($socket, $message . "\r\n.\r\n");
            $dataResp = $this->readResponse($socket);
            if (!str_starts_with($dataResp, '2')) {
                throw new RuntimeException('DATA error: ' . $dataResp);
            }

            // QUIT
            $this->sendCommand($socket, "QUIT");
            fclose($socket);
            return true;

        } catch (RuntimeException $e) {
            $this->errors[] = $e->getMessage();
            if (is_resource($socket)) {
                fclose($socket);
            }
            error_log('[SchoolMailer] ' . $e->getMessage());
            return false;
        }
    }

    private function connect(): mixed
    {
        $host    = $this->host;
        $port    = $this->port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        if ($this->encryption === 'ssl' || $port === 465) {
            $target = "ssl://{$host}:{$port}";
        } else {
            $target = "tcp://{$host}:{$port}";
        }

        $socket = @stream_socket_client(
            $target,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            $this->errors[] = "SMTP Connection failed: {$errstr} ({$errno})";
            return false;
        }

        stream_set_timeout($socket, 15);
        $this->readResponse($socket); // 220 greeting
        return $socket;
    }

    private function sendCommand(mixed $socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private function readResponse(mixed $socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return trim($response);
    }

    private function buildMessage(string $toEmail, string $toName, string $boundary): string
    {
        $toHeader = $toName
            ? '=?UTF-8?B?' . base64_encode($toName) . '?= <' . $toEmail . '>'
            : $toEmail;

        $fromHeader = $this->fromName
            ? '=?UTF-8?B?' . base64_encode($this->fromName) . '?= <' . $this->fromEmail . '>'
            : $this->fromEmail;

        $subject = '=?UTF-8?B?' . base64_encode($this->subject) . '?=';

        $headers  = "From: {$fromHeader}\r\n";
        $headers .= "To: {$toHeader}\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . time() . '.' . md5($toEmail) . "@{$this->host}>\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($this->altBody)) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($this->body)) . "\r\n";
        $body .= "--{$boundary}--";

        return $headers . "\r\n" . $body;
    }

    // -------------------------------------------------------
    // Static email templates
    // -------------------------------------------------------

    public static function emailTemplate(string $title, string $content, string $footer = ''): string
    {
        $site = get_setting('site_name', 'School ERP');
        $year = date('Y');
        $ft   = $footer ?: get_setting('footer_text', "© {$year} {$site}");
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<style>
  body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0}
  .wrapper{max-width:600px;margin:30px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.1)}
  .header{background:#0d6efd;padding:20px;text-align:center;color:#fff}
  .header h2{margin:0;font-size:22px}
  .body{padding:30px;color:#333;font-size:15px;line-height:1.6}
  .btn{display:inline-block;padding:10px 24px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;margin:10px 0}
  .otp-box{font-size:32px;letter-spacing:8px;font-weight:bold;color:#0d6efd;text-align:center;padding:20px;background:#f0f6ff;border-radius:8px;margin:20px 0}
  .footer{background:#f8f9fa;padding:15px;text-align:center;font-size:13px;color:#666}
  .divider{border:none;border-top:1px solid #eee;margin:20px 0}
</style>
</head>
<body>
<div class="wrapper">
  <div class="header"><h2>{$site}</h2></div>
  <div class="body">
    <h3>{$title}</h3>
    {$content}
  </div>
  <div class="footer">{$ft}</div>
</div>
</body></html>
HTML;
    }

    // -------------------------------------------------------
    // Pre-built senders
    // -------------------------------------------------------

    public static function sendOTP(string $email, string $name, string $otp): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $m->setSubject('Password Reset OTP - ' . get_setting('site_name', 'School ERP'));
        $content = "<p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your One-Time Password (OTP) for password reset is:</p>
            <div class='otp-box'>{$otp}</div>
            <p>This OTP is valid for <strong>" . OTP_EXPIRY_MINUTES . " minutes</strong>.</p>
            <p>If you did not request this, please ignore this email.</p>";
        $m->setBody(self::emailTemplate('Password Reset OTP', $content));
        return $m->send();
    }

    public static function sendAdmissionConfirmation(string $email, string $name, string $appId): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $m->setSubject('Admission Application Received - ' . get_setting('site_name', 'School ERP'));
        $content = "<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Thank you for submitting your admission application.</p>
            <p>Your Application ID is: <strong>{$appId}</strong></p>
            <p>You can track your application status at any time using this ID.</p>
            <hr class='divider'>
            <p>We will review your application and notify you of the decision.</p>";
        $m->setBody(self::emailTemplate('Application Received', $content));
        return $m->send();
    }

    public static function sendAdmissionStatus(string $email, string $name, string $status, string $remarks = ''): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $statusLabel = strtoupper($status);
        $m->setSubject("Admission {$statusLabel} - " . get_setting('site_name', 'School ERP'));
        $color = $status === 'approved' ? '#198754' : '#dc3545';
        $content = "<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your admission application has been <span style='color:{$color};font-weight:bold;'>{$statusLabel}</span>.</p>"
            . ($remarks ? "<p><strong>Remarks:</strong> " . htmlspecialchars($remarks) . "</p>" : '')
            . "<p>Please contact the school office for further information.</p>";
        $m->setBody(self::emailTemplate("Admission {$statusLabel}", $content));
        return $m->send();
    }

    public static function sendWelcome(string $email, string $name, string $role, string $password): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $m->setSubject('Welcome to ' . get_setting('site_name', 'School ERP'));
        $loginUrl = SITE_URL . '/auth/login.php';
        $content = "<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your account has been created on " . get_setting('site_name', 'School ERP') . ".</p>
            <p><strong>Role:</strong> " . ucfirst($role) . "<br>
            <strong>Email:</strong> " . htmlspecialchars($email) . "<br>
            <strong>Password:</strong> " . htmlspecialchars($password) . "</p>
            <a href='{$loginUrl}' class='btn'>Login Now</a>
            <p><small>Please change your password after first login.</small></p>";
        $m->setBody(self::emailTemplate('Account Created', $content));
        return $m->send();
    }

    public static function sendFeeInvoice(string $email, string $name, string $invoiceNo, float $amount, string $dueDate): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $m->setSubject('Fee Invoice ' . $invoiceNo . ' - ' . get_setting('site_name', 'School ERP'));
        $sym = get_setting('currency_symbol', '₹');
        $content = "<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>A fee invoice has been generated for your account.</p>
            <table style='width:100%;border-collapse:collapse;margin:15px 0'>
            <tr><td style='padding:8px;border:1px solid #ddd'><strong>Invoice No</strong></td><td style='padding:8px;border:1px solid #ddd'>{$invoiceNo}</td></tr>
            <tr><td style='padding:8px;border:1px solid #ddd'><strong>Amount</strong></td><td style='padding:8px;border:1px solid #ddd'>{$sym}" . number_format($amount,2) . "</td></tr>
            <tr><td style='padding:8px;border:1px solid #ddd'><strong>Due Date</strong></td><td style='padding:8px;border:1px solid #ddd'>" . format_date($dueDate) . "</td></tr>
            </table>
            <p>Please make the payment before the due date to avoid any penalty.</p>";
        $m->setBody(self::emailTemplate('Fee Invoice', $content));
        return $m->send();
    }

    public static function sendPaymentConfirmation(string $email, string $name, string $txnId, float $amount): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $m->setSubject('Payment Successful - ' . get_setting('site_name', 'School ERP'));
        $sym = get_setting('currency_symbol', '₹');
        $content = "<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Your payment has been successfully processed.</p>
            <p><strong>Transaction ID:</strong> {$txnId}<br>
            <strong>Amount Paid:</strong> {$sym}" . number_format($amount,2) . "<br>
            <strong>Date:</strong> " . date('d M Y H:i') . "</p>
            <p>Thank you for your payment!</p>";
        $m->setBody(self::emailTemplate('Payment Successful', $content));
        return $m->send();
    }

    public static function sendResultPublished(string $email, string $name, string $examName): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $m->setSubject('Result Published - ' . $examName);
        $loginUrl = SITE_URL . '/student/results.php';
        $content = "<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
            <p>Results for <strong>" . htmlspecialchars($examName) . "</strong> have been published.</p>
            <a href='{$loginUrl}' class='btn'>View Results</a>";
        $m->setBody(self::emailTemplate('Result Published', $content));
        return $m->send();
    }

    public static function sendAbsenceAlert(string $parentEmail, string $parentName, string $studentName, string $date): bool
    {
        $m = new self();
        $m->addAddress($parentEmail, $parentName);
        $m->setSubject('Absence Alert - ' . get_setting('site_name', 'School ERP'));
        $content = "<p>Dear <strong>" . htmlspecialchars($parentName) . "</strong>,</p>
            <p>Your child <strong>" . htmlspecialchars($studentName) . "</strong> was marked <span style='color:#dc3545;font-weight:bold;'>ABSENT</span> on <strong>" . format_date($date) . "</strong>.</p>
            <p>If this was unplanned, please contact the school office.</p>";
        $m->setBody(self::emailTemplate('Absence Alert', $content));
        return $m->send();
    }

    public static function sendCustomNotification(string $email, string $name, string $subject, string $message): bool
    {
        $m = new self();
        $m->addAddress($email, $name);
        $m->setSubject($subject);
        $content = "<p>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>"
            . "<div>" . nl2br(htmlspecialchars($message)) . "</div>";
        $m->setBody(self::emailTemplate($subject, $content));
        return $m->send();
    }
}
