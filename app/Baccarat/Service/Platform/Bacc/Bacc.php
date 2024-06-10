<?php

namespace App\Baccarat\Service\Platform\Bacc;



class Bacc
{


    public function __construct(protected Client $client)
    {
    }

    public function calculate(array $data): Response
    {
        return $this->client->calculate(['dataArray'=>$data]);
    }
}