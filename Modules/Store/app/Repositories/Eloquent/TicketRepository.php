<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use App\Utilities\FileManager;
use Modules\Store\Models\IssueType;
use Modules\Store\Models\Ticket;
use Modules\Store\Repositories\Interfaces\TicketRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TicketRepository implements TicketRepositoryInterface
{


    use PaginateTrait;

    protected $model;

    public function __construct(Ticket $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function findById($id)
    {
        return $this->model->with([
            'user',
            'store',
            'messages.sender',
            'issueType',
            'returnRequest.items',
        ])->findOrFail($id);
    }

    public function createWithMessage(array $data, $orderItems)
    {
        return DB::transaction(function () use ($data, $orderItems) {
            $ticket = $this->model->create([
                'store_id'          => $data['store_id'],
                'user_id'           => $data['user_id'],
                'issue_type_id'      => $data['issue_type_id'],
                'priority'           => $data['priority'],
                'subject'           => $data['subject'],
                'status'            => 'OPEN',
                'sla_response_hours' => $data['sla_response_hours'] ?? 24,
            ]);

            $ticket->orderItems()->attach($orderItems->pluck('id'));

            $attachments = [];
            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $file) {
                    $attachments[] = FileManager::upload(
                        "tickets/{$ticket->id}",
                        $file
                    );
                }
            }

            $ticket->messages()->create([
                'sender_id'   => $data['user_id'],
                'sender_role' => 'BUYER',
                'message'     => $data['message'],
                'attachments' => $attachments,
            ]);

            return $ticket->load(['messages', 'orderItems']);
        });
    }



    public function addMessage(Ticket $ticket, int $senderId, array $data)
    {
        $attachments = [];

        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                $attachments[] = FileManager::upload(
                    "tickets/{$ticket->id}",
                    $file
                );
            }
        }

        return $ticket->messages()->create([
            'sender_id'   => $senderId,
            'message'     => $data['message'] ?? null,
            'attachments' => $attachments,
        ]);
    }


    public function resolve($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->status = 'RESOLVED';
        $ticket->save();
        return $ticket;
    }

    public function getByStore(int $storeId, array $filters = [])
    {
        $query = $this->model
            ->where('store_id', $storeId)
            ->with([
                'user',
                'messages',
                'issueType',
                'returnRequest'
            ])
            ->orderBy('created_at', 'desc');

        // فلترة التصعيد (الخاصة بالمدير والمالك)
        // if ($onlyEscalated) {
        //     $query->where('status', 'ESCALATED');
        // }

        // فلترة الحالة
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // فلترة الأولوية
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // فلترة نوع المشكلة
        if (!empty($filters['issue_type_id'])) {
            $query->where('issue_type_id', $filters['issue_type_id']);
        }

        // بحث بالعنوان
        if (!empty($filters['search'])) {
            $query->where('subject', 'LIKE', '%' . $filters['search'] . '%');
        }

        // نطاق التاريخ
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $this->paginate($query);
    }





    public function update(array $data, Ticket $ticket)
    {
        $ticket->update($data);
        return $ticket;
    }

    public function delete(Ticket $ticket)
    {
        return $ticket->delete();
    }

    public function escalate(int $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        if ($ticket->status === 'RESOLVED') {
            return $ticket;
        }

        $ticket->status = 'ESCALATED';
        $ticket->save();

        return $ticket;
    }

    public function getIssueTypes()
    {
        return IssueType::orderByRaw("FIELD(priority, 'high', 'medium', 'low')")->get();
    }

    public function getTicketsForUser(int $userId, array $filters = [])
    {
        $query = $this->model
            ->with(['issueType', 'returnRequest', 'store'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Filter by issue type
        if (!empty($filters['issue_type_id'])) {
            $query->where('issue_type_id', $filters['issue_type_id']);
        }

        // Search by subject
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('subject', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhereHas('messages', function ($messageQuery) use ($filters) {
                      $messageQuery->where('message', 'LIKE', '%' . $filters['search'] . '%');
                  });
            });
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $this->paginate($query);
    }
}
