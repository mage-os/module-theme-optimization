<?php declare(strict_types=1);

namespace MageOS\ThemeOptimization\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Zend_Db_Expr;

/**
 * Update config path for speculation rules from version 1.0.0
 */
class UpdateSpeculationRulesConfigPathPatch implements DataPatchInterface
{
    public function __construct(
        protected ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * Do Upgrade.
     *
     * @return self
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->startSetup();

        // Change the core_config_data path for any 'dev/speculation_rules/*' values to 'system/speculation_rules/*'
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
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * Example of implementation:
     *
     * [
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch1::class,
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch2::class
     * ]
     *
     * @return string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
