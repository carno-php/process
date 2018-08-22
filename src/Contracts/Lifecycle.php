<?php
/**
 * Process lifecycle
 * User: moyo
 * Date: 28/12/2017
 * Time: 11:15 AM
 */

namespace Carno\Process\Contracts;

interface Lifecycle
{
    /**
     * startup new process
     */
    public function startup() : void;

    /**
     * kill process
     */
    public function shutdown() : void;
}
