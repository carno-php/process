<?php
/**
 * Forking daemon
 * User: moyo
 * Date: 28/12/2017
 * Time: 11:17 AM
 */

namespace Carno\Process\Chips;

use Carno\Process\Master;
use Carno\Process\Piping;
use Carno\Process\Program;
use Carno\Process\Progress;
use Carno\Promise\Promise;
use Swoole\Process as SWProcess;
use Swoole\Server as SWServer;
use Closure;
use Throwable;

trait Forking
{
    /**
     * @var Piping
     */
    private $forked = null;

    /**
     * @var bool
     */
    private $started = false;

    /**
     * @var Closure[]
     */
    private $events = [];

    /**
     * @var SWProcess
     */
    private $process = null;

    /**
     * @var bool
     */
    private $exiting = false;

    /**
     * @param int $event
     * @param Closure ...$actions
     */
    final public function on(int $event, Closure ...$actions) : void
    {
        $this->events[$event] = $actions;
    }

    /**
     * @return SWProcess
     */
    final public function process() : ?SWProcess
    {
        return $this->process;
    }

    /**
     * @return bool
     */
    final public function started() : bool
    {
        return $this->started;
    }

    /**
     * startup new process
     * @param object $server
     */
    final public function startup(object $server = null) : void
    {
        $this->process = new SWProcess(function (SWProcess $process) {
            // set process name
            if ($this->name) {
                @$process->name(sprintf('[process] %s', $this->name));
            }

            // progress notify
            Progress::started(posix_getpid(), $this->name);

            // start pipe reading
            $this->forked->reading();

            // trigger system startup
            $this->forked->bootstrap();

            // trigger event::program::started
            $this->action(Program::STARTED);

            // trigger user starting
            $this->starting();
        });

        // running
        $server instanceof SWServer ? $server->addProcess($this->process) : Master::watch($this->process->start());

        // flag
        $this->started = true;
    }

    /**
     * shutdown process
     * @param int $sig
     */
    final public function shutdown(int $sig = 0) : void
    {
        // check exiting state
        if ($this->exiting) {
            return;
        }

        $this->exiting = true;

        // be wait
        $wait = Promise::deferred();

        // user-land stopping
        $this->stopping($wait);

        // trigger event::program::stopped
        $this->action(Program::STOPPED);

        // waiting
        $wait->then(function () use ($sig) {
            $this->exited($sig);
        }, function (Throwable $e) use ($sig) {
            $this->exited($sig, $e);
        });
    }

    /**
     * @param int $sig
     * @param Throwable $e
     */
    final private function exited(int $sig = 0, Throwable $e = null) : void
    {
        Progress::exited(posix_getpid(), $sig, $e ? 1 : 0, $this->name);

        $this->process->exit();
    }

    /**
     * @param int $event
     */
    final private function action(int $event) : void
    {
        foreach ($this->events[$event] ?? [] as $program) {
            $program($this->forked);
        }
    }
}
