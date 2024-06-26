<?php

namespace Zoho\Crm\V1;

use DateTime;
use InvalidArgumentException;
use Zoho\Crm\Contracts\QueryInterface;
use Zoho\Crm\Contracts\PaginatedQueryInterface;
use Zoho\Crm\Contracts\ClientInterface;
use Zoho\Crm\Contracts\ResponseInterface;
use Zoho\Crm\Contracts\ResponseTransformerInterface;
use Zoho\Crm\Contracts\QueryPaginatorInterface;
use Zoho\Crm\Contracts\ResponsePageMergerInterface;
use Zoho\Crm\Support\UrlParameters;
use Zoho\Crm\Exceptions\InvalidQueryException;
use Zoho\Crm\Entities\Collection;
use Zoho\Crm\Support\Helper;
use Zoho\Crm\Traits\{
    BasicQueryImplementation,
    HasRequestUrlParameters,
    HasPagination
};

/**
 * A container for all the attributes of an API request.
 *
 * It contains the format, the module, the method and the URL parameters.
 * It provides a fluent interface to set the different attributes of an API request.
 */
class Query implements PaginatedQueryInterface
{
    use BasicQueryImplementation, HasRequestUrlParameters, HasPagination;

    /** @var string The response format */
    protected $format;

    /** @var string The name of the Zoho module */
    protected $module;

    /** @var string The API method */
    protected $method;

    /** @var int|null The maximum number of records to fetch */
    protected $limit;

    /** @var \DateTime The maximum modification date to fetch records */
    protected $maxModificationDate;

    /**
     * The constructor.
     *
     * @param \Zoho\Crm\V1\Client $client The client to use to make the request
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        $this->urlParameters = new UrlParameters();
    }

    /**
     * @inheritdoc
     */
    public function getHttpMethod(): string
    {
        return $this->getClientMethod()->getHttpMethod();
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return "{$this->format}/{$this->module}/{$this->method}?{$this->urlParameters}";
    }

