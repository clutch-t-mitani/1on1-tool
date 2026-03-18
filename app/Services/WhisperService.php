<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * OpenAI Whisper API を使って音声ファイルをテキストに変換する。
 */
final class WhisperService
{
    public function __construct(
        private readonly string $apiKey = '',
    ) {}

    /**
     * S3上の音声ファイルを取得し、Whisper API でテキストに変換して返す。
     *
     * @throws RuntimeException 変換に失敗した場合
     */
    public function transcribe(string $s3Path): string
    {
        $audioContent = Storage::disk('s3')->get($s3Path);

        if ($audioContent === null) {
            throw new RuntimeException("音声ファイルが見つかりません: {$s3Path}");
        }

        $response = Http::withToken($this->apiKey)
            ->attach('file', $audioContent, basename($s3Path))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model'    => 'whisper-1',
                'language' => 'ja',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Whisper APIの変換に失敗しました。');
        }

        return (string) $response->json('text');
    }
}
