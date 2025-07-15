<?php
class FCMService
{
    private static $FCM_URL = 'https://fcm.googleapis.com/fcm/send';
    private static $SERVER_KEY = 'YOUR_FCM_SERVER_KEY_HERE'; // Firebase Console'dan alınacak

    /**
     * Tek bir cihaza bildirim gönder
     */
    public static function sendNotification($fcmToken, $title, $body, $data = null, $imageUrl = null)
    {
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => 1
        ];

        if ($imageUrl) {
            $notification['image'] = $imageUrl;
        }

        $fields = [
            'to' => $fcmToken,
            'notification' => $notification,
            'priority' => 'high'
        ];

        if ($data) {
            $fields['data'] = $data;
        }

        return self::sendRequest($fields);
    }

    /**
     * Birden fazla cihaza bildirim gönder
     */
    public static function sendMultipleNotifications($fcmTokens, $title, $body, $data = null)
    {
        if (empty($fcmTokens)) {
            return false;
        }

        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => 1
        ];

        $fields = [
            'registration_ids' => $fcmTokens,
            'notification' => $notification,
            'priority' => 'high'
        ];

        if ($data) {
            $fields['data'] = $data;
        }

        return self::sendRequest($fields);
    }

    /**
     * Topic'e bildirim gönder
     */
    public static function sendToTopic($topic, $title, $body, $data = null)
    {
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => 1
        ];

        $fields = [
            'to' => '/topics/' . $topic,
            'notification' => $notification,
            'priority' => 'high'
        ];

        if ($data) {
            $fields['data'] = $data;
        }

        return self::sendRequest($fields);
    }

    /**
     * FCM API'ye istek gönder
     */
    private static function sendRequest($fields)
    {
        $headers = [
            'Authorization: key=' . self::$SERVER_KEY,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$FCM_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $response = json_decode($result, true);

            // Başarılı gönderim kontrolü
            if (isset($response['success']) && $response['success'] > 0) {
                return [
                    'success' => true,
                    'message' => 'Bildirim başarıyla gönderildi',
                    'response' => $response
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Bildirim gönderilemedi',
                    'response' => $response
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'FCM API hatası: HTTP ' . $httpCode,
                'response' => $result
            ];
        }
    }

    /**
     * Kampanya bildirimi gönder (resim ve aksiyon URL'i ile)
     */
    public static function sendCampaignNotification($fcmToken, $title, $body, $imageUrl = null, $actionUrl = null)
    {
        $data = [];

        if ($actionUrl) {
            $data['action_url'] = $actionUrl;
            $data['click_action'] = 'OPEN_URL';
        }

        return self::sendNotification($fcmToken, $title, $body, $data, $imageUrl);
    }

    /**
     * Hatırlatma bildirimi gönder
     */
    public static function sendReminderNotification($fcmToken, $title, $body, $reminderType, $vehicleId)
    {
        $data = [
            'notification_type' => 'reminder',
            'reminder_type' => $reminderType,
            'vehicle_id' => $vehicleId,
            'click_action' => 'OPEN_REMINDER'
        ];

        return self::sendNotification($fcmToken, $title, $body, $data);
    }
}
