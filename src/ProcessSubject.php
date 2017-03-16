<?php

namespace Rx\React;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use Rx\Disposable\CallbackDisposable;
use Rx\Disposable\EmptyDisposable;
use Rx\DisposableInterface;
use Rx\ObserverInterface;
use Rx\Subject\Subject;

class ProcessSubject extends Subject
{
    private $process;
    private $loop;
    private $errorObserver;

    public function __construct(string $cmd, ObserverInterface $errorObserver = null, string $cwd = null, array $env = null, array $options = [], LoopInterface $loop = null)
    {
        $this->process       = $process = new Process($cmd, $cwd, $env, $options);
        $this->loop          = $loop ?: \EventLoop\getLoop();
        $this->errorObserver = $errorObserver ?: new Subject();
    }

    public function onNext($data)
    {
        $this->assertNotDisposed();

        if ($this->process->stdin) {
            $this->process->stdin->write($data);
        }
    }

    public function _subscribe(ObserverInterface $observer): DisposableInterface
    {
        parent::_subscribe($observer);

        try {
            if ($this->process->isRunning()) {
                return new EmptyDisposable();
            }
            $this->process->start($this->loop);

            $this->process->stdout->on('data', function ($output) {
                parent::onNext($output);
            });

            $this->process->stderr->on('data', function ($output) use ($observer) {
                if ($output) {
                    $this->errorObserver->onNext(new \Exception($output));
                }
            });

            $this->process->stdout->on('close', function () use ($observer) {
                if (!$this->isDisposed()) {
                    parent::onCompleted();
                }
            });
        } catch (\Throwable $e) {
            parent::onError($e);
        }

        return new CallbackDisposable(function () use ($observer) {
            $this->removeObserver($observer);
            if (empty($this->observers)) {
                $this->process->terminate();
            }
        });
    }

    public function dispose()
    {
        parent::dispose();
        if ($this->process->isRunning()) {
            $this->process->terminate();
        }
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
