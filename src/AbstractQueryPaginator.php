<?php

namespace Zoho\Crm;

/**
 * Base but incomplete query paginator implementation.
 */
abstract class AbstractQueryPaginator implements Contracts\QueryPaginatorInterface
{
    /** @var int The maximum number of items per page */
    const PAGE_MAX_SIZE = 200;

    /** @var Contracts\PaginatedQueryInterface The parent query */
    protected $query;

    /** @var Response[] The responses that have been retrieved so far */
    protected $responses = [];

    /** @var bool Whether there is still data to fetch */
    protected $hasMoreData = true;

    /** @var int The number of pages fetched */
    protected $fetchCount = 0;

    /**
     * The constructor.
     *
     * @param Contracts\PaginatedQueryInterface $query The parent query
     */
    public function __construct(Contracts\PaginatedQueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * @inheritdoc
     *
     * @return \Zoho\Crm\Response[]
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    /**
     * Check if there is more data to fetch.
     *
     * There is no actual check, so if it returns true, it only means
     * that as far as we know, we have not fetched the last record/page yet.
     * The value is updated after each fetch.
     *
     * @return bool
     */
    public function hasMoreData(): bool
    {
        return $this->hasMoreData;
    }

    /**
     * Get the number of pages fetched so far.
     *
     * @return int
     */
    public function getNumberOfPagesFetched(): int
    {
        return $this->fetchCount;
    }

    /**
     * Get the number of items fetched so far.
     *
     * @return int
     */
    public function getNumberOfItemsFetched(): int
    {
        return array_reduce($this->responses, function ($sum, $response) {
            return $sum + count($response->getContent());
        }, 0);
    }

    /**
     * Fetch a new page.
     *
     * It creates a copy of the parent query, and changes the page indexes
     * to match the current state of fetching.
     *
     * @return Response|null
     */
    public function fetch()
    {
        if (! $this->hasMoreData) {
            return;
        }

        $page = $this->getNextPageQuery()->execute();
        $this->handlePage($page);
        $this->fetchCount++;

        return $page;
    }

    /**
     * @inheritdoc
     *
     * @return Response[]
     */
    public function fetchAll()
    {
        if ($this->query->mustBePaginatedConcurrently()) {
            return $this->fetchAllAsync();
        }

        return $this->fetchAllSync();
    }

    /**
     * Fetch pages synchronously until there is no more data to fetch.
     *
     * @return Response[]
     */
    public function fetchAllSync()
    {
        while ($this->hasMoreData) {
            $this->fetch();
        }

        return $this->responses;
    }

    /**
     * Fetch pages asynchronously by batches until there is no more data to fetch.
     *
     * @param int|null $concurrency (optional) The concurrency limit override value
     * @return Response[]
     */
    public function fetchAllAsync(int $concurrency = null)
    {
        while ($this->hasMoreData) {
            $this->fetchConcurrently($concurrency ?? $this->query->getConcurrency());
        }

        return $this->responses;
    }

    /**
     * Fetch a given maximum number of pages.
     *
     * The limit is global, it is not only bound to one execution of the method,
     * since it is based on the $fetchCount instance property.
     *
     * @param int $limit The maximum number of pages to fetch
     * @return Response[]
     */
    public function fetchLimit(int $limit)
    {
        while ($this->hasMoreData && $this->fetchCount < $limit) {
            $this->fetch();
        }

        return $this->responses;
    }

    /**
     * Fetch a given number of pages concurrently.
     *
     * @param int $concurrentQueries The number of pages to fetch
     * @return Response[]
     */
    public function fetchConcurrently(int $concurrentQueries)
    {
        if (! $this->hasMoreData) {
            return;
        }

        $queries = [];

        for ($i = 0; $i < $concurrentQueries; $i++) {
            $queries[] = $this->getNextPageQuery();
        }

        $responses = $this->query->getClient()->executeAsyncBatch($queries);

        foreach ($responses as $page) {
            if (! $this->hasMoreData) {
                break;
            }

            $this->handlePage($page);
        }

        $this->fetchCount += $concurrentQueries;

        return $this->responses;
    }

    /**
     * Handle a freshly retrieved page, perform checks, alter contents if needed.
     *
     * @param Response $page The page response
     * @return void
     */
    protected function handlePage(Response &$page)
    {
        $this->responses[] = $page;

        // If this page is empty, then the following ones will be too
        if ($page->isEmpty()) {
            $this->hasMoreData = false;
            return;
        }

        // If the page is not fully filled, it means we reached the end
        if (count($page->getContent()) < $this->getPageSize()) {
            $this->hasMoreData = false;
        }
    }

    /**
     * Get a query for the next page to fetch, and move forward the page cursor.
     *
     * @return Contracts\QueryInterface
     */
    abstract protected function getNextPageQuery(): Contracts\QueryInterface;

    /**
     * Get the size of a page.
     *
     * @return int
     */
    abstract protected function getPageSize(): int;
}
