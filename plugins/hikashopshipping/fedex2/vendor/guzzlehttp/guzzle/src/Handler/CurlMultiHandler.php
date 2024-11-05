<?php

namespace GuzzleHttp\Handler;

use Closure;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Utils;
use Psr\Http\Message\RequestInterface;

class CurlMultiHandler
{
    private $factory;

    private $selectTimeout;

    private $active = 0;

    private $handles = [];

    private $delays = [];

    private $options = [];


    private $_mh;

    public function __construct(array $options = [])
    {
        $this->factory = $options['handle_factory'] ?? new CurlFactory(50);

        if (isset($options['select_timeout'])) {
            $this->selectTimeout = $options['select_timeout'];
        } elseif ($selectTimeout = Utils::getenv('GUZZLE_CURL_SELECT_TIMEOUT')) {
            @trigger_error('Since guzzlehttp/guzzle 7.2.0: Using environment variable GUZZLE_CURL_SELECT_TIMEOUT is deprecated. Use option "select_timeout" instead.', \E_USER_DEPRECATED);
            $this->selectTimeout = (int) $selectTimeout;
        } else {
            $this->selectTimeout = 1;
        }

        $this->options = $options['options'] ?? [];

        unset($this->_mh);
    }

    public function __get($name)
    {
        if ($name !== '_mh') {
            throw new \BadMethodCallException("Can not get other property as '_mh'.");
        }

        $multiHandle = \curl_multi_init();

        if (false === $multiHandle) {
            throw new \RuntimeException('Can not initialize curl multi handle.');
        }

        $this->_mh = $multiHandle;

        foreach ($this->options as $option => $value) {
            curl_multi_setopt($this->_mh, $option, $value);
        }

        return $this->_mh;
    }

    public function __destruct()
    {
        if (isset($this->_mh)) {
            \curl_multi_close($this->_mh);
            unset($this->_mh);
        }
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $easy = $this->factory->create($request, $options);
        $id = (int) $easy->handle;

        $promise = new Promise(
            [$this, 'execute'],
            function () use ($id) {
                return $this->cancel($id);
            }
        );

        $this->addRequest(['easy' => $easy, 'deferred' => $promise]);

        return $promise;
    }

    public function tick(): void
    {
        if ($this->delays) {
            $currentTime = Utils::currentTime();
            foreach ($this->delays as $id => $delay) {
                if ($currentTime >= $delay) {
                    unset($this->delays[$id]);
                    \curl_multi_add_handle(
                        $this->_mh,
                        $this->handles[$id]['easy']->handle
                    );
                }
            }
        }

        P\Utils::queue()->add(Closure::fromCallable([$this, 'tickInQueue']));

        P\Utils::queue()->run();

        if ($this->active && \curl_multi_select($this->_mh, $this->selectTimeout) === -1) {
            \usleep(250);
        }

        while (\curl_multi_exec($this->_mh, $this->active) === \CURLM_CALL_MULTI_PERFORM) {
            \curl_multi_select($this->_mh, $this->selectTimeout);
        }

        $this->processMessages();
    }

    private function tickInQueue(): void
    {
        if (\curl_multi_exec($this->_mh, $this->active) === \CURLM_CALL_MULTI_PERFORM) {
            \curl_multi_select($this->_mh, 0);
            P\Utils::queue()->add(Closure::fromCallable([$this, 'tickInQueue']));
        }
    }

    public function execute(): void
    {
        $queue = P\Utils::queue();

        while ($this->handles || !$queue->isEmpty()) {
            if (!$this->active && $this->delays) {
                \usleep($this->timeToNext());
            }
            $this->tick();
        }
    }

    private function addRequest(array $entry): void
    {
        $easy = $entry['easy'];
        $id = (int) $easy->handle;
        $this->handles[$id] = $entry;
        if (empty($easy->options['delay'])) {
            \curl_multi_add_handle($this->_mh, $easy->handle);
        } else {
            $this->delays[$id] = Utils::currentTime() + ($easy->options['delay'] / 1000);
        }
    }

    private function cancel($id): bool
    {
        if (!is_int($id)) {
            trigger_deprecation('guzzlehttp/guzzle', '7.4', 'Not passing an integer to %s::%s() is deprecated and will cause an error in 8.0.', __CLASS__, __FUNCTION__);
        }

        if (!isset($this->handles[$id])) {
            return false;
        }

        $handle = $this->handles[$id]['easy']->handle;
        unset($this->delays[$id], $this->handles[$id]);
        \curl_multi_remove_handle($this->_mh, $handle);
        \curl_close($handle);

        return true;
    }

    private function processMessages(): void
    {
        while ($done = \curl_multi_info_read($this->_mh)) {
            if ($done['msg'] !== \CURLMSG_DONE) {
                continue;
            }
            $id = (int) $done['handle'];
            \curl_multi_remove_handle($this->_mh, $done['handle']);

            if (!isset($this->handles[$id])) {
                continue;
            }

            $entry = $this->handles[$id];
            unset($this->handles[$id], $this->delays[$id]);
            $entry['easy']->errno = $done['result'];
            $entry['deferred']->resolve(
                CurlFactory::finish($this, $entry['easy'], $this->factory)
            );
        }
    }

    private function timeToNext(): int
    {
        $currentTime = Utils::currentTime();
        $nextTime = \PHP_INT_MAX;
        foreach ($this->delays as $time) {
            if ($time < $nextTime) {
                $nextTime = $time;
            }
        }

        return ((int) \max(0, $nextTime - $currentTime)) * 1000000;
    }
}
