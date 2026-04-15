<?php
require_once __DIR__ . '/config.php';

class TawkTo {
    private $apiKey;
    private $baseUrl = 'https://api.tawk.to/v2';

    public function __construct() {
        $this->apiKey = TAWKTO_API_KEY;
    }

    // API Request helper
    private function request($method, $endpoint, $body = []) {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode($this->apiKey . ':'),
            ],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST]       = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        } elseif ($method === 'PUT') {
            $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $options[CURLOPT_POSTFIELDS]    = json_encode($body);
        } elseif ($method === 'GET' && !empty($body)) {
            $options[CURLOPT_URL] = $url . '?' . http_build_query($body);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error, 'status' => false];
        }

        $data = json_decode($response, true);
        return $data ?? ['error' => 'Invalid JSON response', 'status' => false];
    }

    // Get all chats/conversations
    public function getChats($params = []) {
        $defaults = [
            'pageSize' => 20,
            'page'     => 1,
        ];
        return $this->request('GET', '/chats', array_merge($defaults, $params));
    }

    // Get specific chat details
    public function getChat($chatId) {
        return $this->request('GET', '/chats/' . $chatId);
    }

    // Get messages in a chat
    public function getChatMessages($chatId) {
        return $this->request('GET', '/chats/' . $chatId . '/messages');
    }

    // Reply to a chat
    public function sendMessage($chatId, $message) {
        return $this->request('POST', '/chats/' . $chatId . '/messages', [
            'type'    => 'msg',
            'message' => $message,
        ]);
    }

    // Get all tickets
    public function getTickets($params = []) {
        $defaults = ['pageSize' => 20, 'page' => 1];
        return $this->request('GET', '/tickets', array_merge($defaults, $params));
    }

    // Get single ticket
    public function getTicket($ticketId) {
        return $this->request('GET', '/tickets/' . $ticketId);
    }

    // Reply to a ticket
    public function replyToTicket($ticketId, $message) {
        return $this->request('POST', '/tickets/' . $ticketId . '/messages', [
            'type'    => 'msg',
            'message' => $message,
        ]);
    }

    // Get agents
    public function getAgents() {
        return $this->request('GET', '/agents');
    }

    // Get dashboard stats
    public function getDashboardStats() {
        $chats   = $this->getChats(['pageSize' => 100]);
        $tickets = $this->getTickets(['pageSize' => 100]);

        $totalChats   = 0;
        $openChats    = 0;
        $missedChats  = 0;

        if (isset($chats['data'])) {
            $totalChats = count($chats['data']);
            foreach ($chats['data'] as $chat) {
                if (isset($chat['status'])) {
                    if ($chat['status'] === 'open')   $openChats++;
                    if ($chat['status'] === 'missed') $missedChats++;
                }
            }
        }

        return [
            'total_chats'   => $totalChats,
            'open_chats'    => $openChats,
            'missed_chats'  => $missedChats,
            'total_tickets' => isset($tickets['data']) ? count($tickets['data']) : 0,
        ];
    }
}
