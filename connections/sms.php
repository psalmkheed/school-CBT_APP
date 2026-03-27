<?php
// connections/sms.php

/**
 * Sends an SMS using the global school_config SMS settings
 * Supports Twilio, Termii, and Africa's Talking based on DB config
 *
 * @param PDO $conn Active database connection
 * @param string $to Recipient phone number (international format e.g., 2348000000000 or +234...)
 * @param string $message The SMS text content
 * @return array ['status' => 'success'|'error', 'message' => '...']
 */
function send_school_sms($conn, $to, $message) {
    // 1. Fetch SMS settings from database
    $stmt = $conn->query("SELECT sms_provider, sms_from, sms_api_key, sms_api_secret FROM school_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config || empty($config['sms_provider']) || empty($config['sms_api_key'])) {
        return ['status' => 'error', 'message' => 'SMS Gateway is not configured fully in System Settings.'];
    }

    $provider = strtolower($config['sms_provider']);
    $sender_id = $config['sms_from'] ?: 'SCHOOLAPP';
    $api_key = $config['sms_api_key'];
    $api_secret = $config['sms_api_secret']; // Used as Account SID or Auth Token depending on provider
    
    // Normalize phone number (strip spaces, +, leading zero out)
    $phone = preg_replace('/[^0-9]/', '', $to);
    
    // Fallback normalization logic, depends on country, assuming Nigerian (234) base if starts with 0
    if (strpos($phone, '0') === 0 && strlen($phone) === 11) {
        $phone = '234' . substr($phone, 1);
    }

    try {
        if ($provider === 'twilio') {
            // Twilio uses Account SID ($api_key) and Auth Token ($api_secret)
            // https://api.twilio.com/2010-04-01/Accounts/{AccountSid}/Messages.json
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$api_key}/Messages.json";
            
            // Format phone for Twilio (requires +)
            $twilio_phone = "+$phone";

            $data = http_build_query([
                'To' => $twilio_phone,
                'From' => $sender_id,
                'Body' => $message
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_USERPWD, "{$api_key}:{$api_secret}");
            
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = json_decode($result, true);
            
            if ($httpcode >= 200 && $httpcode < 300) {
                return ['status' => 'success', 'message' => 'Twilio: Message sent.'];
            } else {
                return ['status' => 'error', 'message' => 'Twilio Error: ' . ($response['message'] ?? 'Unknown Gateway Error')];
            }

        } elseif ($provider === 'termii') {
            // Termii: https://api.ng.termii.com/api/sms/send
            // Payload: to, from, sms, type="plain", channel="generic", api_key
            $url = "https://api.ng.termii.com/api/sms/send";
            
            $data = json_encode([
                "to" => $phone,
                "from" => $sender_id,
                "sms" => $message,
                "type" => "plain",
                "channel" => "generic", // or dnd depending on setup
                "api_key" => $api_key
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = json_decode($result, true);

            if ($httpcode >= 200 && $httpcode < 300 && isset($response['message_id'])) {
                return ['status' => 'success', 'message' => 'Termii: Message sent.'];
            } else {
                return ['status' => 'error', 'message' => 'Termii Error: ' . ($response['message'] ?? 'Unknown Gateway Error')];
            }

        } elseif ($provider === 'africastalking') {
            // Africa's Talking: https://api.africastalking.com/version1/messaging
            // Sandbox URL: https://api.sandbox.africastalking.com/version1/messaging
            $url = "https://api.africastalking.com/version1/messaging";
            
            $at_phone = '+' . $phone;
            // For AT, api_key is username, api_secret is the actual AT API Key
            $username = $api_key; 
            $apikey = $api_secret; 

            $data = http_build_query([
                'username' => $username,
                'to'       => $at_phone,
                'message'  => $message,
                'from'     => $sender_id 
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'apiKey: ' . $apikey
            ]);

            $result = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = json_decode($result, true);

            if ($httpcode >= 200 && $httpcode < 300) {
                return ['status' => 'success', 'message' => 'AfricasTalking: Message sent.'];
            } else {
                return ['status' => 'error', 'message' => 'AfricasTalking Error: Failed to send via API.'];
            }

        } else {
            return ['status' => 'error', 'message' => 'Unsupported SMS Provider specified.'];
        }

    } catch (Exception $e) {
        return ['status' => 'error', 'message' => "Internal SMS Error: {$e->getMessage()}"];
    }
}
