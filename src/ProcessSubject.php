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
    /** @var  Process */
    private $process;
    private $loop;
    private $errorObserver;
    private $cmd;
    private $cwd;
    private $env;
    private $options;

    public function __construct(string $cmd, ObserverInterface $errorObserver = null, string $cwd = null, array $env = null, array $options = [], LoopInterface $loop = null)
    {
        $this->cmd           = $cmd;
        $this->cwd           = $cwd;
        $this->env           = $env;
        $this->options       = $options;
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
            if ($this->process && $this->process->isRunning()) {
                return new CallbackDisposable(function () use ($observer) {
                    $this->removeObserver($observer);
                    if (empty($this->observers) && $this->process && $this->process->isRunning()) {
                        $this->process->terminate();
                    }
                });
            }

            $this->process = $process = new Process($this->cmd, $this->cwd, $this->env, $this->options);
            $this->process->start($this->loop);

            $this->process->stdout->on('data', function ($output) {
                parent::onNext($output);
            });

            $this->process->stderr->on('data', function ($output) use ($observer) {
                if ($output) {
                    $this->errorObserver->onNext(new \Exception($output));
                }
            });

            $this->process->stdout->on('close', function () {
                if (!$this->isDisposed()) {
                    foreach ($this->observers as $observer) {
                        $observer->onCompleted();
                    }
                }
            });
        } catch (\Throwable $e) {
            parent::onError($e);
        }

        return new CallbackDisposable(function () use ($observer) {
            $this->removeObserver($observer);
            if (empty($this->observers)&& $this->process && $this->process->isRunning()) {
                $this->process->terminate();
            }
        });
    }

    public function dispose()
    {
        parent::dispose();
        if ($this->process && $this->process->isRunning()) {
            $this->process->terminate();
        }
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
