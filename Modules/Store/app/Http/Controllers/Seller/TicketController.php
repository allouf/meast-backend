<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\IssueTypeResource;
use Modules\Store\Http\Resources\User\TicketResource;
use Modules\Store\Services\TicketService;
use Illuminate\Http\Request;
use Modules\Store\Http\Resources\User\ListTicketResource;
use Modules\Store\Http\Requests\Seller\ReplyTicketRequest;


class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService,
    ) {}


    public function index(Request $request, $storeId)
    {

        try {

            $filters = $request->only([
                'status',
                'priority',
                'issue_type_id',
                'search',
                'date_from',
                'date_to'
            ]);

            $tickets = $this->ticketService->getStoreTickets($storeId, $filters);

            return $this->successResponse(
                "Tickets fetched successfully",
                [
                    'tickets' =>  ListTicketResource::collection($tickets['data']),
                    'meta' => $tickets['meta']
                ]
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }


    public function reply(ReplyTicketRequest $request, $id)
    {

        try {

            $ticket = $this->ticketService->replyToTicket($id, $request->validated());

            return $this->successResponse(
                "Reply added successfully",
                new TicketResource($ticket)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function resolve($ticketId)
    {
        $ticket = $this->ticketService->resolveTicket($ticketId);

        return $this->successResponse("Ticket resolved successfully", new TicketResource($ticket));
    }

    public function escalate($ticketId)
    {
        $ticket = $this->ticketService->escalateTicket($ticketId);

        return $this->successResponse(
            "Ticket escalated successfully",
            new TicketResource($ticket)
        );
    }

    public function show($id)
    {
        $ticket = $this->ticketService->findDetailedTicket($id);

        return $this->successResponse(
            "Ticket details fetched successfully",
            new TicketResource($ticket)
        );
    }


    public function getIssueTypes()
    {
        try {
            $types = $this->ticketService->getIssueTypes();

            return $this->successResponse(
                __('store::messages.issue_types.fetched_successfully'),
                IssueTypeResource::collection($types)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function reviewReturn(Request $request, $ticketId)
    {
        try {
            $ticket = $this->ticketService->reviewReturn($ticketId, $request->all());

            return $this->successResponse(
                "Return request is now under review",
                new TicketResource($ticket)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function decideReturn(Request $request, $ticketId)
    {
        try {
            $data = $request->validate([
                'decision' => 'required|in:APPROVED,DENIED',
                'note'     => 'nullable|string|max:500',
            ]);

            $ticket = $this->ticketService->decideReturn(
                $ticketId,
                $data['decision'],
                $data['note'] ?? null
            );

            return $this->successResponse(
                "Return request has been {$data['decision']}",
                new TicketResource($ticket)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }


    public function processRefund(Request $request, $ticketId)
    {
        try {
            $data = $request->validate([
                'note' => 'nullable|string|max:500',
            ]);

            $this->ticketService->processRefund(
                $ticketId,
                $data['note'] ?? null
            );

            return $this->successResponse(
                "Refund has been processed successfully"
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function markReplacementSent(Request $request, $ticketId)
    {
        try {
            $data = $request->validate([
                'note' => 'nullable|string|max:500',
            ]);

            $ticket = $this->ticketService->markReplacementSent(
                $ticketId,
                $data['note'] ?? null
            );

            return $this->successResponse(
                "Replacement has been marked as sent",
                new TicketResource($ticket)
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
