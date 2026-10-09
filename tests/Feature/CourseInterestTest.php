<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\InterestStatus;
use App\Enums\PreinscriptionStatus;
use App\Mail\CourseSlotAvailableNotification;
use App\Models\Course;
use App\Models\CourseInterest;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\CourseInterestService;
use App\Services\PreinscriptionService;
use App\Support\BusinessRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('prevents a duplicate active interest for the same course and email', function () {
    $course = Course::factory()->create();
    $email = fake()->unique()->safeEmail();
    $service = app(CourseInterestService::class);

    $first = $service->register($course, $email);
    $second = $service->register($course, mb_strtoupper($email));

    expect($second->id)->toBe($first->id)
        ->and($second->wasRecentlyCreated)->toBeFalse()
        ->and(CourseInterest::where('course_id', $course->id)->count())->toBe(1);
});

it('never consumes group capacity when registering an interest', function () {
    $course = Course::factory()->create();
    $group = Group::factory()->habilitado()->create([
        'course_id' => $course->id,
        'cupo_minimo' => BusinessRules::MIN_GROUP_CAPACITY,
        'cupo_maximo' => 30,
    ]);

    $preinscriptions = app(PreinscriptionService::class);

    $occupiedBefore = $group->cupos_ocupados;
    $availableBefore = $preinscriptions->availableSlots($group);

    app(CourseInterestService::class)->register($course, fake()->unique()->safeEmail());

    $group->refresh();

    expect($group->cupos_ocupados)->toBe($occupiedBefore)
        ->and($group->cupo_maximo)->toBe(30)
        ->and($group->cupos_disponibles)->toBe(30)
        ->and($preinscriptions->availableSlots($group))->toBe($availableBefore)
        ->and(Preinscription::where('group_id', $group->id)->count())->toBe(0);
});

it('allows the same email to register interest in two different courses', function () {
    $firstCourse = Course::factory()->create();
    $secondCourse = Course::factory()->create();
    $email = fake()->unique()->safeEmail();
    $service = app(CourseInterestService::class);

    $first = $service->register($firstCourse, $email);
    $second = $service->register($secondCourse, $email);

    expect($first->id)->not->toBe($second->id)
        ->and(CourseInterest::where('email', $email)->count())->toBe(2);
});

it('archives only interests of finished courses', function () {
    $finished = Course::factory()->create(['status' => CourseStatus::FINALIZADO]);
    $published = Course::factory()->create(['status' => CourseStatus::PUBLICADO]);

    $finishedInterest = CourseInterest::factory()->create(['course_id' => $finished->id]);
    $publishedInterest = CourseInterest::factory()->create(['course_id' => $published->id]);

    $archived = app(CourseInterestService::class)->archiveForFinishedCourses();

    expect($archived)->toBe(1)
        ->and($finishedInterest->fresh()->estado)->toBe(InterestStatus::DESCARTADO)
        ->and($publishedInterest->fresh()->estado)->toBe(InterestStatus::INTERESADO);
});

it('links the matching preinscription by email and course only', function () {
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();

    $group = Group::factory()->habilitado()->create(['course_id' => $course->id]);
    $otherGroup = Group::factory()->habilitado()->create(['course_id' => $otherCourse->id]);

    $email = fake()->unique()->safeEmail();
    $otherEmail = fake()->unique()->safeEmail();

    $matchingInterest = CourseInterest::factory()->create([
        'course_id' => $course->id,
        'email' => $email,
    ]);
    $unmatchedInterest = CourseInterest::factory()->create([
        'course_id' => $course->id,
        'email' => $otherEmail,
    ]);

    $matchingPreinscription = Preinscription::factory()->create([
        'group_id' => $group->id,
        'email' => $email,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);
    Preinscription::factory()->create([
        'group_id' => $otherGroup->id,
        'email' => $email,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $converted = app(CourseInterestService::class)->syncConversions($course);

    expect($converted)->toBe(1)
        ->and($matchingInterest->fresh()->estado)->toBe(InterestStatus::CONVERTIDO)
        ->and($matchingInterest->fresh()->preinscription_id)->toBe($matchingPreinscription->id)
        ->and($unmatchedInterest->fresh()->estado)->toBe(InterestStatus::INTERESADO)
        ->and($unmatchedInterest->fresh()->preinscription_id)->toBeNull();
});

it('never links a preinscription that belongs to another course', function () {
    $course = Course::factory()->create();
    $otherCourse = Course::factory()->create();
    $otherGroup = Group::factory()->habilitado()->create(['course_id' => $otherCourse->id]);

    $email = fake()->unique()->safeEmail();
    $interest = CourseInterest::factory()->create([
        'course_id' => $course->id,
        'email' => $email,
    ]);

    Preinscription::factory()->create([
        'group_id' => $otherGroup->id,
        'email' => $email,
        'status' => PreinscriptionStatus::PENDIENTE_PAGO,
    ]);

    $converted = app(CourseInterestService::class)->syncConversions($course);

    expect($converted)->toBe(0)
        ->and($interest->fresh()->estado)->toBe(InterestStatus::INTERESADO)
        ->and($interest->fresh()->preinscription_id)->toBeNull();
});

it('queues one email per notified interest and never re-notifies an already notified row', function () {
    Mail::fake();

    $course = Course::factory()->create();
    $interests = CourseInterest::factory()->count(2)->create(['course_id' => $course->id]);
    CourseInterest::factory()->notificado()->create(['course_id' => $course->id]);

    $service = app(CourseInterestService::class);

    $notified = $service->notifyInterested($course);

    expect($notified)->toBe(2)
        ->and($interests->every(
            fn (CourseInterest $interest): bool => $interest->fresh()->estado === InterestStatus::NOTIFICADO
                && $interest->fresh()->notificado_at !== null
        ))->toBeTrue();

    Mail::assertQueued(CourseSlotAvailableNotification::class, 2);

    expect($service->notifyInterested($course))->toBe(0);

    Mail::assertQueued(CourseSlotAvailableNotification::class, 2);
});
