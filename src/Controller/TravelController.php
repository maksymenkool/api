<?php
namespace Api\Controller;

use Api\Exception\ApiException;
use Api\Mapper\DB\TravelMapper;
use Psr\Log\LoggerAwareTrait;

/**
 * Travel API controller
 */
class TravelController
{
    use LoggerAwareTrait;

    /**
     * @var TravelMapper
     */
    private $travelMapper;

    /**
     * TravelController constructor.
     *
     * @param TravelMapper $travelMapper
     */
    public function __construct(TravelMapper $travelMapper)
    {
        $this->travelMapper = $travelMapper;
    }

    public function getTravel($id)
    {
        $led = [
            'id' => 0,
            'iata' => 'LED',
            'name' => 'Pulkovo',
            'geo' => [59.800278, 30.2625]
        ];
        $svo = [
            'id' => 0,
            'iata' => 'SVO',
            'name' => 'Sheremetievo',
            'geo' => [55.972778, 37.414722],
        ];
        $dme = [
            'id' => 0,
            'iata' => 'DME',
            'name' => 'Domodedovo',
            'geo' => [55.408611, 37.906111],
        ];
        $cosmos = [
            'id' => 0,
            'name' => 'Cosmos',
            'images' => [
                'http://www.gostinica-kocmoc.ru/images/zdanie_gostinicy_kosmos_v_moskve-full8.jpg',
                'http://static.tonkosti.ru/images/b/b3/%D0%9A%D0%BE%D1%81%D0%BC%D0%BE%D1%81_%D0%9C%D0%BE%D1%81%D0%BA%D0%B2%D0%B0.jpg'
            ],
            'geo' => [55.8222, 37.6472],
            'address' => [
                'country' => 'RU',
                'city' => 'Moscow',
                'street' => 'pr-t. Mira, 150',
                'zip' => 129366,
            ],
        ];
        $redSquare = [
            'name' => 'The Red Square',
            'images' => [
                'http://strana.ru/media/images/uploaded/gallery_promo21092359.jpg',
                'http://olgazhdan.com/wp-content/uploads/MY_KREMLIN/IMG_3111.jpg'
            ],
            'geo' => [55.754194, 37.620139],
            'address' => [
                'country' => 'RU',
                'city' => 'Moscow',
                'street' => 'Red Square',
                'zip' => 109012,
            ],
        ];


        return [
            'id' => 0,
            'favorite' => true,
            'title' => 'Example travel',
            'description' => 'This is a mock travel object to help us understand what we need',
            'images' => [
                'http://www.provancewine.ru/assets/shop/images/vodka01.jpg',
                'http://s00.yaplakal.com/pics/pics_original/9/0/3/609309.jpg',
            ],
            'allowedDates' => [
                [
                    'first' => '2016-06-01',
                    'last' => '2016-08-31',
                ],
                [
                    'first' => '2016-12-01',
                    'last' => '2017-02-28',
                ],
            ],
            'author' => [
                'id' => 0,
                'image' => 'http://slon.gr/names/bday_photos/389.jpg',
                'firstName' => 'Alexander',
                'lastName' => 'Radischev',
            ],
            'elements' => [
                [
                    'offset' => 0,
                    'offsetUnit' => 'minute',
                    'type' => 'airport',
                    'airport' => $led,
                ],
                [
                    'offset' => 0,
                    'offsetUnit' => 'minute',

                    'type' => 'flight',
                    'segments' => [
                        [
                            'origin' => 'LED',
                            'destination' => 'SVO',
                            'duration' => 80
                        ]
                    ]
                ],
                [
                    'offset' => 80,
                    'offsetUnit' => 'minute',
                    'type' => 'airport',
                    'airport' => $svo,
                ],
                [
                    'offset' => 180,
                    'offsetUnit' => 'minute',
                    'type' => 'hotel',
                    'subtype' => 'check-in',
                    'hotel' => $cosmos,
                ],
                [
                    'offset' => 1,
                    'offsetUnit' => 'day',
                    'type' => 'sight',
                    'sight' => $redSquare,
                ],
                [
                    'offset' => 2880,
                    'offsetUnit' => 'minute',
                    'type' => 'hotel',
                    'subtype' => 'check-out',
                    'hotel' => $cosmos
                ],
                [
                    'offset' => 3000,
                    'offsetUnit' => 'minute',
                    'type' => 'airport',
                    'airport' => $dme,
                ],
                [
                    'offset' => 3000,
                    'offsetUnit' => 'minute',
                    'type' => 'flight',
                    'segments' => [
                        [
                            'origin' => 'DME',
                            'destination' => 'LED',
                            'duration' => 80
                        ]
                    ]
                ],
                [
                    'offset' => 3080,
                    'offsetUnit' => 'minute',
                    'type' => 'airport',
                    'airport' => $led,
                ],
            ]
        ];
    }
}