<?php

namespace Zoho;

/**
 * Container for Zoho record IDs.
 */
class Client
{
    /**
     * Return a string representation of the list.
     *
     * It concatenates the IDs, separated by semicolons.
     *
     * @return string
     */

    private $data;
    public function __construct()
    {
        $this->data = [
            'name' => 'Zoho API Example',
            'version' => '1.0',
        ];
    }


    // Hàm để lấy dữ liệu
    public function getData()
    {
        return $this->data;
    }
}
