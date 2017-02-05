<?php
/**
 * profiler
 *
 * @author    Wolfy-J
 */
namespace Spiral\Profiler;

use Monolog\Handler\AbstractHandler;

/**
 * Aggregates log messages into memory.
 */
class DebugHandler extends AbstractHandler
{
    /**
     * @var array
     */
    protected $records = [];

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        $this->records[] = $record;

        //Passing
        return false;
    }

    /**
     * All collected records.
     *
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }
}