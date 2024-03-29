<?php
namespace Api\Controller;

use F3\SimpleUber\Uber;

class UberController extends ApiController
{
    /**
     * @var Uber
     */
    private $uber;

    /**
     * UberController constructor.
     *
     * @param Uber $uber
     */
    public function __construct(Uber $uber)
    {
        $this->uber = $uber;
    }

    /**
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return array
     */
    public function getPriceEstimate(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $response = $this->uber->getPriceEstimates($lat1, $lon1, $lat2, $lon2);
        $prices = $response->prices;
        $price = isset($prices[0]) ? $prices[0]->estimate : null;
        return [
            'price_estimate' => $price,
        ];
    }
}
