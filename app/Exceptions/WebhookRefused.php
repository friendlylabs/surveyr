<?php

namespace App\Exceptions;

use Exception;

/**
 * WebhookRefused
 *
 * A webhook that won't be sent however often it's tried: its address isn't http(s), can't be found, or points into a
 * private or local network (App\Support\Webhooks::target). Its message is the reason, shown with the delivery.
 */
class WebhookRefused extends Exception {}
