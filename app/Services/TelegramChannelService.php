<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramChannelService
{
    public function __construct(
        protected ?string $botToken = null,
        protected ?string $channelChatId = null,
    ) {
        $this->botToken = $botToken ?? (string) config('services.telegram.bot_token', '');
        $this->channelChatId = $channelChatId ?? (string) config('services.telegram.channel_chat_id', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->botToken) && ! empty($this->channelChatId);
    }

    public function formatCaption(Course $course): string
    {
        $nombre = htmlspecialchars($course->nombre, ENT_QUOTES, 'UTF-8');
        $contenido = htmlspecialchars(Str::limit(strip_tags((string) $course->contenido), 300), ENT_QUOTES, 'UTF-8');
        $carga = $course->carga_horaria;
        $nivel = $course->nivel?->value ?? 'General';
        $precioUmss = number_format((float) $course->precio_umss, 2);
        $precioExterno = number_format((float) $course->precio_externo, 2);

        $firstGroup = $course->groups->first();
        $courseUrl = $firstGroup ? route('preinscripcion', $firstGroup) : route('home');

        return "🎓 <b>NUEVO CURSO DISPONIBLE</b>\n\n"
            ."<b>{$nombre}</b>\n\n"
            ."📖 {$contenido}\n\n"
            ."⏱ <b>Carga horaria:</b> {$carga} horas\n"
            ."📊 <b>Nivel:</b> {$nivel}\n"
            ."💰 <b>Inversión:</b> Bs. {$precioUmss} (UMSS) | Bs. {$precioExterno} (Externo)\n\n"
            ."🔗 <b>Preinscripciones abiertas:</b>\n"
            ."<a href=\"{$courseUrl}\">{$courseUrl}</a>";
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function broadcastCourse(Course $course): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'El bot de Telegram o el ID del canal no están configurados en el entorno (TELEGRAM_BOT_TOKEN / TELEGRAM_CHANNEL_CHAT_ID).',
            ];
        }

        $caption = $this->formatCaption($course);
        $token = $this->botToken;
        $chatId = $this->channelChatId;

        try {
            // Intentar primero con foto de portada si existe
            if (! empty($course->portada_url)) {
                $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'photo' => $course->portada_url,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);

                if ($response->successful()) {
                    Log::info('Telegram broadcast sent with photo', [
                        'course_id' => $course->id,
                        'chat_id' => $chatId,
                    ]);

                    return [
                        'success' => true,
                        'message' => 'Anuncio publicado exitosamente en el canal de Telegram.',
                    ];
                }

                Log::warning('Telegram sendPhoto failed, falling back to sendMessage', [
                    'course_id' => $course->id,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
            }

            // Fallback a mensaje de texto si no hay foto o sendPhoto falló
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $caption,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ]);

            if ($response->successful()) {
                Log::info('Telegram broadcast sent as text', [
                    'course_id' => $course->id,
                    'chat_id' => $chatId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Anuncio publicado exitosamente en el canal de Telegram.',
                ];
            }

            $description = $response->json('description') ?? 'Error desconocido al comunicar con la API de Telegram.';
            Log::error('Telegram broadcast failed', [
                'course_id' => $course->id,
                'status' => $response->status(),
                'description' => $description,
            ]);

            return [
                'success' => false,
                'message' => "Error de Telegram: {$description}",
            ];
        } catch (\Throwable $e) {
            // Guzzle embeds the full request URI — bot token included — in the
            // exception message. Never let that reach the log or the admin UI.
            $safeMessage = $this->redactToken($e->getMessage());

            Log::error('Telegram broadcast exception', [
                'course_id' => $course->id,
                'error' => $safeMessage,
            ]);

            return [
                'success' => false,
                'message' => "Excepción al conectar con Telegram: {$safeMessage}",
            ];
        }
    }

    /**
     * Strip any occurrence of the configured bot token, and the
     * /bot<token> URL segment, from a message before it is logged or returned.
     */
    protected function redactToken(string $message): string
    {
        if ($this->botToken !== '') {
            $message = str_replace($this->botToken, '[redacted]', $message);
        }

        return (string) preg_replace('#/bot[^/\s]*#', '/bot[redacted]', $message);
    }
}
