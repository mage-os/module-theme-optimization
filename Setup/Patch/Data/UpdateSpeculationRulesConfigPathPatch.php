<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Zend_Db_Expr;

class UpdateSpeculationRulesConfigPathPatch implements DataPatchInterface
{
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @return self
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        $connection->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'path' => new Zend_Db_Expr(
                    'REPLACE(path, "dev/speculation_rules/", "system/speculation_rules/")'
                )
            ],
            ['path LIKE ?' => 'dev/speculation_rules/%']
        );

        $connection->endSetup();

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
