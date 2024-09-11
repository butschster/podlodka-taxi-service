<?php

declare(strict_types=1);

namespace App\Application\Entity;

/**
 * @template TChange of object
 */
abstract class AbstractAggregate
{
    /** @var iterable<TChange> */
    protected array $changes = [];

    /**
     * @param TChange $change
     */
    protected function markAsChanged(object $change): void
    {
        $this->changes[] = $change;
    }

    /**
     * @return iterable<TChange>
     */
    public function flush(): iterable
    {
        $uniqueChanges = [];
        foreach ($this->changes as $change) {
            $key = \get_class($change) . \spl_object_id($change);
            $uniqueChanges[$key] = $change;
        }
        $this->changes = [];

        return \array_values($uniqueChanges);
    }
}
