<?php
/**
 * Progress statusing
 * User: moyo
 * Date: 03/02/2018
 * Time: 2:27 PM
 */

namespace Carno\Process;

class Progress
{
    /**
     * @var array
     */
    private static $named = [];

    /**
     * @param int $pid
     * @param string $name
     */
    public static function started(int $pid, string $name = null) : void
    {
        $name && self::$named[$pid] = $name;

        logger('process')->info('Process started', [
            'pid' => $pid,
            'name' => $name ?? (self::$named[$pid] ?? 'unknown'),
        ]);
    }

    /**
     * @param int $pid
     * @param int $sig
     * @param int $code
     * @param string $name
     */
    public static function exited(int $pid, int $sig, int $code, string $name = null) : void
    {
        $name && self::$named[$pid] = $name;

        logger('process')->info('Process exited', [
            'pid' => $pid,
            'signal' => $sig,
            'code' => $code,
            'name' => $name ?? (self::$named[$pid] ?? 'unknown'),
        ]);
    }
}
