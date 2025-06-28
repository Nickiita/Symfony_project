<?php

namespace App\Model;

class Deposit
{
    public readonly float $rate;
    public readonly string $description;
    public readonly string $publicationMonth;

    public function __construct(array $rawDataElem, array $headerData)
    {
        $this->rate = $rawDataElem['obs_val'];

        $this->description = current(
            array_filter($headerData, function ($headerDataElem) use ($rawDataElem) {
                return $rawDataElem['element_id'] === $headerDataElem['id'];
            })
        )['elname'];

        $this->publicationMonth = $rawDataElem['dt'];
    }
}