<?php

namespace Base\Scholar\Model;

/**
 * What one sync did for one scholar: works new (waiting to be validated,
 * or online at once when the scholar says so), brought up to date, found
 * unchanged, the CV lines read, the sources that did not answer - a sync
 * with a source missing is never taken for "that work is gone".
 */
final class SyncSummary
{
    public int $read = 0;
    public int $created = 0;
    public int $updated = 0;
    public int $unchanged = 0;
    /** Works the scholar rejected once, found again: they stay rejected. */
    public int $ignored = 0;
    public int $cv = 0;

    /** @var array<string, string> source => why it was skipped */
    public array $incomplete = [];

    /** @var list<string> */
    public array $errors = [];

    public function isComplete(): bool
    {
        return [] === $this->incomplete && [] === $this->errors;
    }

    /** @return array{read: int, created: int, updated: int, unchanged: int, ignored: int, cv: int, incomplete: array<string, string>, errors: list<string>} */
    public function toArray(): array
    {
        return [
            'read' => $this->read,
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'ignored' => $this->ignored,
            'cv' => $this->cv,
            'incomplete' => $this->incomplete,
            'errors' => $this->errors,
        ];
    }
}
