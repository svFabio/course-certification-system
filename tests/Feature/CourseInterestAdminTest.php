<?php

declare(strict_types=1);

use App\Enums\CourseStatus;
use App\Enums\InterestStatus;
use App\Filament\Admin\Resources\GroupResource\Pages\ListGroups;
use App\Filament\Admin\Widgets\CourseInterestOverviewWidget;
use App\Mail\CourseSlotAvailableNotification;
use App\Models\Course;
use App\Models\CourseInterest;
use App\Models\User;
use App\Services\CourseDemandService;
use App\Support\BusinessRules;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs($this->admin);
});

it('flags the interest threshold once a course reaches the minimum group capacity', function () {
    $course = Course::factory()->create();
    CourseInterest::factory()->count(BusinessRules::MIN_GROUP_CAPACITY - 1)->create(['course_id' => $course->id]);

    $service = app(CourseDemandService::class);

    $below = $service->coursesWithInterest()->get()->firstWhere('id', $course->id);

    expect((int) $below->active_interests_count)->toBe(BusinessRules::MIN_GROUP_CAPACITY - 1)
        ->and($service->interestThresholdReached((int) $below->active_interests_count))->toBeFalse();

    CourseInterest::factory()->create(['course_id' => $course->id]);

    $at = $service->coursesWithInterest()->get()->firstWhere('id', $course->id);

    expect((int) $at->active_interests_count)->toBe(BusinessRules::MIN_GROUP_CAPACITY)
        ->and($service->interestThresholdReached((int) $at->active_interests_count))->toBeTrue();
});

it('shows the interest overview with counts and the current threshold on the widget', function () {
    $course = Course::factory()->create(['nombre' => 'Curso Con Interesados']);
    CourseInterest::factory()->count(3)->create(['course_id' => $course->id]);

    Livewire::test(CourseInterestOverviewWidget::class)
        ->assertSee('Curso Con Interesados')
        ->assertSee('3 / '.BusinessRules::MIN_GROUP_CAPACITY.' mínimos')
        ->assertSee('Bajo el mínimo');
});

it('notifies interested students from the admin action and queues one mail per student', function () {
    Mail::fake();

    $course = Course::factory()->create();
    CourseInterest::factory()->count(2)->create(['course_id' => $course->id]);
    CourseInterest::factory()->notificado()->create(['course_id' => $course->id]);

    Livewire::test(CourseInterestOverviewWidget::class)
        ->callTableAction('notificarInteresados', $course)
        ->assertHasNoTableActionErrors();

    expect(CourseInterest::where('course_id', $course->id)
        ->where('estado', InterestStatus::NOTIFICADO->value)
        ->count())->toBe(3);

    Mail::assertQueued(CourseSlotAvailableNotification::class, 2);
});

it('allows notifying below the minimum threshold and shows a visible warning', function () {
    Mail::fake();

    $course = Course::factory()->create();
    CourseInterest::factory()->count(3)->create(['course_id' => $course->id]);

    session()->forget('filament.notifications');

    Livewire::test(CourseInterestOverviewWidget::class)
        ->callTableAction('notificarInteresados', $course)
        ->assertHasNoTableActionErrors();

    $notifications = collect(session('filament.notifications', []));
    $warning = $notifications->firstWhere('status', 'warning');

    expect($warning)->not->toBeNull()
        ->and($warning['title'])->toBe('Umbral mínimo no alcanzado');

    expect(CourseInterest::where('course_id', $course->id)
        ->where('estado', InterestStatus::NOTIFICADO->value)
        ->count())->toBe(3);

    Mail::assertQueued(CourseSlotAvailableNotification::class, 3);
});

it('archives active interests of finished courses through the groups page header action', function () {
    $finished = Course::factory()->create(['status' => CourseStatus::FINALIZADO]);
    CourseInterest::factory()->create(['course_id' => $finished->id]);

    $published = Course::factory()->create(['status' => CourseStatus::PUBLICADO]);
    $kept = CourseInterest::factory()->create(['course_id' => $published->id]);

    Livewire::test(ListGroups::class)
        ->callAction('archivarInteresesFinalizados')
        ->assertHasNoActionErrors();

    expect(CourseInterest::where('course_id', $finished->id)
        ->where('estado', InterestStatus::DESCARTADO->value)
        ->count())->toBe(1)
        ->and($kept->fresh()->estado)->toBe(InterestStatus::INTERESADO);
});
