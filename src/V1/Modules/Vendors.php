<?php

namespace Zoho\Crm\V1\Modules;

/**
 * Vendors module handler.
 *
 * @see https://www.zoho.com/crm/developer/docs/api/modules-fields.html#Vendors
 */
class Vendors extends AbstractRecordsModule
{
    /** @inheritdoc */
    protected static $associatedEntity = \Zoho\Crm\V1\Entities\Records\Vendor::class;

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
