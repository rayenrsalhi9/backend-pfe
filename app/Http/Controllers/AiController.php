<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:forum,blog,article',
            'title' => 'required|string|max:200',
            'subtitle' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
        ]);

        $type = $request->type;
        $title = $request->title;
        $subtitle = $request->subtitle;
        $description = $request->description;
        $category = $request->category;

        $systemPrompt = $this->getSystemPrompt($type);
        $userPrompt = $this->buildUserPrompt($type, $title, $subtitle, $description, $category);

        $apiKey = env('OPENROUTER_API_KEY');

        if (!$apiKey) {
            Log::error('OPENROUTER_API_KEY not set in .env');
            return response()->json(['message' => 'AI service not configured'], 500);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'openai/gpt-oss-120b:free',
                'messages' => [
                    ['role' => 'user', 'content' => $systemPrompt . "\n\n" . $userPrompt],
                ],
                'max_tokens' => 2048,
            ]);

            if ($response->failed()) {
                Log::error('OpenRouter API error: ' . $response->body());
                return response()->json(['message' => 'AI service error'], 502);
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                Log::error('OpenRouter returned empty content');
                return response()->json(['message' => 'AI returned empty content'], 502);
            }

            return response($content, 200)->header('Content-Type', 'text/plain');
        } catch (\Throwable $th) {
            Log::error('AI generation failed: ' . $th->getMessage());
            return response()->json(['message' => 'AI generation failed'], 500);
        }
    }

    private function getSystemPrompt(string $type): string
    {
        $base = 'Generate content in raw HTML only — no markdown, no code blocks, no metadata. Do not exceed 150 words. Do NOT include the title or heading — the title is already displayed separately.';

        switch ($type) {
            case 'forum':
                return $base . ' Write a forum discussion starter that is engaging and thought-provoking. Encourage readers to share opinions, react, and comment. Use an informal but respectful tone with open-ended questions. Output as HTML with heading and paragraphs.';
            case 'blog':
                return $base . ' Write a blog post that invites readers to comment and share thoughts. Use a conversational yet informative tone. Include a compelling introduction, key insights, and a conclusion that invites discussion. Output as HTML with heading and paragraphs.';
            case 'article':
                return $base . ' Write an informative, read-only article. Use a formal authoritative tone with thorough analysis. Do NOT include calls to action or questions. Output as HTML with heading and paragraphs.';
            default:
                return '';
        }
    }

    private function buildUserPrompt(string $type, string $title, ?string $subtitle, ?string $description, ?string $category): string
    {
        $parts = ["Title: $title"];

        if ($subtitle) {
            $parts[] = "Subtitle: $subtitle";
        }

        if ($description) {
            $parts[] = "Description: $description";
        }

        if ($category) {
            $parts[] = "Category: $category";
        }

        $parts[] = '';
        $parts[] = 'Write the content in HTML format based on the above information.';

        return implode("\n", $parts);
    }
}
