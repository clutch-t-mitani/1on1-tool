<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OpenAI GPT-4o を使って感情ログの要約を生成する。
 */
final class GptSummaryService
{
    private const MODEL = 'gpt-4o';

    private const SYSTEM_PROMPT = <<<'PROMPT'
あなたは部下の専属エージェント（通訳者）です。
以下のルールを厳守してください。

- 部下の感情・本音を全肯定する立場で解釈する
- 上司が「改善のヒント」として受け取れる前向きな表現に変換する
- 主語を「個人」ではなく「仕組みや環境」に置き換える
- 感情の傾向・継続テーマ・温度感の変化を3〜5点でまとめる
- 1点あたり100〜200字程度で記述する
- 評価・判断・アドバイスは行わない
PROMPT;

    public function __construct(
        private readonly string $apiKey = '',
    ) {}

    /**
     * @param array<int, array{question: string, answer: string, date: string}> $logs
     * @return string JSON文字列（{"points": [{"theme": string, "content": string}]}）
     * @throws RuntimeException 要約生成に失敗した場合
     */
    public function summarize(array $logs): string
    {
        $logLines = collect($logs)
            ->map(fn (array $log): string => "{$log['date']}: {$log['question']} → {$log['answer']}")
            ->implode("\n");

        $userPrompt = "以下は過去{$this->countDays($logs)}日間の日々の記録です。\n\n{$logLines}\n\nこの内容を元に、感情の傾向を要約してください。";

        $response = Http::withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => self::MODEL,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('GPT-4o による要約生成に失敗しました。');
        }

        return (string) $response->json('choices.0.message.content');
    }

    /**
     * @param array<int, array{date: string}> $logs
     */
    private function countDays(array $logs): int
    {
        return collect($logs)->pluck('date')->unique()->count();
    }
}
