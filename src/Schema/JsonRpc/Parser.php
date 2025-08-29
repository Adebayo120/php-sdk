<?php

/*
 * This file is part of the official PHP MCP SDK.
 *
 * A collaboration between Symfony and the PHP Foundation.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mcp\Schema\JsonRpc;

use Mcp\Exception\InvalidArgumentException;
use Mcp\Schema\Constants;

/**
 * @author Kyrian Obikwelu <koshnawaza@gmail.com>
 */
class Parser
{
    /**
     * Parses a raw JSON string into a JSON-RPC Message object (Request, Notification, Response, Error, or Batch variants).
     *
     * This method determines if the incoming message is a request-like message (Request, Notification, BatchRequest)
     * or a response-like message (Response, Error, BatchResponse) based on the presence of 'method' vs 'result'/'error'.
     *
     * @param string $json the raw JSON string to parse
     *
     * @return MessageInterface a specific instance of Request, Notification, Response, Error, BatchRequest, or BatchResponse
     *
     * @throws \JsonException           if the string is not valid JSON
     * @throws InvalidArgumentException if the JSON structure does not conform to a recognizable JSON-RPC message type
     */
    public static function parse(string $json): MessageInterface
    {
        $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($data)) {
            throw new InvalidArgumentException('Invalid JSON-RPC message: Root must be an object or array.');
        }

        if (array_is_list($data) && !empty($data)) {
            $firstItem = $data[0];
            if (!\is_array($firstItem)) {
                throw new InvalidArgumentException('Invalid JSON-RPC batch: Items must be objects.');
            }

            if (isset($firstItem['method'])) {
                return BatchRequest::fromArray($data);
            } elseif (isset($firstItem['id']) && (isset($firstItem['result']) || isset($firstItem['error']))) {
                return BatchResponse::fromArray($data);
            } else {
                throw new InvalidArgumentException('Invalid JSON-RPC batch: Items are not recognizable requests or responses.');
            }
        }

        if (!isset($data['jsonrpc']) || Constants::JSONRPC_VERSION !== $data['jsonrpc']) {
            throw new InvalidArgumentException('Invalid or missing "jsonrpc" version. Must be "'.Constants::JSONRPC_VERSION.'".');
        }

        if (isset($data['method'])) {
            if (isset($data['id'])) {
                return Request::fromArray($data);
            } else {
                return Notification::fromArray($data);
            }
        } elseif (isset($data['id'])) {
            if (\array_key_exists('result', $data)) {
                return Response::fromArray($data);
            } elseif (isset($data['error'])) {
                return Error::fromArray($data);
            } else {
                throw new InvalidArgumentException('Invalid JSON-RPC response/error: Missing "result" or "error" field for message with "id".');
            }
        }

        throw new InvalidArgumentException('Unrecognized JSON-RPC message structure.');
    }
}
