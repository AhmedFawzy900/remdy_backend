<?php

namespace App\Services;

use OpenAI\Client;
use Illuminate\Support\Facades\Log;


class RemedyAIService
{
    protected $openai;

    public function __construct(Client $openai)
    {
        $this->openai = $openai;
    }

    public function getRemedyInformation($query)
    {
        try {
            $prompt = $this->buildPrompt($query);
            
            $result = $this->openai->chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a knowledgeable assistant specializing in natural remedies and health information. Always provide helpful, accurate information while reminding users to consult healthcare professionals for medical advice. Respond with structured JSON data only.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ],
                ],
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

            $response = $result->choices[0]->message->content;
            return $this->parseResponse($response);
            
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Unable to fetch remedy information at the moment.'
            ];
        }
    }

    private function buildPrompt($query)
    {
        return "Please provide comprehensive information about the remedy or health topic: '{$query}'. 

        Respond with ONLY a valid JSON object in this exact format:
        {
            \"title\": \"Clear descriptive title\",
            \"description\": \"Detailed explanation of what it is, its origins, and how it works\",
            \"ingredients\": [
                {\"name\": \"Ingredient 1\"},
                {\"name\": \"Ingredient 2\"},
                {\"name\": \"Ingredient 3\"}
            ],
            \"instructions\": [
                {\"name\": \"Step 1 instruction\"},
                {\"name\": \"Step 2 instruction\"},
                {\"name\": \"Step 3 instruction\"}
            ],
            \"benefits\": [
                {\"name\": \"Benefit 1\"},
                {\"name\": \"Benefit 2\"},
                {\"name\": \"Benefit 3\"}
            ],
            \"precautions\": [
                {\"name\": \"Precaution 1\"},
                {\"name\": \"Precaution 2\"}
            ]
        }

        Guidelines:
        - Provide 3-5 ingredients, instructions, benefits, and precautions
        - Keep each item concise (1-2 lines max)
        - Ensure the JSON is valid and properly formatted
        - If ingredients are not applicable, provide empty array
        - Always include relevant precautions for safety";
    }

    private function parseResponse($response)
    {
        try {
            // Clean the response to extract JSON
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
                $data = json_decode($jsonString, true);
                
                if (json_last_error() === JSON_ERROR_NONE && $data) {
                    return [
                        'success' => true,
                        'data' => [
                            'title' => $data['title'] ?? 'Remedy Information',
                            'description' => $data['description'] ?? '',
                            'ingredients' => $data['ingredients'] ?? [],
                            'instructions' => $data['instructions'] ?? [],
                            'benefits' => $data['benefits'] ?? [],
                            'precautions' => $data['precautions'] ?? []
                        ]
                    ];
                }
            }
            
            // Fallback if JSON parsing fails
            return [
                'success' => true,
                'data' => [
                    'title' => 'Remedy Information',
                    'description' => $this->cleanText($response),
                    'ingredients' => [],
                    'instructions' => [],
                    'benefits' => [],
                    'precautions' => []
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Response parsing error: ' . $e->getMessage());
            return [
                'success' => true,
                'data' => [
                    'title' => 'Remedy Information',
                    'description' => $this->cleanText($response),
                    'ingredients' => [],
                    'instructions' => [],
                    'benefits' => [],
                    'precautions' => []
                ]
            ];
        }
    }

    private function cleanText($text)
    {
        // Remove markdown formatting
        $text = preg_replace('/###\s*/', '', $text);
        $text = preg_replace('/\*\*/', '', $text);
        $text = preg_replace('/\*/', '', $text);
        $text = preg_replace('/`/', '', $text);
        
        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
}