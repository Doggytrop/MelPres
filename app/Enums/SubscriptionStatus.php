<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';
}
