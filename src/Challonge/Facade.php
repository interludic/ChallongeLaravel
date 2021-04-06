<?php

namespace Interludic\Challonge;

/**
 * This is the challonge facade class.
 *
 */

class Facade extends \Illuminate\Support\Facades\Facade

{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Challonge::class;
    }
}
