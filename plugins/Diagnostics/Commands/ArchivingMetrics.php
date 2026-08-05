<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Plugins\Diagnostics\Commands;

use Matomo\Container\StaticContainer;
use Matomo\Plugin\ConsoleCommand;
use Matomo\Plugins\Diagnostics\Diagnostic\ArchiveInvalidationsInformational;
use Matomo\Translation\Translator;

/**
 * Diagnostic command that returns archiving invalidation metrics
 */
class ArchivingMetrics extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('diagnostics:archiving-metrics');
        $this->addNoValueOption(
            'json',
            null,
            "If supplied, the command will return data in json format"
        );
        $this->setDescription('Show metrics describing the current archiving status');
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();

        $metrics = $this->getMetrics();

        if ($input->getOption('json')) {
            $output->write(json_encode($metrics));
        } else {
            $headers = ['Metric', 'Value'];
            $this->renderTable($headers, $metrics);
        }

        return self::SUCCESS;
    }

    /**
     * Get an archiving metrics array from the diagnostics class
     *
     * @return array
     * @throws \Matomo\Exception\DI\DependencyException
     * @throws \Matomo\Exception\DI\NotFoundException
     */
    public function getMetrics(): array
    {
        $metrics = [];
        $informational = new ArchiveInvalidationsInformational(StaticContainer::get(Translator::class));
        $diags[] = $informational->execute();
        if (is_array($diags)) {
            foreach (reset($diags) as $diag) {
                $items = $diag->getItems();
                if (count($items) > 0) {
                    $metrics[] = [$diag->getLabel(), reset($items)->getComment()];
                }
            }
        }
        return $metrics;
    }
}