    /**
     * Get the response format.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the response format.
     *
     * @param string $format The desired response format
     * @return $this
     */
    public function format(?string $format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get the requested module.
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Set the requested module.
     *
     * @param string $module The module name
     * @return $this
     */
    public function module(?string $module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get the requested API method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the requested API method.
     *
     * @param string $method The method name
     * @return $this
     */
    public function method(?string $method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Order records by a given column, in a given direction.
     *
     * The ordering direction must be either 'asc' or 'desc'.
     *
     * @param string $column The column name
     * @param string $order (optional) The ordering direction
     * @return $this
     */
    public function orderBy(string $column, string $order = 'asc')
    {
        return $this->params([
            'sortColumnString' => $column,
            'sortOrderString' => $order
        ]);
    }

    /**
     * Order records by ascending order.
     *
     * @return $this
     */
    public function orderAsc()
    {
        return $this->param('sortOrderString', 'asc');
    }

    /**
     * Order records by descending order.
     *
     * @return $this
     */
    public function orderDesc()
    {
        return $this->param('sortOrderString', 'desc');
    }

    /**
     * Select one or more fields/columns to retrieve.
     *
     * @param string[] $columns An array of column names
     * @return $this
     */
    public function select($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $currentSelection = $this->getSelectedColumns();
        $newSelection = array_unique(array_merge($currentSelection, $columns));

        return $this->param('selectColumns', $this->wrapSelectedColumns($newSelection));
    }

    /**
     * Unselect one or more fields/columns.
     *
     * @param string[] $columns An array of column names
     * @return $this
     */
    public function unselect($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $currentSelection = $this->getSelectedColumns();
        $newSelection = array_diff($currentSelection, $columns);

        return $this->param('selectColumns', $this->wrapSelectedColumns($newSelection));
    }

    /**
     * Wrap an array of field/column names into a valid "selectColumns" parameter value.
     *
     * @param string[] $columns An array of column names
     * @return string
     */
    protected function wrapSelectedColumns(array $columns)
    {
        return $this->module . '(' . implode(',', $columns) . ')';
    }

    /**
     * Get the selected fields/columns.
     *
     * @return string[]
     */
    public function getSelectedColumns()
    {
        $selection = $this->urlParameters->get('selectColumns');

        if ($selection === null) {
            return [];
        }

        // Unwrap the column names
        $selection = substr($selection, strlen($this->module.'('), -1);

        // Split the string on coma and trim the column names
        return array_map('trim', array_filter(explode(',', $selection)));
    }

    /**
     * Check if a field/column is selected.
     *
     * @param string $column The column to check
     * @return bool
     */
    public function hasSelect(string $column)
    {
        return in_array($column, $this->getSelectedColumns());
    }

    /**
     * Remove selection of fields/columns.
     *
     * @return $this
     */
    public function selectAll()
    {
        return $this->removeParam('selectColumns');
    }

    /**
     * Select the creation and last modification timestamps.
     *
     * @return $this
     */
    public function selectTimestamps()
    {
        return $this->select('Created Time', 'Modified Time');
    }

    /**
     * Select a set of default fields which are present on all records.
     *
     * @return $this
     */
    public function selectDefaultColumns()
    {
        $entityName = $this->getClientModule()->newEntity()::name();

        return $this->select("$entityName Owner", 'Created By', 'Modified By')->selectTimestamps();
    }

    /**
     * Set the minimum date for records' last modification.
     *
     * @param \DateTime|string $date A date object or a valid string
     * @return $this
     */
    public function modifiedAfter($date)
    {
        if ($date === null) {
            $this->urlParameters->unset('lastModifiedTime');
            return $this;
        }

        if (! ($date instanceof DateTime) && is_string($date)) {
            $date = new DateTime($date);
        }

        return $this->param('lastModifiedTime', $date);
    }

    /**
     * Set the maximum date for records' last modification.
     *
     * @param \DateTime|string $date A date object or a valid string
     * @return $this
     */
    public function modifiedBefore($date)
    {
        if (! ($date instanceof DateTime) && is_string($date)) {
            $date = new DateTime($date);
        }

        $this->maxModificationDate = $date;

        return $this;
    }

    /**
     * Get the maximum date for records' last modification.
     *
     * @return \DateTime
     */
    public function getMaxModificationDate()
    {
        return $this->maxModificationDate;
    }

    /**
     * Check if the query has a maximum modification date for records.
     *
     * @return bool
     */
    public function hasMaxModificationDate()
    {
        return isset($this->maxModificationDate);
    }

    /**
     * Set the minimum and maximum dates for records' last modification.
     *
     * @param \DateTime|string $from A date object or a valid string
     * @param \DateTime|string $to A date object or a valid string
     * @return $this
     */
    public function modifiedBetween($from, $to)
    {
        return $this->modifiedAfter($from)->modifiedBefore($to);
    }

    /**
     * Trigger the workflow rules on Zoho after the query execution.
     *
     * @param bool $enabled (optional) Whether the workflow rules should be triggered
     * @return $this
     */
    public function triggerWorkflowRules(bool $enabled = true)
    {
        return $this->param('wfTrigger', Helper::booleanToString($enabled));
    }

    /**
     * Limit the number of records to retrieve.
     *
     * @param int|null $limit The number of records
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function limit(?int $limit)
    {
        if (! is_null($limit) && (! is_int($limit) || $limit <= 0)) {
            throw new InvalidArgumentException('Query limit must be a positive non-zero integer.');
        }

        $this->limit = $limit;

        return $this;
    }

    /**
     * Get the limit of records to retrieve.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Check if there is a limit of records to retrieve.
     *
     * @return bool
     */
    public function hasLimit()
    {
        return isset($this->limit);
    }

    /**
     * @inheritdoc
     */
    public function getPaginator(): QueryPaginatorInterface
    {
        return new QueryPaginator($this);
    }

    /**
     * Check if the query is malformed.
     *
     * @return bool
     */
    public function isMalformed()
    {
        return is_null($this->format) || is_null($this->module) || is_null($this->method);
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        // "Modified Time" column has to be be present in the results
        // for "modifiedBefore()" constraint to work properly.
        $selectedColumns = $this->getSelectedColumns();
        $modifiedDateIsMissing = ! empty($selectedColumns) && ! in_array('Modified Time', $selectedColumns);

        if ($this->hasMaxModificationDate() && $modifiedDateIsMissing) {
            $message = '"Modified Time" column is required with "modifiedBefore()" constraint.';
            throw new InvalidQueryException($this, $message);
        }
    }

    /**
     * Check if the query passes validation.
     *
     * @return bool
     */
    public function isValid()
    {
        try {
            $this->validate();
        } catch (InvalidQueryException $e) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve only the first record matched by the query.
     *
     * It will fail if called on a query which is not supposed to retrieve records.
     *
     * @return \Zoho\Crm\V1\Entities\Entity
     */
    public function first()
    {
        // Set the range of fetched records to 1 to optimize the execution time.

        return $this->copy()
            ->param('toIndex', $this->getUrlParameter('fromIndex'))
            ->autoPaginated(false)
            ->get()
            ->first();
    }

    /**
     * Get the module instance attached to the bound client.
     *
     * @return Modules\AbstractModule
     */
    public function getClientModule()
    {
        return $this->client->module($this->module);
    }

    /**
     * Get the method handler attached to the bound client.
     *
     * @return Methods\AbstractMethod
     */
    public function getClientMethod()
    {
        return $this->client->getMethodHandler($this->method);
    }

    /**
     * @inheritdoc
     */
    public function getResponsePageMerger(): ResponsePageMergerInterface
    {
        return $this->getClientMethod();
    }

    /**
     * @inheritdoc
     */
    public function getResponseTransformer(): ?ResponseTransformerInterface
    {
        return $this->getClientMethod();
    }

    /**
     * Allow the deep cloning of the query.
     *
     * @return void
     */
    public function __clone()
    {
        $this->urlParameters = clone $this->urlParameters;
    }
}
