<?php

namespace Zoho\Crm\V2\Records;

use Zoho\Crm\Contracts\ResponseTransformerInterface;
use Zoho\Crm\Exceptions\InvalidQueryException;
use Zoho\Crm\Support\Helper;

/**
 * A query to get a specific record by ID.
 *
 * @see https://www.zoho.com/crm/developer/docs/api/get-specific-record.html
 */
class GetByIdQuery extends AbstractQuery
{
    /** @var string|null The ID of the record to fetch */
    protected $recordId;

    /**
     * Set the ID of the record to fetch.
     *
     * @param string $id The ID to fetch
     * @return $this
     */
    public function setRecordId(string $id): self
    {
        $this->recordId = $id;

        return $this;
    }

    /**
     * Get the ID of the record to fetch.
     *
     * @return string|null
     */
    public function getRecordId(): ?string
    {
        return $this->recordId;
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return "$this->module/$this->recordId?$this->urlParameters";
    }

    /**
     * @inheritdoc
     */
    public function validate(): void
    {
        parent::validate();

        if (is_null($this->recordId) || empty($this->recordId)) {
            throw new InvalidQueryException($this, 'the record ID must be present.');
        }
    }

    /**
     * @inheritdoc
     *
     * @return SingleRecordTransformer
     */
    public function getResponseTransformer(): ?ResponseTransformerInterface
    {
        return new SingleRecordTransformer();
    }
}
