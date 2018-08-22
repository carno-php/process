<?php
/**
 * Master process
 * User: moyo
 * Date: 28/12/2017
 * Time: 12:08 PM
 */

namespace Carno\Process;

use Swoole\Process as SWProcess;

class Master
{
    /**
     * @var bool
     */
    private static $watched = false;

    /**
     * @var array
     */
    private static $exited = [];

    /**
     * @param int $pid
     */
    public static function watch(int $pid) : void
    {
        self::$watched || self::$watched = SWProcess::signal(SIGCHLD, function () {
            while ($ex = SWProcess::wait(false)) {
                Progress::exited($ex['pid'], $ex['signal'], $ex['code']);
            }
        });
    }

    /**
     * @param int $pid
     */
    public static function wait(int $pid) : void
    {
        if (isset(self::$exited[$pid])) {
            Progress::exited(...self::$exited[$pid]);
        } else {
            if (false !== $ex = SWProcess::wait(true)) {
                if ($ex['pid'] === $pid) {
                    Progress::exited($ex['pid'], $ex['signal'], $ex['code']);
                } else {
                    self::$exited[$ex['pid']] = [$ex['pid'], $ex['signal'], $ex['code']];
                }
            }
        }
    }
}
