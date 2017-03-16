<?php

declare(strict_types = 1);

namespace Rx\React\Tests\Subject;

use Exception;
use Rx\ObserverInterface;
use Rx\React\ProcessSubject;
use Rx\TestCase;

class ProcessSubjectTest extends TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_throws_when_subscribing_to_a_disposed_subject()
    {
        $subject = new ProcessSubject('echo foo');
        $subject->dispose();

        $observer = $this->createMock(ObserverInterface::class);
        $subject->subscribe($observer);
    }

    /**
     * @test
     */
    public function it_exposes_if_it_has_observers()
    {
        $subject = new ProcessSubject('echo foo');

        $this->assertFalse($subject->hasObservers());

        $observer = $this->createMock(ObserverInterface::class);
        $subject->subscribe($observer);
        $this->assertTrue($subject->hasObservers());
    }

    /**
     * @test
     */
    public function it_exposes_if_it_is_disposed()
    {
        $subject = new ProcessSubject('echo foo');

        $this->assertFalse($subject->isDisposed());

        $subject->dispose();
        $this->assertTrue($subject->isDisposed());
    }

    /**
     * @test
     */
    public function it_has_no_observers_after_disposing()
    {
        $subject = new ProcessSubject('cat');

        $observer = $this->createMock(ObserverInterface::class);
        $subject->subscribe($observer);
        $this->assertTrue($subject->hasObservers());

        $subject->dispose();
        $this->assertFalse($subject->hasObservers());
    }

    /**
     * @test
     */
    public function it_returns_true_if_an_observer_is_removed()
    {
        $subject = new ProcessSubject('echo foo');

        $observer = $this->createMock(ObserverInterface::class);
        $subject->subscribe($observer);
        $this->assertTrue($subject->hasObservers());

        $this->assertTrue($subject->removeObserver($observer));
        $this->assertFalse($subject->hasObservers());
    }

    /**
     * @test
     */
    public function it_returns_false_if_an_observer_is_not_subscribed()
    {
        $subject = new ProcessSubject('echo foo');

        $observer = $this->createMock(ObserverInterface::class);

        $this->assertFalse($subject->removeObserver($observer));
        $this->assertFalse($subject->hasObservers());
    }

    /**
     * @test
     */
    public function it_passes_exception_on_subscribe_if_already_stopped()
    {
        $exception = new Exception('fail');
        $subject   = new ProcessSubject('echo foo');
        $subject->onError($exception);

        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('onError')
            ->with($this->equalTo($exception));

        $subject->subscribe($observer);
    }

    /**
     * @test
     */
    public function it_passes_on_complete_on_subscribe_if_already_stopped()
    {
        $subject = new ProcessSubject('echo foo');
        $subject->onCompleted();

        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('onCompleted');

        $subject->subscribe($observer);
    }

    /**
     * @test
     */
    public function it_passes_on_error_if_not_disposed()
    {
        $exception = new Exception('fail');
        $subject   = new ProcessSubject('echo foo');

        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('onError')
            ->with($this->equalTo($exception));

        $subject->subscribe($observer);
        $subject->onError($exception);
    }

    /**
     * @test
     */
    public function it_passes_on_complete_if_not_disposed()
    {
        $subject = new ProcessSubject('echo foo');

        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('onCompleted');

        $subject->subscribe($observer);
        $subject->onCompleted();
    }

    /**
     * @test
     */
    public function it_passes_on_next_if_not_disposed()
    {
        $subject = new ProcessSubject('read value; echo $value');
        $value   = "test\n";

        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('onNext')
            ->with($this->equalTo($value));

        $subject->subscribe($observer);
        $subject->onNext($value);
        \EventLoop\getLoop()->run();
    }

    /**
     * @test
     */
    public function it_does_not_pass_if_already_stopped()
    {
        $subject = new ProcessSubject('echo foo');

        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('onCompleted');

        $observer->expects($this->never())
            ->method('onNext');

        $observer->expects($this->never())
            ->method('onError');

        $subject->subscribe($observer);
        $subject->onCompleted();

        $subject->onError(new Exception('fail'));
        $subject->onNext(42);
        $subject->onCompleted();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_throws_on_error_if_disposed()
    {
        $subject = new ProcessSubject('echo foo');

        $subject->dispose();
        $subject->onError(new Exception('fail'));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_passes_on_complete_if_disposed()
    {
        $subject = new ProcessSubject('echo foo');

        $subject->dispose();
        $subject->onCompleted();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_passes_on_next_if_disposed()
    {
        $subject = new ProcessSubject('echo foo');
        $value   = 42;

        $subject->dispose();
        $subject->onNext($value);
    }

    /**
     * @test
     */
    public function it_does_emits_errors_on_observer()
    {
        $errors  = $this->createMock(ObserverInterface::class);
        $subject = new ProcessSubject('somebadcommand', $errors);

        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('onCompleted');

        $observer->expects($this->never())
            ->method('onNext');

        $observer->expects($this->never())
            ->method('onError');

        $errors->expects($this->once())
            ->method('onNext')
            ->with($this->equalTo(new Exception("sh: somebadcommand: command not found\n")));

        $subject->subscribe($observer);
        \EventLoop\getLoop()->run();
    }
}
