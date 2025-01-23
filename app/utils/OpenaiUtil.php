<?php

namespace App\Utils;
use App\Services\Fetch;

class OpenaiUtil
{
    private static $apiKey;
    private static $baseUrl;
    private static $textModel;

    /**
     * Initializes the OpenAI API settings.
     * @return void
     */
    public static function init(){
        self::$apiKey = _env('OPENAI_API_KEY');
        self::$textModel = _env('OPENAI_TEXTGEN_MODEL');

        self::$baseUrl = 'https://api.openai.com/v1/';
    }

    /**
     * Generates a completion based on the provided data.
     *
     * @param array $data The data to generate a completion for.
     * @return string The generated completion.
     */
    public static function complete(array $data) :string
    {
        self::init();
        $response = Fetch::post(static::$baseUrl . "chat/completions", [
            "headers" => [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . self::$apiKey
            ],
            "body" => json_encode($data)
        ])['body'];

        $response = json_decode($response, true);
        if(array_key_exists('error', $response)){
            throw new \Exception($response['error']['message']);
        }

        return $response['choices'][0]['message']['content'];
    }

    /**
     * Generates a surveyJS form based on the provided title and description.
     *
     * @param string $title The title of the form.
     * @param string $description The description of the form.
     * @return array The generated surveyJS form.
     */
    public static function formGenerator(string $title, string $description) :array
    {
        $data = [
            "model" => 'gpt-4o',
            "messages" => [
                [
                    "role" => "system",
                    "content" => 'Your\'re a surveyJs assistant, you will only generate surveyJS forms and that\'s all you can do. All your responses will be in surveyJS JSON format.'
                ],
                [
                    "role" => "user",
                    "content" => 'The title of the form is: ' . $title
                ],
                [
                    "role" => "user",
                    "content" => 'The form properties: ' . $description
                ]
            ]
        ];

        return self::extractFormJson(self::complete($data));
    }

    /**
     * Extracts JSON from a formatted string containing JSON inside ```json code block.
     *
     * @param string $input The input string containing the JSON block.
     * @return array|null The extracted JSON string, or null if not found.
     */
    protected static function extractFormJson(string $input): ?array
    {
        $pattern = '/```json\\n(.*?)\\n```/s'; // Match JSON content inside ```json and ```
        if (preg_match($pattern, $input, $matches)) {
            $form = json_decode(stripslashes($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $form['description'] = $form['title'] ?? 'Form description';
                return $form;
            }

            throw new \Exception('Invalid Form Data');
        }

        throw new \Exception('Failed parse form data');
    }
}