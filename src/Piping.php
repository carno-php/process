<?php
/**
 * Process communication pipe
 * User: moyo
 * Date: 28/12/2017
 * Time: 11:13 AM
 */

namespace Carno\Process;

use Carno\Process\Chips\Forking;
use Swoole\Process as SWProcess;
use Throwable;

class Piping
{
    /**
     * @var Program
     */
    private $backend = null;

    /**
     * Piping constructor.
     * @param Program $backend
     */
    public function __construct(Program $backend)
    {
        $this->backend = $backend->forked($this);
    }

    /**
     * process bootstrap
     */
    final public function bootstrap() : void
    {
        foreach ([SIGINT, SIGTERM] as $sig) {
            SWProcess::signal($sig, function (int $sig) {
                $this->backend->shutdown($sig);
            });
        }
    }

    /**
     * message receiving
     */
    final public function reading() : void
    {
        swoole_event_add($this->backend->process()->pipe, function () {
            $recv = $this->backend->process()->read();
            try {
                list($name, $arguments) = unserialize($recv);
                $this->backend->$name(...$arguments);
            } catch (Throwable $e) {
                logger('process')->notice('Dispatch failed', ['err' => get_class($e), 'msg' => $e->getMessage()]);
            }
        });
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    final public function __call(string $name, array $arguments = [])
    {
        if (null !== $process = $this->backend->process()) {
            return $process->write(serialize([$name, $arguments]));
        } else {
            return $this->backend->$name(...$arguments);
        }
    }

    /**
     * call from master/parent and will redirect to child-process
     * @see Forking::shutdown
     */
    final public function shutdown() : void
    {
        $this->__call('shutdown');
        Master::wait($this->backend->process()->pid ?? -2);
    }
}
