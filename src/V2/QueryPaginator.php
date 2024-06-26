<?php

namespace Zoho\Crm\V2;

use Zoho\Crm\AbstractQueryPaginator;
use Zoho\Crm\Contracts\QueryInterface;
use Zoho\Crm\Response;
use Zoho\Crm\Entities\Collection;

/**
 * Paginator for API v2 queries.
 */
class QueryPaginator extends AbstractQueryPaginator
{
    /** @var int The latest page fetched */
    protected $latestPageFetched = 0;

    /**
     * @inheritdoc
     *
     * @return AbstractQuery
     */
    protected function getNextPageQuery(): QueryInterface
    {
        return $this->query->copy()
            ->autoPaginated(false)
            ->param('page', ++$this->latestPageFetched);
    }

    /**
     * @inheritdoc
     */
    protected function getPageSize(): int
    {
        return (int) ($this->query->getUrlParameter('per_page') ?? static::PAGE_MAX_SIZE);
    }

    /**
     * @inheritdoc
     */
    protected function handlePage(Response &$page)
    {
        parent::handlePage($page);

        if ($page->isEmpty()) {
            return;
        }

        // Apply the "maximum modification date" limit.
        if (method_exists($this->query, 'modifiedBefore') && $this->query->hasMaxModificationDate()) {
            $lastEntityDate = new \DateTime($page->getContent()->last()->get('Modified_Time'));

            if ($lastEntityDate >= $this->query->getMaxModificationDate()) {
                $this->hasMoreData = false;
                $page->setContent($this->filterEntitiesExceedingMaxModificationDate($page->getContent()));
            }
        }
    }

    /**
     * Remove all entities from a page whose last modification date exceeds
     * the maximum date set in the query.
     *
     * @param \Zoho\Crm\Entities\Collection $entities The entities to filter
     * @return \Zoho\Crm\Entities\Collection
     */
    protected function filterEntitiesExceedingMaxModificationDate(Collection $entities)
    {
        return $entities->filter(function ($entity) {
            $modifiedAt = new \DateTime($entity->get('Modified_Time'));
            return $modifiedAt < $this->query->getMaxModificationDate();
        });
    }
}
