<?php declare(strict_types=1);

namespace Shopware\Core\Content\ImportExport\Processing\Pipe;

use Shopware\Core\Content\ImportExport\Aggregate\ImportExportLog\ImportExportLogEntity;
use Shopware\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.7.0 - reason:becomes-internal
 */
#[Package('fundamentals@after-sales')]
abstract class AbstractPipeFactory
{
    abstract public function create(ImportExportLogEntity $logEntity): AbstractPipe;

    abstract public function supports(ImportExportLogEntity $logEntity): bool;
}
