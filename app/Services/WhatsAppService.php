<?php

namespace App\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Exception;

class WhatsAppService
{
    private $client;
    private $fromNumber;

    public function __construct()
    {
        $twilioSid = config('services.twilio.sid');
        $twilioAuthToken = config('services.twilio.auth_token');
        $this->fromNumber = 'whatsapp:' . config('services.twilio.whatsapp_number');

        $this->client = new Client($twilioSid, $twilioAuthToken);
    }

    /**
     * Send a WhatsApp message
     *
     * @param string $to
     * @param string $message
     * @param array $options
     * @return array
     */
    public function sendMessage($to, $message, $options = [])
    {
        try {
            // Format the recipient number
            $formattedTo = $this->formatPhoneNumber($to);

            // Prepare message parameters
            $messageParams = [
                'from' => $this->fromNumber,
                'body' => $message
            ];

            // Add media URL if provided
            if (isset($options['media_url'])) {
                $messageParams['mediaUrl'] = [$options['media_url']];
            }

            // Send the message
            $twilioMessage = $this->client->messages->create(
                $formattedTo,
                $messageParams
            );

            return [
                'success' => true,
                'message_sid' => $twilioMessage->sid,
                'status' => $twilioMessage->status,
                'to' => $to
            ];

        } catch (TwilioException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to send WhatsApp message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number for WhatsApp
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present (assuming +91 for India)
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }

        return 'whatsapp:+' . $phone;
    }

    /**
     * Validate phone number
     *
     * @param string $phone
     * @return bool
     */
    public function validatePhoneNumber($phone)
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Check if it's a valid Indian mobile number (10 digits)
        if (strlen($phone) === 10 && preg_match('/^[6-9]\d{9}$/', $phone)) {
            return true;
        }

        // Check if it's a valid international number (10-15 digits)
        if (strlen($phone) >= 10 && strlen($phone) <= 15) {
            return true;
        }

        return false;
    }
} 