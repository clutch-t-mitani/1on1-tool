<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(['name' => '株式会社サンプル']);

        $questions = [
            'やったこと（今日の出来事・文脈）',
            'プラスの感情（嬉しかった・達成感があったこと）',
            'マイナスの感情（しんどかった・モヤモヤしたこと）',
            '本音・独り言（誰にも言えない生のログ）',
        ];

        foreach ($questions as $content) {
            Question::updateOrCreate(
                ['company_id' => $company->id, 'content' => $content],
                ['is_active' => true],
            );
        }
    }
}
