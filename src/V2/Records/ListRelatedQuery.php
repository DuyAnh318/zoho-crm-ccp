<?php

namespace Zoho\Crm\V2\Records;

use Zoho\Crm\Contracts\PaginatedQueryInterface;
use Zoho\Crm\Contracts\ResponseTransformerInterface;
use Zoho\Crm\Exceptions\InvalidQueryException;
use Zoho\Crm\Support\Helper;
use Zoho\Crm\V2\Traits\HasPagination;

/**
 * A query to get a list of related records.
 *
 * @see https://www.zoho.com/crm/developer/docs/api/get-related-records.html
 */
class ListRelatedQuery extends AbstractQuery implements PaginatedQueryInterface
{
    use HasPagination;

    /** @var string|null The record ID */
    protected $recordId;

    /** @var string|null The name of the related module */
    protected $relatedModule;

    /**
     * Set the record ID.
     *
     * @param string $id The record ID
     * @return $this
     */
    public function setRecordId(string $id): self
    {
        $this->recordId = $id;

        return $this;
    }

    /**
     * Get the record ID.
     *
     * @return string|null
     */
    public function getRecordId(): ?string
    {
        return $this->recordId;
    }

    /**
     * Set the name of the related module.
     *
     * @param string $relatedModule The name of the related module
     * @return $this
     */
    public function setRelatedModule(string $relatedModule): self
    {
        $this->relatedModule = $relatedModule;

        return $this;
    }

    /**
     * Get the name of the related module.
     *
     * @return string|null
     */
    public function getRelatedModule(): ?string
    {
        return $this->relatedModule;
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return "$this->module/$this->recordId/$this->relatedModule?$this->urlParameters";
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

        if (is_null($this->relatedModule) || empty($this->relatedModule)) {
            throw new InvalidQueryException($this, 'the related module must be defined.');
        }
    }

    /**
     * @inheritdoc
     *
     * @return RecordListTransformer
     */
    public function getResponseTransformer(): ?ResponseTransformerInterface
    {
        return new RecordListTransformer();
    }

    /**
     * Set the minimum date for records' last modification (`Modified_Time` field).
     *
     * @param \DateTimeInterface|string|null $date A date object or a valid string
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function modifiedAfter($date)
    {
        if (is_null($date)) {
            return $this->removeHeader('If-Modified-Since');
        }

        $date = $this->getValidatedDateObject($date);

        return $this->setHeader('If-Modified-Since', $date->format(DATE_ATOM));
    }

    /**
     * Ensure to get a valid date object (implementing DateTimeInterface).
     *
     * @param \DateTimeInterface|string $date A date object or a valid string
     * @return \DateTimeInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function getValidatedDateObject($date)
    {
        if (! Helper::isValidDateInput($date)) {
            throw new \InvalidArgumentException('Date must implement DateTimeInterface or be a valid date string.');
        }

        if (is_string($date)) {
            return new \DateTime($date);
        }

        return $date;
    }
}
