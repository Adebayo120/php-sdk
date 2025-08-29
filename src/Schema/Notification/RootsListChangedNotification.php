<?php

/*
 * This file is part of the official PHP MCP SDK.
 *
 * A collaboration between Symfony and the PHP Foundation.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mcp\Schema\Notification;

use Mcp\Exception\InvalidArgumentException;
use Mcp\Schema\JsonRpc\Notification;

/**
 * A notification from the client to the server, informing it that the list of roots has changed.
 * This notification should be sent whenever the client adds, removes, or modifies any root.
 * The server should then request an updated list of roots using the ListRootsRequest.
 *
 * @author Kyrian Obikwelu <koshnawaza@gmail.com>
 */
class RootsListChangedNotification extends Notification
{
    /**
     * @param array<string, mixed>|null $_meta
     */
    public function __construct(
        public readonly ?array $_meta = null,
    ) {
        $params = [];
        if (null !== $_meta) {
            $params['_meta'] = $_meta;
        }

        parent::__construct('notifications/roots/list_changed', $params);
    }

    public static function fromNotification(Notification $notification): self
    {
        if ('notifications/roots/list_changed' !== $notification->method) {
            throw new InvalidArgumentException('Notification is not a notifications/roots/list_changed notification');
        }

        return new self($notification->params['_meta'] ?? null);
    }
}
