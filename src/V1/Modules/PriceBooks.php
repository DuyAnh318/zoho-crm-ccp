<?php

namespace Zoho\Crm\V1\Modules;

/**
 * PriceBooks module handler.
 *
 * @see https://www.zoho.com/crm/developer/docs/api/modules-fields.html#Price_Books
 */
class PriceBooks extends AbstractRecordsModule
{
    /** @inheritdoc */
    protected static $associatedEntity = \Zoho\Crm\V1\Entities\Records\PriceBook::class;

    /** @inheritdoc */
    protected static $supportedMethods = [
        'getFields',
        'getRecordById',
        'getRecords',
        'getMyRecords',
        'searchRecords',
        'insertRecords',
        'updateRecords',
        'deleteRecords',
        'getDeletedRecordIds',
        'getRelatedRecords',
        'getSearchRecordsByPDC',
        'deleteFile',
    ];
}
