<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GroupStatus;
use App\Enums\MergeParticipantDecision;
use App\Enums\PreinscriptionStatus;
use App\Events\GroupSheetsNeedRefresh;
use App\Mail\GroupMergedNotification;
use App\Models\Group;
use App\Models\Preinscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class GroupMergeService
{
    public function __construct(
        private readonly PreinscriptionService $preinscriptions,
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Executes an assisted merge of a course group into a destination group.
     *
     * Processes decisions per active participant (trasladar, reembolso, retirar),
     * closes the origin group and dispatches spreadsheet refreshes and notifications.
     *
     * @param  array<int|string, string>  $participantDecisions  Mapping of preinscription id to decision ('trasladar', 'reembolso', 'retirar')
     * @return array{
     *     origin: Group,
     *     destination: Group,
     *     moved: Collection<int, Preinscription>,
     *     refunded: Collection<int, Preinscription>,
     *     withdrawn: Collection<int, Preinscription>,
     *     movedCount: int,
     *     refundedCount: int,
     *     withdrawnCount: int
     * }
     */
    public function assistedMerge(Group $origin, Group $destinationGroup, array $participantDecisions = []): array
    {
        $result = DB::transaction(function () use ($origin, $destinationGroup, $participantDecisions): array {
            $origin = Group::query()->whereKey($origin->getKey())->lockForUpdate()->firstOrFail();
            $destination = Group::query()->whereKey($destinationGroup->getKey())->lockForUpdate()->firstOrFail();

            if ($destination->getKey() === $origin->getKey()
                || $destination->course_id !== $origin->course_id
                || $destination->status === GroupStatus::CERRADO
            ) {
                throw ValidationException::withMessages([
                    'grupo_destino_id' => 'Seleccione un grupo destino válido del mismo curso que no esté cerrado.',
                ]);
            }

            $activeStatuses = [PreinscriptionStatus::PENDIENTE_PAGO, PreinscriptionStatus::INSCRITO];
            $activeParticipants = $origin->preinscriptions()
                ->whereIn('status', $activeStatuses)
                ->get();

            // Resolve decision per participant (default is 'trasladar')
            $toMove = new Collection;
            $toRefund = new Collection;
            $toWithdraw = new Collection;

            foreach ($activeParticipants as $participant) {
                $rawDecision = $participantDecisions[$participant->id] ?? $participantDecisions[(string) $participant->id] ?? MergeParticipantDecision::TRASLADAR;
                $decision = $rawDecision instanceof MergeParticipantDecision
                    ? $rawDecision
                    : MergeParticipantDecision::tryFrom((string) $rawDecision) ?? MergeParticipantDecision::TRASLADAR;

                match ($decision) {
                    MergeParticipantDecision::REEMBOLSO => $toRefund->push($participant),
                    MergeParticipantDecision::RETIRAR => $toWithdraw->push($participant),
                    MergeParticipantDecision::TRASLADAR => $toMove->push($participant),
                };
            }

            $toMoveCount = $toMove->count();

            if ($this->preinscriptions->availableSlots($destination) < $toMoveCount) {
                throw ValidationException::withMessages([
                    'grupo_destino_id' => "El grupo destino no tiene cupo disponible para {$toMoveCount} participante(s).",
                ]);
            }

            // Process participants to move
            foreach ($toMove as $participant) {
                $participant->update(['group_id' => $destination->getKey()]);
            }

            // Process refunds
            foreach ($toRefund as $participant) {
                if ($participant->status === PreinscriptionStatus::INSCRITO) {
                    $this->paymentService->requestRefund($participant, 'Incompatibilidad de horario por fusión de grupo');
                } else {
                    $this->preinscriptions->markAsWithdrawn($participant);
                }
            }

            // Process withdrawals
            foreach ($toWithdraw as $participant) {
                $this->preinscriptions->markAsWithdrawn($participant);
            }

            $origin->status = GroupStatus::CERRADO;
            $origin->save();

            return [
                'origin' => $origin,
                'destination' => $destination,
                'moved' => $toMove,
                'refunded' => $toRefund,
                'withdrawn' => $toWithdraw,
                'movedCount' => $toMoveCount,
                'refundedCount' => $toRefund->count(),
                'withdrawnCount' => $toWithdraw->count(),
            ];
        });

        // Trigger Google Sheets refresh for origin and destination
        GroupSheetsNeedRefresh::dispatch($result['origin']->getKey());
        GroupSheetsNeedRefresh::dispatch($result['destination']->getKey());

        // Queue notifications for moved participants with email
        foreach ($result['moved'] as $participant) {
            if (filled($participant->email)) {
                Mail::to($participant->email)
                    ->queue(new GroupMergedNotification($result['origin'], $result['destination'], $participant, MergeParticipantDecision::TRASLADAR));
            }
        }

        // Queue notifications for refunded participants (schedule incompatibility)
        foreach ($result['refunded'] as $participant) {
            if (filled($participant->email)) {
                Mail::to($participant->email)
                    ->queue(new GroupMergedNotification($result['origin'], $result['destination'], $participant, MergeParticipantDecision::REEMBOLSO));
            }
        }

        // Queue notifications for withdrawn participants
        foreach ($result['withdrawn'] as $participant) {
            if (filled($participant->email)) {
                Mail::to($participant->email)
                    ->queue(new GroupMergedNotification($result['origin'], $result['destination'], $participant, MergeParticipantDecision::RETIRAR));
            }
        }

        return $result;
    }

    /**
     * Backwards-compatible merge wrapper.
     *
     * @return array{origin: Group, destination: Group, toMove: Collection<int, Preinscription>, movedCount: int, moved: Collection<int, Preinscription>, refunded: Collection<int, Preinscription>, withdrawn: Collection<int, Preinscription>, refundedCount: int, withdrawnCount: int}
     */
    public function merge(Group $origin, Group $destinationGroup): array
    {
        $res = $this->assistedMerge($origin, $destinationGroup, []);

        return array_merge($res, [
            'toMove' => $res['moved'],
        ]);
    }
}
