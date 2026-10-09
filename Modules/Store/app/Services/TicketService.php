<?php

namespace Modules\Store\Services;

use Modules\Store\Enums\StoreMemberRole;
use Modules\Store\Models\IssueType;
use Modules\Store\Models\MasterOrder;
use Modules\Store\Models\OrderItem;
use Modules\Store\Models\SlaRule;
use Modules\Store\Models\StoreMember;
use Modules\Store\Models\StoreOrder;
use Modules\Store\Models\Ticket;
use Modules\Store\Repositories\Interfaces\TicketRepositoryInterface;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TicketService
{
    protected $ticketRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
    }

    public function all()
    {
        return $this->ticketRepository->all();
    }

    public function findDetailedTicket($id)
    {
        $ticket = $this->ticketRepository->findById($id);

        $user = auth()->user();

        if ($ticket->user_id !== $user->id) {

            $member = StoreMember::where('store_id', $ticket->store_id)
                ->where('user_id', $user->id)
                ->active()
                ->first();

            if (!$member) {
                throw new HttpException(403, "Unauthorized to view this ticket.");
            }
        }

        return $ticket;
    }

    public function openTicket(array $data)
    {
        if (!isset($data['order_item_ids'])) {
            throw new HttpException(400, "order_item_ids is required to create a ticket.");
        }
        $orderItemIds = is_array($data['order_item_ids'])
            ? $data['order_item_ids']
            : [$data['order_item_ids']];

        $data['order_item_ids'] = $orderItemIds;

        $orderItems = OrderItem::whereIn('id', $data['order_item_ids'])
            ->whereHas('storeOrder.masterOrder', function ($q) use ($data) {
                $q->where('user_id', $data['user_id']);
            })
            ->whereHas('storeOrder', function ($q) {
                $q->where('status', 'COMPLETED');
            })
            ->with('storeOrder')
            ->get();

        if ($orderItems->count() !== count($data['order_item_ids'])) {
            throw new HttpException(400, "You must buy from this store before creating a ticket.");
        }

        $storeId = $orderItems->first()->storeOrder->store_id;
        if ($orderItems->pluck('storeOrder.store_id')->unique()->count() > 1) {
            throw new HttpException(400, "Order items must belong to the same store.");
        }

        $issueType = IssueType::findOrFail($data['issue_type_id']);

        // if ($issueType->name === 'RETURN') {
        //     return $this->handleReturnTicket($storeOrder, $data);
        // }

        $data['store_id'] = $storeId;
        $data['priority'] = $issueType->priority;

        $slaRule = SlaRule::where('store_id', $data['store_id'])
            ->where('issue_type_id', $data['issue_type_id'])
            ->first();

        $data['sla_response_hours'] = $slaRule->sla_response_hours ?? 24;

        return $this->ticketRepository->createWithMessage($data, $orderItems);
    }




    protected function handleReturnTicket(StoreOrder $order, array $data)
    {
        // سياسة الأهلية (مثلاً 30 يوم)
        $allowedDays = 30; // لاحقًا من سياسة المتجر
        if ($order->created_at->addDays($allowedDays)->isPast()) {
            throw new HttpException(400, "Return window expired.");
        }

        $issueType = IssueType::findOrFail($data['issue_type_id']);

        // إنشاء Ticket
        $ticket = $this->ticketRepository->createWithMessage([
            'store_id'      => $order->store_id,
            'user_id'       => $data['user_id'],
            'order_id'      => $order->id,
            'issue_type_id' => $issueType->id,
            'priority'      => $issueType->priority,
            'subject'       => $data['subject'] ?? 'Return / Refund Request',
            'message'       => $data['reason'] ?? $data['message'],
            'status'        => 'OPEN',
            'sla_response_hours' => SlaRule::where('store_id', $order->store_id)
                ->where('issue_type_id', $issueType->id)
                ->value('sla_response_hours') ?? 24,
        ]);

        // إنشاء سجل المرتجع
        $return = $ticket->returnRequest()->create([
            'preferred_solution' => $data['preferred_solution'],
            'reason'             => $data['reason'],
            'description'        => $data['description'] ?? null,
            'decision'           => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            $return->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity'      => $item['quantity'],
            ]);
        }


        return $ticket->load(['messages', 'returnRequest.items', 'issueType']);
    }


    public function replyToTicket(int $ticketId, array $data)
    {
        $ticket = $this->ticketRepository->findById($ticketId);

        if ($ticket->isResolved()) {
            throw new HttpException(400, "Ticket is already resolved.");
        }

        $user = auth()->user();

        $role = $this->determineSenderRole($user, $ticket);

        $message = $this->ticketRepository->addMessage($ticket, $user->id, $data);

        if ($role !== 'BUYER' && !$ticket->first_response_at) {
            $ticket->first_response_at = now();
        }

        $ticket->status = $role === 'BUYER' ? 'OPEN' : 'ANSWERED';
        $ticket->save();

        return $ticket->load(['messages', 'user']);
    }

    private function determineSenderRole($user, Ticket $ticket)
    {
        if ($user->id === $ticket->user_id) {
            return 'BUYER';
        }

        $member = StoreMember::where('store_id', $ticket->store_id)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (!$member) {
            throw new HttpException(403, "Unauthorized to reply on this ticket.");
        }

        if ($member->isSupport()) {
            return StoreMemberRole::SUPPORT_AGENT->value;
        }

        if ($member->isManager()) {
            return StoreMemberRole::STORE_MANAGER->value;
        }

        if ($member->isOwner()) {
            return StoreMemberRole::STORE_OWNER->value;
        }

        throw new HttpException(403, "Unauthorized role.");
    }

    public function resolveTicket($ticketId)
    {
        $ticket = $this->ticketRepository->findById($ticketId);

        if ($ticket->status === 'RESOLVED') {
            return $ticket;
        }

        return $this->ticketRepository->resolve($ticketId);
    }

    public function getStoreTickets(int $storeId, array $filters = [])
    {
        $user = auth()->user();

        $member = StoreMember::where('store_id', $storeId)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (!$member) {
            throw new HttpException(403, "Unauthorized to view tickets for this store.");
        }

        // $onlyEscalated = $member->isOwner() || $member->isManager();

        return $this->ticketRepository->getByStore($storeId, $filters);
    }

    public function delete($id)
    {
        $ticket = $this->ticketRepository->findById($id);
        throw_if(!$ticket, ModelNotFoundException::class, "Record not found");
        return $this->ticketRepository->delete($ticket);
    }

    public function escalateTicket(int $ticketId)
    {
        $ticket = $this->ticketRepository->escalate($ticketId);

        return $ticket;
    }

    public function getIssueTypes()
    {
        return $this->ticketRepository->getIssueTypes();
    }

    public function getMyTickets(int $userId, array $filters = [])
    {
        return $this->ticketRepository->getTicketsForUser($userId, $filters);
    }


    public function reviewReturn(int $ticketId, array $data)
    {
        $ticket = Ticket::with('returnRequest')->findOrFail($ticketId);

        if (!$ticket->returnRequest) {
            throw new HttpException(400, "This ticket is not a return request.");
        }

        if ($ticket->status !== 'OPEN') {
            throw new HttpException(400, "Ticket is not in a reviewable state.");
        }

        $ticket->update([
            'status' => 'UNDER_REVIEW',
        ]);

        $ticket->returnRequest->auditLogs()->create([
            'action' => 'UNDER_REVIEW',
            'note'   => null,
            'by'     => auth()->id(),
        ]);

        return $ticket->load(['messages', 'returnRequest.items']);
    }

    public function decideReturn(int $ticketId, string $decision, ?string $note = null)
    {
        $ticket = Ticket::with('returnRequest')->findOrFail($ticketId);

        if (!in_array($decision, ['APPROVED', 'DENIED'])) {
            throw new HttpException(400, "Invalid decision.");
        }

        if ($ticket->status !== 'UNDER_REVIEW') {
            throw new HttpException(400, "Ticket must be under review.");
        }

        DB::transaction(function () use ($ticket, $decision, $note) {
            $ticket->update([
                'status' => $decision,
            ]);

            $ticket->returnRequest->update([
                'decision' => strtolower($decision),
                'decision_note' => $note,
                'approved_at' => $decision === 'approved' ? now() : null,

            ]);

            $ticket->returnRequest->auditLogs()->create([
                'action' => $decision,
                'note'   => $note,
                'by'     => auth()->id(),
            ]);

            $ticket->messages()->create([
                'sender_id'   => auth()->id(),
                'message'     => $decision === 'APPROVED'
                    ? 'Your return request has been approved.'
                    : 'Your return request has been denied.',
            ]);
        });

        return $ticket->load(['messages', 'returnRequest.items']);
    }


    public function processRefund(int $ticketId, ?string $note = null)
    {
        $ticket = Ticket::with('returnRequest', 'storeOrder')
            ->findOrFail($ticketId);

        if ($ticket->status !== 'APPROVED') {
            throw new HttpException(400, "Ticket must be approved first.");
        }

        if ($ticket->returnRequest->preferred_solution !== 'refund') {
            throw new HttpException(400, 'Not a refund request.');
        }

        // هنا يتم ربط PSP أو المحفظة
        // $this->paymentService->refund($ticket->storeOrder);

        $ticket->update([
            'status' => 'REFUND_PROCESSED',
        ]);

        $ticket->returnRequest->update([
            'processed_at' => now(),

        ]);

        $ticket->returnRequest->auditLogs()->create([
            'action' => 'REFUND_PROCESSED',
            'by' => auth()->id(),
        ]);
    }

    public function markReplacementSent(int $ticketId, ?string $note = null)
    {
        $ticket = Ticket::with('returnRequest')->findOrFail($ticketId);

        if (!$ticket->returnRequest) {
            throw new HttpException(400, "This ticket is not a return request.");
        }

        if ($ticket->status !== 'APPROVED') {
            throw new HttpException(400, "Replacement can be sent only after approval.");
        }

        if ($ticket->returnRequest->preferred_solution !== 'replacement') {
            throw new HttpException(400, 'Not a refund request.');
        }

        DB::transaction(function () use ($ticket, $note) {

            $ticket->update([
                'status' => 'REPLACEMENT_SENT',
            ]);

            $ticket->returnRequest->auditLogs()->create([
                'action' => 'REPLACEMENT_SENT',
                'note'   => $note,
                'by'     => auth()->id(),
            ]);


            $ticket->returnRequest->update([
                'processed_at' => now(),

            ]);

            $ticket->messages()->create([
                'sender_id'   => auth()->id(),
                'message'     => $note
                    ? "Replacement has been sent. {$note}"
                    : "Your replacement item has been sent.",
            ]);
        });

        return $ticket->load(['messages', 'returnRequest.items']);
    }
}
