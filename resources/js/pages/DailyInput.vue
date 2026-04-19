<template>
    <AppLayout>
        <!-- 確認画面 -->
        <div v-if="isConfirmStep">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-slate-800">入力内容の確認</h1>
                <p class="mt-1 text-sm text-slate-500">送信後は内容を再確認できません。よく確認してから送信してください。</p>
            </div>

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div v-for="(question, index) in questions" :key="question.id" class="border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                    <p class="mb-1 text-xs font-medium text-slate-400">質問 {{ index + 1 }}</p>
                    <p class="mb-2 text-sm font-semibold text-slate-700">{{ question.text }}</p>
                    <div v-if="answers[index].mode === 'voice' && answers[index].audioBlob" class="flex items-center gap-2 rounded-lg bg-sky-50 px-3 py-2">
                        <svg class="h-4 w-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                        </svg>
                        <span class="text-sm text-sky-600">音声入力済み</span>
                    </div>
                    <p v-else-if="answers[index].text" class="whitespace-pre-wrap text-sm text-slate-600">{{ answers[index].text }}</p>
                    <p v-else class="text-sm text-slate-400">（未入力）</p>
                </div>
            </div>

            <div class="mt-6 flex justify-between">
                <button type="button" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50" @click="isConfirmStep = false">
                    戻る
                </button>
                <button type="button" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700" @click="handleSubmit">
                    送信する
                </button>
            </div>
        </div>

        <!-- ローディング -->
        <div v-else-if="isLoadingQuestions" class="flex flex-col items-center gap-4 py-16">
            <div class="h-10 w-10 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-500"></div>
            <p class="text-sm text-slate-400">質問を読み込んでいます…</p>
        </div>

        <!-- 質問取得エラー -->
        <div v-else-if="questionsError" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center">
            <p class="text-sm font-medium text-rose-600">{{ questionsError }}</p>
        </div>

        <!-- 質問なし -->
        <div v-else-if="questions.length === 0" class="rounded-2xl border border-slate-200 bg-white p-6 text-center">
            <p class="text-sm text-slate-500">質問が登録されていません。管理者に連絡してください。</p>
        </div>

        <!-- ステップ入力フォーム -->
        <div v-else>
            <!-- プログレス表示 -->
            <div class="mb-6">
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">今日の気持ちを記録する</h1>
                        <p class="mt-1 inline-block rounded-full bg-indigo-50 px-3 py-0.5 text-sm font-medium text-indigo-600">{{ today }}</p>
                    </div>
                    <span class="text-sm text-slate-400">{{ currentStep + 1 }} / {{ questions.length }}</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-indigo-500 transition-all duration-300"
                        :style="{ width: `${((currentStep + 1) / questions.length) * 100}%` }"
                    ></div>
                </div>
            </div>

            <!-- 質問カード -->
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-indigo-400">質問 {{ currentStep + 1 }}</p>
                <h2 class="mb-5 text-base font-semibold text-slate-800">{{ currentQuestion.text }}</h2>

                <!-- テキスト / 音声 切り替え（録音中はロック） -->
                <div class="mb-4 flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition disabled:opacity-40"
                        :class="currentAnswer.mode === 'text' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                        :disabled="isRecording"
                        @click="setMode('text')"
                    >
                        テキスト
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition disabled:opacity-40"
                        :class="currentAnswer.mode === 'voice' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                        :disabled="isRecording"
                        @click="setMode('voice')"
                    >
                        音声
                    </button>
                </div>

                <!-- テキスト入力 -->
                <div v-if="currentAnswer.mode === 'text'">
                    <textarea
                        v-model="currentAnswer.text"
                        rows="5"
                        maxlength="100"
                        placeholder="ここに入力してください"
                        class="w-full resize-none rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 placeholder-slate-300 outline-none transition focus:border-indigo-300 focus:ring-2 focus:ring-indigo-100"
                    ></textarea>
                    <p class="mt-1 text-right text-xs text-slate-400">{{ currentAnswer.text.length }} / 100</p>
                </div>

                <!-- 音声入力 -->
                <div v-else class="flex flex-col items-center gap-4 py-4">
                    <div v-if="currentAnswer.audioBlob" class="flex w-full items-center gap-3 rounded-xl bg-sky-50 px-4 py-3">
                        <svg class="h-5 w-5 flex-shrink-0 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="flex-1 text-sm text-sky-700">録音済み（{{ formatDuration(currentAnswer.audioDuration) }}）</span>
                        <button type="button" class="text-xs text-slate-400 hover:text-slate-600" @click="clearAudio">やり直す</button>
                    </div>

                    <div v-else class="flex flex-col items-center gap-3">
                        <!-- 波形アニメーション（録音中のみ表示） -->
                        <div v-if="isRecording" class="flex items-end gap-1 h-10">
                            <span v-for="i in 12" :key="i" class="w-1 rounded bg-rose-400 animate-pulse" :style="{ height: `${12 + (i % 4) * 8}px` }"></span>
                        </div>

                        <p v-if="isRecording" class="flex items-center gap-2 text-sm text-rose-500">
                            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-rose-500"></span>
                            録音中… {{ formatDuration(recordingDuration) }}
                        </p>
                        <button
                            type="button"
                            class="flex h-16 w-16 items-center justify-center rounded-full shadow-md transition"
                            :class="isRecording ? 'bg-rose-500 hover:bg-rose-600' : 'bg-sky-500 hover:bg-sky-600'"
                            @click="toggleRecording"
                        >
                            <svg v-if="!isRecording" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                            </svg>
                            <svg v-else class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <rect x="6" y="6" width="12" height="12" rx="2" />
                            </svg>
                        </button>
                        <p class="text-center text-xs text-slate-400">{{ isRecording ? 'タップして停止' : 'タップして録音開始' }}</p>
                    </div>

                    <p v-if="micError" class="text-xs text-rose-500">{{ micError }}</p>
                </div>
            </div>

            <!-- ナビゲーション（録音中は戻るをロック） -->
            <div class="mt-6 flex justify-between">
                <button
                    type="button"
                    class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:opacity-40"
                    :disabled="currentStep === 0 || isRecording"
                    @click="prevStep"
                >
                    戻る
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-40"
                    :disabled="!canProceed"
                    @click="nextStep"
                >
                    {{ isLastStep ? '確認する' : '次へ' }}
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '../layouts/AppLayout.vue';
import axios from 'axios';

