<?php 

namespace TaskChecker\Promise;

class UnfailingDeferred implements PromisorInterface
{
    private $promise;
    private $resolveCallback;
    private $rejectCallback;

    public function promise()
    {
        if (null === $this->promise) {
            $this->promise = new UnfailingPromise(function ($resolve, $reject) {
                $this->resolveCallback = $resolve;
                $this->rejectCallback  = $reject;
            });
        }

        return $this->promise;
    }

    public function resolve($value = null)
    {
        $this->promise();

        call_user_func($this->resolveCallback, $value);
    }

    public function reject($reason = null)
    {
        $this->promise();

        call_user_func($this->rejectCallback, $reason);
    }
}