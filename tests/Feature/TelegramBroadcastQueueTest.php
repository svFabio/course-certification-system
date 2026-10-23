<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\CourseResource\Pages\ListCourses;
use App\Jobs\BroadcastCourseToTelegram;
use App\Models\Course;
use App\Models\Group;
use App\Models\Preinscription;
use App\Models\User;
use App\Services\TelegramChannelService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => UserRole::ADMIN->value]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('links the oldest group that still accepts preinscriptions', function () {
    $course = Course::factory()->create([
        'status' => CourseStatus::PUBLICADO,
        'portada_path' => null,
    ]);

    $closed = Group::factory()->cerrado()->create(['course_id' => $course->id]);

    $open = Group::factory()->habilitado()->create(['course_id' => $course->id]);

    $full = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_maximo' => 1,
    ]);
    Preinscription::factory()->inscrito()->create(['group_id' => $full->id]);

    $service = new TelegramChannelService('fake-token', '@umss_channel');
    $caption = $service->formatCaption($course->fresh());

    expect($caption)
        ->toContain('Preinscripciones abiertas')
        ->toContain(route('preinscripcion', $open))
        ->not->toContain(route('preinscripcion', $closed))
        ->not->toContain(route('preinscripcion', $full));
});

it('falls back to the course catalog when no group accepts preinscriptions', function () {
    $course = Course::factory()->create([
        'status' => CourseStatus::PUBLICADO,
        'portada_path' => null,
    ]);

    Group::factory()->cerrado()->create(['course_id' => $course->id]);

    $service = new TelegramChannelService('fake-token', '@umss_channel');
    $caption = $service->formatCaption($course->fresh());

    expect($caption)
        ->toContain('Ver cursos disponibles')
        ->toContain(route('home'))
        ->not->toContain('Preinscripciones abiertas');
});

it('queues the broadcast instead of calling telegram during the admin request', function () {
    Queue::fake();
    Http::fake();
    config([
        'services.telegram.bot_token' => 'fake-token',
        'services.telegram.channel_chat_id' => '@umss_channel',
    ]);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::ADMIN->value);
    $this->actingAs($admin);

    $course = Course::factory()->create([
        'status' => CourseStatus::PUBLICADO,
        'portada_path' => null,
    ]);
    Group::factory()->habilitado()->create(['course_id' => $course->id]);

    Livewire::test(ListCourses::class)
        ->callTableAction('campañaRedes', $course);

    Queue::assertPushed(
        BroadcastCourseToTelegram::class,
        fn (BroadcastCourseToTelegram $job): bool => $job->courseId === $course->id,
    );

    Http::assertNothingSent();
});

it('sends the announcement when the queued job runs', function () {
    Http::fake([
        'https://api.telegram.org/botfake-token/sendMessage' => Http::response(['ok' => true], 200),
    ]);
    config([
        'services.telegram.bot_token' => 'fake-token',
        'services.telegram.channel_chat_id' => '@umss_channel',
    ]);

    $course = Course::factory()->create([
        'nombre' => 'Curso en cola',
        'portada_path' => null,
        'status' => CourseStatus::PUBLICADO,
    ]);
    Group::factory()->habilitado()->create(['course_id' => $course->id]);

    (new BroadcastCourseToTelegram($course->id))->handle(app(TelegramChannelService::class));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage')
        && str_contains($request['text'], 'Curso en cola'));
});

it('does not fail the queued job when the course was deleted', function () {
    Http::fake();

    (new BroadcastCourseToTelegram(PHP_INT_MAX))->handle(app(TelegramChannelService::class));

    Http::assertNothingSent();
});
