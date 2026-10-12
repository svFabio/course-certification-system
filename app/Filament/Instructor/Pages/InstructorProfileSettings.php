<?php

declare(strict_types=1);

namespace App\Filament\Instructor\Pages;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class InstructorProfileSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Mi Perfil';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Mi Firma Digital';

    protected static ?string $title = 'Firma Digital del Docente';

    protected static ?string $slug = 'profile/signature';

    protected static string $view = 'filament.instructor.pages.profile-settings';

    public ?array $data = [];

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        $this->form->fill([
            'name' => $user?->name ?? '',
            'email' => $user?->email ?? '',
            'firma_digital_path' => $user?->firma_digital_path,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Firma para Certificados de Cursos')
                    ->description('Cargue su firma digital con fondo transparente para estamparla automáticamente en los certificados de los cursos que usted dicte.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Docente')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Institucional')
                            ->disabled(),
                        Forms\Components\FileUpload::make('firma_digital_path')
                            ->label('Firma Digital (PNG con fondo transparente)')
                            ->disk('cloudinary')
                            ->directory('firmas/docentes')
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(2048)
                            ->helperText('Formato PNG transparente, resolución recomendada 600x300 px (máx. 2MB).')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $state = $this->form->getState();

        $user->update([
            'firma_digital_path' => $state['firma_digital_path'] ?? null,
        ]);

        Notification::make()
            ->title('Firma digital guardada correctamente')
            ->success()
            ->send();
    }
}
