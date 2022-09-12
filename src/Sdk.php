<?php

namespace STS\HubSpot;

use Illuminate\Support\Str;
use STS\HubSpot\Crm\Call;
use STS\HubSpot\Crm\Company;
use STS\HubSpot\Crm\Contact;
use STS\HubSpot\Crm\Deal;
use STS\HubSpot\Crm\Email;
use STS\HubSpot\Crm\Feedback;
use STS\HubSpot\Crm\Items;
use STS\HubSpot\Crm\Meeting;
use STS\HubSpot\Crm\Note;
use STS\HubSpot\Crm\Product;
use STS\HubSpot\Crm\Quote;
use STS\HubSpot\Crm\Task;
use STS\HubSpot\Crm\Ticket;

class Sdk
{
    protected array $models = [
        'companies' => Company::class,
        'contacts' => Contact::class,
        'deals' => Deal::class,
        'feedback_submissions' => Feedback::class,
        'line_items' => Items::class,
        'products' => Product::class,
        'tickets' => Ticket::class,
        'quotes' => Quote::class,
        'calls' => Call::class,
        'emails' => Email::class,
        'meetings' => Meeting::class,
        'notes' => Note::class,
        'tasks' => Task::class
    ];

    public function isType($type)
    {
        return array_key_exists($type, $this->models);
    }

    public function getModel($type)
    {
        return $this->models[$type];
    }
}