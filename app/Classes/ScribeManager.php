<?php

namespace App\Classes;

class ScribeManager
{
    public const GROUP_ADMIN_AUTHENTICATE = 'Admin Authenticate';

    // Client
    public const GROUP_CLIENT_AUTHENTICATE = 'Client Authenticate';

    public const GROUP_TRANSACTIONS = 'Transactions';
    public const GROUP_PROFILE = 'Profile';
    // User
    public const GROUP_SERVICE = 'Services';

    public const GROUP_EVENT_TICKET = 'Event Tickets';

    public const GROUP_USER_AUTHENTICATE = 'User Authenticate';

    public const GROUP_SETTINGS = 'Settings';

    public const GROUP_WEARABLE = 'Wearable';

    public const GROUP_PRODUCT = 'Product';
    public const GROUP_WAITLIST = 'Waitlist';

    public const GROUP_CART = 'Cart';

    public const GROUP_LOCATION = 'Location';

    public const RESPONSE_FILE_401 = 'resources/scribe/responses/401.json';
}
