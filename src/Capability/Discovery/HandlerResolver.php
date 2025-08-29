<?php

/*
 * This file is part of the official PHP MCP SDK.
 *
 * A collaboration between Symfony and the PHP Foundation.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mcp\Capability\Discovery;

use Mcp\Exception\InvalidArgumentException;

/**
 * Utility class to validate and resolve MCP element handlers.
 *
 * @author Kyrian Obikwelu <koshnawaza@gmail.com>
 */
class HandlerResolver
{
    /**
     * Validates and resolves a handler to a ReflectionMethod or ReflectionFunction instance.
     *
     * A handler can be:
     * - A Closure: function() { ... }
     * - An array: [ClassName::class, 'methodName'] (instance method)
     * - An array: [ClassName::class, 'staticMethod'] (static method, if callable)
     * - A string: InvokableClassName::class (which will resolve to its '__invoke' method)
     *
     * @param \Closure|array{0: string, 1: string}|string $handler the handler to resolve
     *
     * @throws InvalidArgumentException If the handler format is invalid, the class/method doesn't exist,
     *                                  or the method is unsuitable (e.g., private, abstract).
     */
    public static function resolve(\Closure|array|string $handler): \ReflectionMethod|\ReflectionFunction
    {
        if ($handler instanceof \Closure) {
            return new \ReflectionFunction($handler);
        }

        if (\is_array($handler)) {
            if (2 !== \count($handler) || !isset($handler[0]) || !isset($handler[1]) || !\is_string($handler[0]) || !\is_string($handler[1])) {
                throw new InvalidArgumentException('Invalid array handler format. Expected [ClassName::class, \'methodName\'].');
            }
            [$className, $methodName] = $handler;
            if (!class_exists($className)) {
                throw new InvalidArgumentException(\sprintf('Handler class "%s" not found for array handler.', $className));
            }
            if (!method_exists($className, $methodName)) {
                throw new InvalidArgumentException(\sprintf('Handler method "%s" not found in class "%s" for array handler.', $methodName, $className));
            }
        } elseif (class_exists($handler)) {
            $className = $handler;
            $methodName = '__invoke';
            if (!method_exists($className, $methodName)) {
                throw new InvalidArgumentException(\sprintf('Invokable handler class "%s" must have a public "__invoke" method.', $className));
            }
        } else {
            throw new InvalidArgumentException('Invalid handler format. Expected Closure, [ClassName::class, \'methodName\'] or InvokableClassName::class string.');
        }

        try {
            $reflectionMethod = new \ReflectionMethod($className, $methodName);

            // For discovered elements (non-manual), still reject static methods
            // For manual elements, we'll allow static methods since they're callable
            if (!$reflectionMethod->isPublic()) {
                throw new InvalidArgumentException(\sprintf('Handler method "%s::%s" must be public.', $className, $methodName));
            }
            if ($reflectionMethod->isAbstract()) {
                throw new InvalidArgumentException(\sprintf('Handler method "%s::%s" must be abstract.', $className, $methodName));
            }
            if ($reflectionMethod->isConstructor() || $reflectionMethod->isDestructor()) {
                throw new InvalidArgumentException(\sprintf('Handler method "%s::%s" cannot be a constructor or destructor.', $className, $methodName));
            }

            return $reflectionMethod;
        } catch (\ReflectionException $e) {
            // This typically occurs if class_exists passed but ReflectionMethod still fails (rare)
            throw new InvalidArgumentException(\sprintf('Reflection error for handler "%s::%s": %s', $className, $methodName, $e->getMessage()), 0, $e);
        }
    }
}
