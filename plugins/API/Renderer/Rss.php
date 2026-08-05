<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\API\Renderer;

use Matomo\API\ApiRenderer;
use Matomo\Common;

class Rss extends ApiRenderer
{
    /**
     * @param $message
     * @param \Exception|\Throwable $exception
     * @return string
     */
    public function renderException($message, $exception)
    {
        self::sendHeader('Content-Type: text/plain; charset=utf-8');

        return 'Error: ' . $message;
    }

    public function renderDataTable($dataTable)
    {
        /** @var \Matomo\DataTable\Renderer\Rss $tableRenderer */
        $tableRenderer = $this->buildDataTableRenderer($dataTable);

        $idSite = $this->requestObj->getIntegerParameter('idSite', 0);
        $method = Common::sanitizeInputValue($this->requestObj->getStringParameter('method', ''));

        if (empty($idSite)) {
            $idSite = 'all';
        }

        $tableRenderer->setApiMethod($method);
        $tableRenderer->setIdSite($idSite);
        $tableRenderer->setTranslateColumnNames($this->requestObj->getBoolParameter('translateColumnNames', false));

        return $tableRenderer->render();
    }

    public function renderArray($array)
    {
        return $this->renderDataTable($array);
    }

    public function sendHeader($type = "xml")
    {
        Common::sendHeader('Content-Type: text/' . $type . '; charset=utf-8');
    }
}
