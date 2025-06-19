<?php

namespace App\Services;

use App\Models\EmailLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Mail\GenericMail;
use App\Mail\StockSummaryMail;
use Illuminate\Support\Facades\Auth;

class MailService
{
    /**
     * Send email using Laravel Mail.
     *
     * @param array $data [
     *     'type' => string,
     *     'to' => array|string,
     *     'cc' => array|string|null,
     *     'subject' => string,
     *     'body' => string (HTML or plain),
     * ]
     * @return bool
     * @throws ValidationException|\Exception
     */
    public function send(array $data): bool
    {
        // Normalize recipients
        $to = is_string($data['to']) ? array_map('trim', explode(',', $data['to'])) : $data['to'];
        $cc = isset($data['cc']) ? (is_string($data['cc']) ? array_map('trim', explode(',', $data['cc'])) : $data['cc']) : [];

        // Validate email addresses
        $validator = Validator::make([
            'to' => $to,
            'cc' => $cc,
            'subject' => $data['subject'] ?? '',
            'body' => $data['body'] ?? '',
        ], [
            'to.*' => 'required|email',
            'cc.*' => 'nullable|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Create email log entry
        $emailLog = EmailLog::create([
            'type' => $data['type'],
            'to' => implode(',', $to),
            'cc' => !empty($cc) ? implode(',', $cc) : null,
            'status' => 'pending',
            'sent_by' => Auth::user()->name ?? 'System',
        ]);

        try {
            if ($data['type'] === EMAIL_TYPES['STOCK_SUMMARY']) {
                $mail = new StockSummaryMail(
                    $data['branchName'],
                    $data['date'],
                    $data['filePath'],
                    $data['fileName']
                );
            } else {
                $mail = new GenericMail($data['subject'], $data['body']);
            }

            $message = Mail::to($to);
            if (!empty($cc)) {
                $message->cc($cc);
            }

            $message->send($mail);

            // Update log status to sent
            $emailLog->update([
                'status' => 'sent'
            ]);

            return true;
        } catch (\Exception $e) {
            // Update log with error
            $emailLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            Log::error('Email send failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