const questions = ref([]);
const isLoadingQuestions = ref(true);
const questionsError = ref('');

const today = new Date().toLocaleDateString('ja-JP', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'short' });

const currentStep = ref(0);
const isConfirmStep = ref(false);

const answers = ref([]);

onMounted(async () => {
    try {
        const response = await axios.get('/api/questions');
        questions.value = response.data.data;
        answers.value = questions.value.map(() => ({
            mode: 'text',
            text: '',
            audioBlob: null,
            audioDuration: 0,
        }));
    } catch {
        questionsError.value = '質問の取得に失敗しました。時間をおいて再試行してください。';
    } finally {
        isLoadingQuestions.value = false;
    }
});

// 音声録音
const isRecording = ref(false);
const recordingDuration = ref(0);
const micError = ref('');
let mediaRecorder = null;
let mediaStream = null;
let recordingTimer = null;
let recordingStepIndex = null;
let audioChunks = [];

const currentQuestion = computed(() => questions.value[currentStep.value]);
const currentAnswer = computed(() => answers.value[currentStep.value]);
const isLastStep = computed(() => currentStep.value === questions.value.length - 1);

const canProceed = computed(() => {
    const answer = currentAnswer.value;
    if (answer.mode === 'text') return answer.text.trim().length > 0;
    return answer.audioBlob !== null;
});

function setMode(mode) {
    answers.value[currentStep.value].mode = mode;
}

function nextStep() {
    if (isLastStep.value) {
        isConfirmStep.value = true;
    } else {
        currentStep.value++;
    }
}

function prevStep() {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
}

async function toggleRecording() {
    if (isRecording.value) {
        stopRecording();
    } else {
        await startRecording();
    }
}

function cleanupRecorder() {
    if (recordingTimer) {
        clearInterval(recordingTimer);
        recordingTimer = null;
    }
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
    mediaRecorder = null;
    audioChunks = [];
    isRecording.value = false;
}

async function startRecording() {
    micError.value = '';
    recordingStepIndex = currentStep.value;
    recordingDuration.value = 0;
    audioChunks = [];

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(mediaStream);

        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            const target = answers.value[recordingStepIndex];
            if (target) {
                target.audioBlob = new Blob(audioChunks, { type: mediaRecorder?.mimeType || 'audio/webm' });
                target.audioDuration = recordingDuration.value;
            }
            cleanupRecorder();
        };

        mediaRecorder.start();
        isRecording.value = true;
        recordingTimer = setInterval(() => {
            recordingDuration.value++;
        }, 1000);
    } catch (error) {
        cleanupRecorder();

        if (!navigator.mediaDevices?.getUserMedia) {
            micError.value = 'このブラウザは音声録音に対応していません。';
        } else if (!window.MediaRecorder) {
            micError.value = 'このブラウザは録音機能に対応していません。';
        } else if (error?.name === 'NotAllowedError') {
            micError.value = 'マイク権限が拒否されています。ブラウザの設定を確認してください。';
        } else {
            micError.value = '録音の開始に失敗しました。時間をおいて再試行してください。';
        }
    }
}

function stopRecording() {
    if (mediaRecorder && isRecording.value) {
        mediaRecorder.stop();
    }
}

function clearAudio() {
    answers.value[currentStep.value].audioBlob = null;
    answers.value[currentStep.value].audioDuration = 0;
}

function formatDuration(seconds) {
    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
}

function handleSubmit() {
    // 保存処理は対象外（issue仕様より）
    // 将来の実装: POST /api/daily-logs/text または /api/daily-logs/voice
}

onUnmounted(() => {
    if (isRecording.value) stopRecording();
    cleanupRecorder();
});
</script>
