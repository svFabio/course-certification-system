<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\PreinscriptionStatus;
use App\Models\Group;
use App\Models\Preinscription;
use App\Services\PreinscriptionService;
use App\Support\BusinessRules;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class PreinscriptionComponent extends Component
{
    use WithFileUploads;

    #[Url]
    public ?int $groupId = null;

    public ?string $ci = null;

    public ?string $codSis = null;

    public ?string $nombres = null;

    public ?string $apellidoPaterno = null;

    public ?string $apellidoMaterno = null;

    public ?string $celular = null;

    public ?string $email = null;

    public ?string $tipoParticipante = null;

    #[Rule(['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'])]
    public $auxiliarCertificado = null;

    public bool $stepConfirmation = false;

    public bool $isSubmitted = false;

    public ?array $registeredData = null;

    public ?Group $group = null;

    public $availableGroups = [];

    public function mount(Group|int $group): void
    {
        $groupModel = $group instanceof Group ? $group : Group::with('course')->findOrFail($group);
        $this->groupId = $groupModel->id;
        $this->group = $groupModel;
        $this->availableGroups = app(PreinscriptionService::class)->getAvailableGroups($this->group->course_id);
    }

    public function updatedGroupId(): void
    {
        if ($this->groupId) {
            $this->group = Group::with('course')->find($this->groupId);
        }
    }

    public function getPrecioCalculadoProperty(): ?float
    {
        if (! $this->tipoParticipante || ! $this->group || ! in_array($this->tipoParticipante, ['umss', 'externo', 'auxiliar'], true)) {
            return null;
        }

        $participantType = $this->tipoParticipante === 'auxiliar'
            ? 'umss'
            : $this->tipoParticipante;

        return BusinessRules::calculatePrice(
            (int) $this->group->course->carga_horaria,
            $participantType
        );
    }

    public function getPrecioDescuentoAuxiliarProperty(): ?float
    {
        if (! $this->group) {
            return null;
        }

        return BusinessRules::calculatePrice(
            (int) $this->group->course->carga_horaria,
            'auxiliar'
        );
    }

    protected function rules(): array
    {
        return [
            'groupId' => ['required', 'integer', 'exists:groups,id'],
            'ci' => ['required', 'string', 'regex:/^[0-9]{4,10}(-[0-9A-Z]{1,2})?$/i'],
            'codSis' => ['nullable', 'string', 'regex:/^[0-9]{7,10}$/'],
            'nombres' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/'],
            'apellidoPaterno' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/'],
            'apellidoMaterno' => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\.\'\-]+$/'],
            'celular' => ['nullable', 'string', 'regex:/^[67][0-9]{7}$/'],
            'email' => ['required', 'email:rfc,dns', 'max:150'],
            'tipoParticipante' => ['required', 'in:umss,externo,auxiliar'],
            'auxiliarCertificado' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'ci.regex' => 'El CI debe contener entre 4 y 10 dígitos numéricos (ej. 7894561 o 7894561-1A).',
            'codSis.regex' => 'El Código SIS debe ser numérico entre 7 y 10 dígitos (ej. 202002515).',
            'nombres.regex' => 'Los nombres solo deben contener letras, espacios y tildes.',
            'apellidoPaterno.regex' => 'El apellido paterno solo debe contener letras, espacios y tildes.',
            'apellidoMaterno.regex' => 'El apellido materno solo debe contener letras, espacios y tildes.',
            'celular.regex' => 'El celular debe ser un número boliviano válido de 8 dígitos (iniciando con 6 o 7).',
            'email.email' => 'Ingrese una dirección de correo electrónico válida.',
            'tipoParticipante.in' => 'Seleccione un tipo de participante válido.',
            'auxiliarCertificado.mimes' => 'El certificado debe ser un archivo PDF o imagen (jpg/png).',
            'auxiliarCertificado.max' => 'El certificado no debe superar los 5 MB.',
        ];
    }

    public function goToConfirmation(): void
    {
        $this->validate();

        $existing = Preinscription::where('ci', $this->ci)
            ->whereHas('group', fn ($q) => $q->where('course_id', $this->group->course_id))
            ->whereIn('status', [
                PreinscriptionStatus::PENDIENTE_PAGO,
                PreinscriptionStatus::INSCRITO,
            ])
            ->latest('id')
            ->first();

        if ($existing) {
            $message = $existing->status === PreinscriptionStatus::INSCRITO
                ? 'Ya estás inscrito(a) en este curso. Revisa tu correo o contacta a la coordinación para más información.'
                : 'Ya enviaste tu preinscripción para este curso. Por favor realiza el pago y mantente atento(a) a tu teléfono o correo para confirmar tu inscripción.';

            $this->addError('ci', $message);

            return;
        }

        $this->stepConfirmation = true;
    }

    public function backToEdit(): void
    {
        $this->stepConfirmation = false;
    }

    public function submit(PreinscriptionService $service): void
    {
        $validated = $this->validate();

        $ciKey = 'preinscripcion:ci:'.mb_strtolower((string) $validated['ci']);
        $ipKey = 'preinscripcion:ip:'.request()->ip();

        if (RateLimiter::tooManyAttempts($ciKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            $this->addError('ci', 'Has realizado demasiados intentos. Por favor espera unos minutos e intenta nuevamente.');
            $this->stepConfirmation = false;

            return;
        }

        $certificatePath = null;
        if ($validated['tipoParticipante'] === 'auxiliar' && $this->auxiliarCertificado) {
            $certificatePath = $this->auxiliarCertificado->store(
                preg_replace('/[^a-zA-Z0-9]+/', '-', mb_strtolower((string) $validated['ci'])),
                'cloudinary'
            );
        }

        $preinscription = $service->register([
            'group_id' => $validated['groupId'],
            'ci' => $validated['ci'],
            'cod_sis' => in_array($validated['tipoParticipante'], ['umss', 'auxiliar'], true) ? ($validated['codSis'] ?? null) : null,
            'nombres' => $validated['nombres'],
            'apellido_paterno' => $validated['apellidoPaterno'],
            'apellido_materno' => $validated['apellidoMaterno'] ?? null,
            'celular' => $validated['celular'] ?? null,
            'email' => $validated['email'],
            'tipo_participante' => $validated['tipoParticipante'],
            'auxiliar_certificado_path' => $certificatePath,
        ]);

        RateLimiter::hit($ciKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        $this->registeredData = [
            'nombres' => "{$this->nombres} {$this->apellidoPaterno} {$this->apellidoMaterno}",
            'ci' => $this->ci,
            'curso' => $this->group->course->nombre,
            'grupo' => "{$this->group->nombre} ({$this->group->hora_inicio->format('H:i')} - {$this->group->hora_fin->format('H:i')})",
            'monto' => $this->precioCalculado,
            'email' => $this->email,
        ];

        $this->isSubmitted = true;
        $this->stepConfirmation = false;
    }

    public function render()
    {
        return view('livewire.preinscription-component', [
            'group' => $this->group,
            'availableGroups' => $this->availableGroups,
        ]);
    }
}
