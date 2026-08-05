<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Monolog\Handler;

use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;
use Matomo\Exception\MissingFilePermissionException;
use Matomo\Filechecks;

/**
 * Writes log to file.
 *
 * Extends StreamHandler to be able to have a custom exception message.
 */
class FileHandler extends StreamHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            parent::write($record);
        } catch (\UnexpectedValueException $e) {
            $ex = new MissingFilePermissionException(
                Filechecks::getErrorMessageMissingPermissions($this->url)
            );
            $ex->setIsHtmlMessage();
            throw $ex;
        }
    }
}
