<?php

namespace Zoho\Crm\Traits;

/**
 * Basic implementation for PaginatedQueryInterface.
 */
trait HasPagination
{
    /** @var bool Whether the query must be automatically paginated or not */
    protected $autoPaginated = false;

    /** @var int|null The maximum number of concurrent requests allowed to fetch pages */
    protected $concurrency;

    /**
     * @inheritdoc
     */
    public function mustBePaginatedAutomatically(): bool
    {
        return $this->autoPaginated;
    }

    /**
     * @inheritdoc
     */
    public function mustBePaginatedConcurrently(): bool
    {
        return isset($this->concurrency) && $this->concurrency > 1;
    }

    /**
     * @inheritdoc
     */
    public function getConcurrency(): ?int
    {
        return $this->concurrency;
    }

    /**
     * Turn on/off automatic pagination for the query.
     *
     * If enabled, the pages will be automatically fetched on query execution.
     *
     * @param bool $enabled (optional) Whether the query is auto paginated, true if omitted
     * @return $this
     */
    public function autoPaginated(bool $enabled = true): self
    {
        $this->autoPaginated = $enabled;

        return $this;
    }

    /**
     * Set the concurrency limit for asynchronous pagination.
     *
     * @param int|null $concurrency The concurrency limit
     * @return $this
     */
    public function concurrency(?int $concurrency): self
    {
        if (! is_null($concurrency) && (! is_int($concurrency) || $concurrency <= 0)) {
            throw new \InvalidArgumentException('Query concurrency must be a positive non-zero integer.');
        }

        $this->concurrency = $concurrency;

        return $this;
    }
}
