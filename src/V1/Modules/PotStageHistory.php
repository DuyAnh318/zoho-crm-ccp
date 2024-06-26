<?php

namespace Zoho\Crm\V1\Modules;

/**
 * PotStageHistory module handler.
 */
class PotStageHistory extends AbstractRecordsModule
{
    /** @inheritdoc */
    protected static $associatedEntity = \Zoho\Crm\V1\Entities\PotentialStageHistoryEntry::class;

    /** @inheritdoc */
    protected static $supportedMethods = [
        'getRelatedRecords',
    ];

    /**
     * Get the stage history of a potential.
     *
     * @param string $potentialId The potential ID
     * @return \Zoho\Crm\Entities\Collection
     */
    public function getPotentialStageHistory($potentialId)
    {
        return $this->relatedTo('Potentials', $potentialId)->get();
    }
}
