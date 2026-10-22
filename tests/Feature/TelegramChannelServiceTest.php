<?php

declare(strict_types=1);

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Group;
use App\Services\TelegramChannelService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('returns failure when not configured', function () {
    $service = new TelegramChannelService('', '');

    expect($service->isConfigured())->toBeFalse();

    $course = Course::factory()->create();
    $result = $service->broadcastCourse($course);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('no están configurados');
});

it('formats course caption with academic and pricing data', function () {
    $course = Course::factory()->create([
        'nombre' => 'Ciberseguridad y Redes',
        'contenido' => 'Aprende seguridad informática y pentesting.',
        'carga_horaria' => '30',
        'nivel' => CourseLevel::AVANZADO,
        'precio_umss' => 250.00,
        'precio_externo' => 380.00,
    ]);

    Group::factory()->habilitado()->create(['course_id' => $course->id]);

    $service = new TelegramChannelService('fake-token', '@test_channel');
    $caption = $service->formatCaption($course);

    expect($caption)
        ->toContain('Ciberseguridad y Redes')
        ->toContain('Aprende seguridad informática')
        ->toContain('30 horas')
        ->toContain('Bs. 250.00 (UMSS)')
        ->toContain('Bs. 380.00 (Externo)');
});

it('broadcasts course using sendPhoto when course has cover image', function () {
    Http::fake([
        'https://api.telegram.org/botfake-token/sendPhoto' => Http::response(['ok' => true, 'result' => ['message_id' => 123]], 200),
    ]);

    $course = Course::factory()->create([
        'nombre' => 'Desarrollo Web Fullstack',
        'portada_path' => 'https://images.unsplash.com/photo-1?auto=format&w=800',
        'status' => CourseStatus::PUBLICADO,
    ]);

    $service = new TelegramChannelService('fake-token', '@umss_channel');
    $result = $service->broadcastCourse($course);

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toContain('exitosamente');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/sendPhoto')
            && $request['chat_id'] === '@umss_channel'
            && $request['photo'] === 'https://images.unsplash.com/photo-1?auto=format&w=800'
            && str_contains($request['caption'], 'Desarrollo Web Fullstack');
    });
});

it('falls back to sendMessage when course has no cover image', function () {
    Http::fake([
        'https://api.telegram.org/botfake-token/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 456]], 200),
    ]);

    $course = Course::factory()->create([
        'nombre' => 'Introducción a Python',
        'portada_path' => null,
        'status' => CourseStatus::PUBLICADO,
    ]);

    $service = new TelegramChannelService('fake-token', '-100987654321');
    $result = $service->broadcastCourse($course);

    expect($result['success'])->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === '-100987654321'
            && str_contains($request['text'], 'Introducción a Python');
    });
});

it('handles telegram error response gracefully', function () {
    Http::fake([
        'https://api.telegram.org/botfake-token/sendMessage' => Http::response([
            'ok' => false,
            'description' => 'Bad Request: chat not found',
        ], 400),
    ]);

    $course = Course::factory()->create([
        'portada_path' => null,
    ]);

    $service = new TelegramChannelService('fake-token', '@canal_inexistente');
    $result = $service->broadcastCourse($course);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('chat not found');
});

it('never leaks the bot token when the connection fails', function () {
    $secret = 'fake-secret-token-value';

    // A connection failure makes Guzzle embed the full request URI — token
    // included — in the exception message. That message must never reach the
    // admin UI or the log.
    Http::fake([
        'https://api.telegram.org/*' => fn () => throw new ConnectionException(
            "cURL error 6: Could not resolve host: api.telegram.org for https://api.telegram.org/bot{$secret}/sendMessage"
        ),
    ]);

    $course = Course::factory()->create(['portada_path' => null]);

    $service = new TelegramChannelService($secret, '@canal');
    $result = $service->broadcastCourse($course);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->not->toContain($secret)
        ->and($result['message'])->toContain('Excepción al conectar con Telegram');
});

it('never writes the bot token to the log when the connection fails', function () {
    $secret = 'fake-secret-token-value';

    Http::fake([
        'https://api.telegram.org/*' => fn () => throw new ConnectionException(
            "cURL error 28: Connection timed out for https://api.telegram.org/bot{$secret}/sendMessage"
        ),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) use ($secret): bool {
            return ! str_contains(json_encode($context), $secret);
        });

    Log::shouldReceive('warning')->zeroOrMoreTimes();
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $course = Course::factory()->create(['portada_path' => null]);

    $service = new TelegramChannelService($secret, '@canal');
    $result = $service->broadcastCourse($course);

    expect($result['success'])->toBeFalse();
});
