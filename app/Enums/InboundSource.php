<?php

namespace App\Enums;

/**
 * Namespaces accepted from authenticated, trusted channel boundaries.
 */
enum InboundSource: string
{
    case WEB_TEST = 'web_test';
    case TRUSTED_CHANNEL = 'trusted_channel';
}
