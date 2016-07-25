<?php

namespace Rx\React;

use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\Timer;
use Rx\Disposable\CallbackDisposable;
use Rx\ObserverInterface;
use Rx\Subject\Subject;

class ProcessSubject extends Subject
{
    private $process;
    private $loop;
    private $errorObserver;

    public function __construct($cmd, ObserverInterface $errorObserver = null, $cwd = null, array $env = null, array $options = [], LoopInterface $loop = null)
    {
        $this->process       = $process = new Process($cmd, $cwd, $env, $options);
        $this->loop          = $loop ?: \EventLoop\getLoop();
        $this->errorObserver = $errorObserver ?: new Subject();
    }

    /**
     * @param $data
     */
    public function onNext($data)
    {
        if ($this->process->stdin) {
            $this->process->stdin->write($data);
        }
    }

    /**
     * @param ObserverInterface $observer
     * @param null $scheduler
     * @return CallbackDisposable
     */
    public function subscribe(ObserverInterface $observer, $scheduler = null)
    {
        parent::subscribe($observer, $scheduler);

        $this->loop->addTimer(0.001, function (Timer $timer) use ($observer) {

            try {
                if ($this->process->isRunning()) {
                    return;
                }
                $this->process->start($timer->getLoop());

                $this->process->stdout->on('data', function ($output) {
                    parent::onNext($output);
                });

                $this->process->stderr->on('data', function ($output) use ($observer) {
                    if ($output)
                        $this->errorObserver->onNext(new \Exception($output));
                });

                $this->process->stdout->on('close', function ($output) use ($observer) {
                    parent::onCompleted();
                });
            } catch (\Exception $e) {
                parent::onError($e);
            }
        });

        return new CallbackDisposable(function () use ($observer) {
            $this->removeObserver($observer);
            if (empty($this->observers)) $this->process->terminate();
        });
    }

    public function dispose()
    {
        parent::dispose();
        $this->process->terminate();
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
