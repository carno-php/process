<?php
/**
 * Program base
 * User: moyo
 * Date: 27/12/2017
 * Time: 6:10 PM
 */

namespace Carno\Process;

use Carno\Process\Chips\Forking;
use Carno\Process\Contracts\Lifecycle;
use Carno\Promise\Promised;

abstract class Program implements Lifecycle
{
    use Forking;

    // use ON process started
    public const STARTED = 0xE1;

    // use ON process stopped
    public const STOPPED = 0xE9;

    /**
     * @var string
     */
    protected $name = null;

    /**
     * @return string
     */
    final public function name() : string
    {
        return $this->name;
    }

    /**
     * @return static
     */
    final public function fork()
    {
        return $this->forked ?? $this->forked = new Piping($this);
    }

    /**
     * @param Piping $piping
     * @return static
     */
    final public function forked(Piping $piping) : self
    {
        $this->forking($piping);
        return $this;
    }

    /**
     * triggered when process forking (still in parent process)
     * @param Piping $piping
     */
    abstract protected function forking(Piping $piping) : void;

    /**
     * triggered when process started (in child process)
     */
    abstract protected function starting() : void;

    /**
     * triggered when process exiting (in child process)
     * @param Promised $wait
     */
    abstract protected function stopping(Promised $wait) : void;
}
