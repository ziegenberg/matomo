<?php

use Matomo\Tests\Framework\Mock\ProfessionalServices\Advertising;
use Matomo\Plugins\ProfessionalServices\tests\Framework\Mock\Promo;

return array(
    'Matomo\ProfessionalServices\Advertising' => function () {
        return new Advertising();
    },
    'Matomo\Plugins\ProfessionalServices\Promo' => function () {
        return new Promo();
    },
);
