<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\Ticket;
use Illuminate\Support\Collection;


interface TicketRepositoryInterface
{
    public function all();

    public function findById($id);

    public function createWithMessage(array $data, Collection $orderItems);

    public function resolve($id);

    public function getByStore(int $storeId);

    public function escalate(int $ticketId);

    public function update(array $data, Ticket $ticket);

    public function delete(Ticket $ticket);

    public function getIssueTypes();

    public function getTicketsForUser(int $userId, array $filters = []);


}
