<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\IntranetMeasurable;

class Type extends \Matomo\Measurable\Type
{
    public const ID = 'intranet';
    protected $name = 'IntranetMeasurable_Intranet';
    protected $namePlural = 'IntranetMeasurable_Intranets';
    protected $description = 'IntranetMeasurable_IntranetDescription';
    protected $longDescription = 'IntranetMeasurable_IntranetLongDescription';
    protected $howToSetupUrl = '?module=CoreAdminHome&action=trackingCodeGenerator';
}
