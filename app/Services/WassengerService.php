<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WassengerService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('WASSENGER_API_KEY');
    }

    // Send a message to a contact using Wassenger
    public function sendMessage($to, $message)
    {
        $response = Http::withToken($this->apiKey)->post('https://api.wassenger.com/v1/messages', [
            'phone' => $to,
            'message' => $message,
        ]);

        if ($response->failed()) {
            Log::error("Failed to send message to $to: " . $response->body());
        }

        return $response->json();
    }

    // Handle incoming messages and generate an AI response
    public function handleIncomingMessage($request)
    {
        $messageText = $request['message']['body'];
        $from = $request['contact']['phone'];

        // Determine conversation step and generate a response
        if ($this->isGreeting($messageText)) {
            return $this->sendMessage($from, "Hey there! I’m Sam from SupperSocial. Looking for a restaurant, bar, day club, spa, or gym in Bali? Let me know if you have a spot in mind, or I can find something amazing for you!");

        } elseif ($this->isPersonalQuestion($messageText)) {
            return $this->sendMessage($from, "Quick question—how did you find out about me? Always curious to know!");

        } elseif ($this->isGatheringPreferences($messageText)) {
            return $this->sendMessage($from, "What kind of vibe are you after? Any specific cuisine, a SupperSocial deal, or just something new and exciting?");

        } elseif ($this->requiresAIResponse($messageText)) {
            // If the message requires a dynamic response, use OpenAI
            $reply = $this->getAIResponse($messageText);
            return $this->sendMessage($from, $reply);
        }

        // Default response if the message doesn't match any flow
        return $this->sendMessage($from, "I'm here to help! Could you provide a bit more detail on what you're looking for?");
    }

    // Functions to determine conversation stages
    protected function isGreeting($messageText)
    {
        return strpos(strtolower($messageText), 'hello') !== false ||
            strpos(strtolower($messageText), 'hi') !== false;
    }

    protected function isPersonalQuestion($messageText)
    {
        return strpos(strtolower($messageText), 'how did you find') !== false;
    }

    protected function isGatheringPreferences($messageText)
    {
        return strpos(strtolower($messageText), 'vibe') !== false ||
            strpos(strtolower($messageText), 'type of place') !== false;
    }

    protected function requiresAIResponse($messageText)
    {
        // Use AI for responses that need dynamic, contextual replies
        return true;
    }

    // Get an AI response from OpenAI API
    protected function getAIResponse($text)
    {
        $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant named SAM, a dining concierge.'],
                ['role' => 'user', 'content' => $text],
            ],
        ]);

        if ($response->failed()) {
            Log::error("Failed to get AI response: " . $response->body());
            return "Sorry, I'm having trouble getting a response. Please try again later!";
        }

        return $response->json('choices')[0]['message']['content'];
    }
}
