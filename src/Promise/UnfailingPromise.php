<?php 

namespace TaskChecker\Promise;

use React\Promise\PromiseInterface;
use React\Promise\_checkTypehint;
use React\Promise;

/**
 * Промис, который при любом реджекте выкидывает исключение - 
 * в отличие от обычных промисов, которые его перехватывают.
 * И не перехватывает исключения внутри then() и подобных функций.
 *
 * Обычные промисы имеют недостаток: если реджект не обработан, 
 * то они о нем никак не сообщают. Также, они перехватывают все 
 * исключения внутри then(). Из-за этого легко не узнать о 
 * какой-то ошибке. Этот класс исправляет ситуацию, выкидывая 
 * исключение при реджекте. 
 */
class UnfailingPromise implements PromiseInterface
{
    private $result;
    private $handlers = [];

    public function __construct(callable $resolver)
    {
        $this->call($resolver);
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        if ($onProgress) {
            throw new \InvalidArgumentException("Progress handler is not supported");
        }

        if (null !== $this->result) {
            return $this->result->then($onFulfilled, $onRejected);
        }

        return new static($this->resolver($onFulfilled, $onRejected));
    }

    public function done(callable $onFulfilled = null, callable $onRejected = null)
    {
        if (null !== $this->result) {
            return $this->result->done($onFulfilled, $onRejected);
        }

        $this->handlers[] = function (PromiseInterface $promise) use ($onFulfilled, $onRejected) {
            $promise
                ->done($onFulfilled, $onRejected);
        };

        if ($onProgress) {
            $this->progressHandlers[] = $onProgress;
        }
    }

    public function otherwise(callable $onRejected)
    {
        return $this->then(null, function ($reason) use ($onRejected) {
            if (!_checkTypehint($onRejected, $reason)) {
                return new RejectedPromise($reason);
            }

            return $onRejected($reason);
        });
    }

    public function always(callable $onFulfilledOrRejected)
    {
        return $this->then(function ($value) use ($onFulfilledOrRejected) {
            return Promise\resolve($onFulfilledOrRejected())->then(function () use ($value) {
                return $value;
            });
        }, function ($reason) use ($onFulfilledOrRejected) {
            return Promise\resolve($onFulfilledOrRejected())->then(function () use ($reason) {
                return new RejectedPromise($reason);
            });
        });
    }

    private function resolver(callable $onFulfilled = null, callable $onRejected = null)
    {
        return function ($resolve, $reject) use ($onFulfilled, $onRejected) {

            $this->handlers[] = function (PromiseInterface $promise) use ($onFulfilled, $onRejected, $resolve, $reject) {
                $promise
                    ->then($onFulfilled, $onRejected)
                    ->done($resolve, $reject);
            };
        };
    }

    private function resolve($value = null)
    {
        if (null !== $this->result) {
            return;
        }

        $this->settle(Promise\resolve($value));
    }

    private function reject($reason)
    {
        if (!($reason instanceof \Exception) && 
            !($reason instanceof \Throwable)) {

            throw new \InvalidArgumentException(
                "An argument for reject() must be an exception");
        }

        throw new UnhandledRejectionException($reason);
    }

    private function settle(PromiseInterface $promise)
    {
        $promise = $this->unwrap($promise);

        $handlers = $this->handlers;

        $this->handlers = [];
        $this->result = $promise;

        foreach ($handlers as $handler) {
            $handler($promise);
        }
    }

    private function unwrap($promise)
    {
        $promise = $this->extract($promise);

        while ($promise instanceof self && null !== $promise->result) {
            $promise = $this->extract($promise->result);
        }

        return $promise;
    }

    private function extract($promise)
    {
        if ($promise instanceof LazyPromise) {
            $promise = $promise->promise();
        }

        if ($promise === $this) {
            return new RejectedPromise(
                new \LogicException('Cannot resolve a promise with itself.')
            );
        }

        return $promise;
    }

    private function call(callable $callback)
    {
        $callback(
            function ($value = null) {
                $this->resolve($value);
            },
            function ($reason = null) {
                $this->reject($reason);
            }
        );
    }
}

