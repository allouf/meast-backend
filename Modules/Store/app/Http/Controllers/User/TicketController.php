<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\User\CreateReturnTicketRequest;
use Modules\Store\Http\Requests\User\StoreTicketRequest;
use Modules\Store\Http\Resources\User\IssueTypeResource;
use Modules\Store\Http\Resources\User\ListTicketResource;
use Modules\Store\Http\Resources\User\TicketResource;
use Modules\Store\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}
    public function index(Request $request)
    {
        try {
            $userId = auth()->id();
            
            $filters = $request->only([
                'search',
                'status',
                'priority',
                'issue_type_id',
                'date_from',
                'date_to',
                'per_page'
            ]);

            $tickets = $this->ticketService->getMyTickets($userId, $filters);

            if (is_array($tickets) && isset($tickets['data'])) {
                return $this->successResponse(
                    "My tickets fetched successfully",
                    [
                        'tickets' => ListTicketResource::collection($tickets['data']),
                        'meta' => $tickets['meta']
                    ]
                );
            }

            return $this->successResponse(
                "My tickets fetched successfully",
                ListTicketResource::collection($tickets)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function show($id)
    {
        try {
            $ticket = $this->ticketService->findDetailedTicket($id);
            return $this->successResponse(
                "Ticket details fetched successfully",
                new TicketResource($ticket)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function getIssueTypes()
    {
        try {
            $types = $this->ticketService->getIssueTypes();
            return $this->successResponse(
                "Issue types fetched successfully",
                IssueTypeResource::collection($types)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function store(StoreTicketRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        try {
            $ticket = $this->ticketService->openTicket($data);
            return $this->successResponse(
                "Ticket created successfully",
                new TicketResource($ticket->load(['messages', 'user']))
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
