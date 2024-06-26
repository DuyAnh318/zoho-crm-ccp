<?php

namespace Zoho\Crm\V2\AccessTokenStores;

/**
 * Implementation of token store that let the developer inject the loading and saving logic.
 */
class CustomStore extends AbstractStore
{
    /** @var callable The callback used to load the data */
    protected $loadCallback;

    /** @var callable The callback used to save the data */
    protected $saveCallback;

    /**
     * The constructor.
     *
     * @param callable $loadCallback The loading logic
     * @param callable $saveCallback The saving logic
     */
    public function __construct(callable $loadCallback, callable $saveCallback)
    {
        $this->loadCallback = $loadCallback;
        $this->saveCallback = $saveCallback;

        $this->reload();
    }

    /**
     * Read the storage and load its contents if existing.
     *
     * @return void
     */
    public function reload(): void
    {
        ($this->loadCallback)($this);
    }

    /**
     * @inheritdoc
     */
    public function save(): bool
    {
        return ($this->saveCallback)($this);
    }
}
