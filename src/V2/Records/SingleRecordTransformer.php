<?php

namespace Zoho\Crm\V2\Records;

use Zoho\Crm\Contracts\ResponseTransformerInterface;
use Zoho\Crm\Contracts\QueryInterface;

/**
 * A transformer for responses that consist in a list of a single record.
 */
class SingleRecordTransformer implements ResponseTransformerInterface
{
    /**
     * @inheritdoc
     *
     * @return Record
     */
    public function transformResponse($content, QueryInterface $query)
    {
        if (empty($content['data'])) {
            return null;
        }

        return new Record($content['data'][0]);
    }
}
