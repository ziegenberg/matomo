<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\UserCountry\Columns;

use Matomo\Columns\Dimension;
use Matomo\Common;
use Matomo\Metrics\Formatter;

class Continent extends Dimension
{
    protected $dbTableName = 'log_visit';
    protected $columnName = 'location_country';
    protected $type = self::TYPE_TEXT;
    protected $category = 'UserCountry_VisitLocation';
    protected $nameSingular = 'UserCountry_Continent';
    protected $namePlural = 'UserCountry_Continents';
    protected $segmentName = 'continentCode';
    protected $acceptValues = 'eur, asi, amc, amn, ams, afr, ant, oce';
    protected $sqlFilter = 'Matomo\Plugins\UserCountry\UserCountry::getCountriesForContinent';

    public function groupValue($value, $idSite)
    {
        return Common::getContinent($value);
    }

    public function formatValue($value, $idSite, Formatter $formatter)
    {
        return \Matomo\Plugins\UserCountry\continentTranslate($value);
    }
}
