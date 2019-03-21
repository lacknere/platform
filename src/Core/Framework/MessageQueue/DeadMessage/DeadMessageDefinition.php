<?php declare(strict_types=1);

namespace Shopware\Core\Framework\MessageQueue\DeadMessage;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Internal;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\ScheduledTask\ScheduledTaskDefinition;

class DeadMessageDefinition extends EntityDefinition
{
    public static function getEntityName(): string
    {
        return 'dead_message';
    }

    public static function getCollectionClass(): string
    {
        return DeadMessageCollection::class;
    }

    public static function getEntityClass(): string
    {
        return DeadMessageEntity::class;
    }

    public static function getDefaults(EntityExistence $existence): array
    {
        return [
            'errorCount' => 1,
            'encrypted' => false,
        ];
    }

    protected static function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),

            (new LongTextField('original_message_class', 'originalMessageClass'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new BlobField('serialized_original_message', 'serializedOriginalMessage'))->addFlags(new Required(), new Internal(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new LongTextField('handler_class', 'handlerClass'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new BoolField('encrypted', 'encrypted'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),

            (new IntField('error_count', 'errorCount', 0))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),

            (new DateField('next_execution_time', 'nextExecutionTime'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),

            (new LongTextField('exception', 'exception'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new LongTextField('exception_message', 'exceptionMessage'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new LongTextField('exception_file', 'exceptionFile'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),
            (new IntField('exception_line', 'exceptionLine'))->setFlags(new Required(), new WriteProtected(Context::SYSTEM_SCOPE)),

            new FkField('scheduled_task_id', 'scheduledTaskId', ScheduledTaskDefinition::class),

            new CreatedAtField(),
            new UpdatedAtField(),

            new ManyToOneAssociationField('scheduledTask', 'scheduled_task_id', ScheduledTaskDefinition::class, false),
        ]);
    }
}
