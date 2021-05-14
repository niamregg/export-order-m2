<?php

declare(strict_types=1);

namespace Withings\ExportOrder\Cron;

use Psr\Log\LoggerInterface;
use Withings\ExportOrder\Model\Order\Export as ExportOrder;

class Export
{
    /** @var LoggerInterface  */
    private $logger;

    /** @var ExportOrder */
    private $export;

    public function __construct(
        LoggerInterface $logger,
        ExportOrder $export
    ) {
        $this->logger = $logger;
        $this->export = $export;
    }

    public function execute(): void
    {
        $this->logger->info('Starting export order');
        $this->export->execute();
    }
}
