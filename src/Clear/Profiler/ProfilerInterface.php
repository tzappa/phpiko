<?php

declare(strict_types=1);

namespace Clear\Profiler;

/**
 * Profiler interface describes 2 mandatory methods which are used
 * to monitor performance of the application parts. As example with
 * this profiler you can check SQL execution times for all queries
 * to the database.
 */
interface ProfilerInterface
{
    /**
     * Starts a profile entry.
     *
     * @param string $label The label starting the profile entry.
     */
    public function start(string $label);

    /**
     * Finishes and logs a profile entry.
     *
     * @param string $message The message you wish to add, if any.
     * @param array  $values The values bound to the message, if any.
     */
    public function finish(string $message = '', array $values = []): void;
}
