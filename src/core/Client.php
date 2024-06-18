<?php

namespace Zoho\Core;

class Client
{
    protected $data;
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
