<?php

namespace Zoho\Crm\V1;

use SimpleXMLElement;
use Zoho\Crm\Support\Helper;
use Zoho\Crm\Entities\Collection;
use Zoho\Crm\Support\Arrayable;

/**
 * Static class to help build XML requests.
 */
class XmlBuilder
{
    /**
     * Build an XML document representing Zoho records.
     *
     * Used for inserting and updating records.
     *
     * @param string $module The name of the module
     * @param array|\Zoho\Crm\Entities\Collection $records The array/collection of records
     * @return string
     */
    public static function buildRecords($module, $records)
    {
        if ($records instanceof Collection) {
            $records = $records->toArray();
        }

        $xml = new SimpleXMLElement("<$module/>");

        $rowCount = 1;

        foreach ($records as $record) {
            $row = $xml->addChild('row');
            $row->addAttribute('no', $rowCount);

            if ($record instanceof Arrayable) {
                $record = $record->toArray();
            }

            foreach ($record as $attrName => $attrValue) {
                // Stringify boolean values
                if (is_bool($attrValue)) {
                    $attrValue = Helper::booleanToString($attrValue);
                }

                $attr = $row->addChild('FL', $attrValue);
                $attr->addAttribute('val', $attrName);
            }

            $rowCount++;
        }

        // We need to return the XML as a string,
        // but also to get rid of the XML version declaration node.
        // Otherwise Zoho won't be able to parse it...
        $document = dom_import_simplexml($xml);
        return $document->ownerDocument->saveXML($document->ownerDocument->documentElement);
    }
}
