<?php namespace App\Libraries;

require APPPATH.'/ThirdParty/vendor/autoload.php';
use Google\Auth\Credentials\ServiceAccountCredentials;

class FCM
{
    private $serviceAccountPath;
    private $projectId;
    private $accessToken;

    public function __construct()
    {
        $this->serviceAccountPath = APPPATH . 'Config/serviceAccountKey.json'; // Path to your service account key
        $this->projectId = 'phoneshield-cd243'; // Replace with your Firebase project ID
    }

    private function getAccessToken()
    {
        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = new ServiceAccountCredentials($scopes, $this->serviceAccountPath);
        return $credentials->fetchAuthToken()['access_token'];
    }

    public function sendNotification($token, $title, $body, $image = null)
    {
        $this->accessToken = $this->getAccessToken();

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $notificationData = [
            'title' => $title,
            'body'  => $body,
        ];

        if (!empty($image)) {
            $notificationData['image'] = $image;
        }

        $message = [
            'token' => $token,
            'notification' => $notificationData,
        ];

        $payload = ['message' => $message];

        return $this->makeCurlRequest($url, $payload);
    }

    public function sendTopicNotification($topic, $title, $body, $image = null)
    {
        $this->accessToken = $this->getAccessToken();

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $notificationData = [
            'title' => $title,
            'body'  => $body,
        ];

        if (!empty($image)) {
            $notificationData['image'] = $image;
        }

        $message = [
            'topic' => $topic,
            'notification' => $notificationData,
        ];

        $payload = ['message' => $message];

        return $this->makeCurlRequest($url, $payload);
    }

    private function makeCurlRequest($url, $payload)
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $httpCode,
            'response' => json_decode($response, true),
        ];
    }
}
