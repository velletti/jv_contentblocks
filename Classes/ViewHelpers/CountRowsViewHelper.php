<?php
namespace JVelletti\JvContentblocks\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Logger;
use InvalidArgumentException;
use Exception;

class CountRowsViewHelper extends AbstractViewHelper
{

    /**
     * @var Logger
     */
    protected $logger;

    public function initializeArguments()
    {
        $this->registerArgument('table', 'string', 'The table name', true);
        $this->registerArgument('where', 'array', 'The where conditions', false, []);
        $this->registerArgument('deleted', 'bool', 'Respect deleted flag', false, false);
        $this->registerArgument('groupby', 'string', 'Field to group the count result', false, '');
    }

    /**
     * Example usage in a Fluid template:
     *
     * <jvelletti:countRows table="my_table" where="{field: {condition: 'lt', value: 'value'}}" deleted="true" groupby="my_field" />
     *
     *  This will count the rows in the table "my_table" where the field "field" is less than "value",
     *  respecting the deleted flag, and grouping by "my_field".
     *
     *  <f:variable name="userCount"><jvelletti:countRows table="fe_users" where="{crdate: {condition: 'gt', value: 'time()', isTime: true}}" /></f:variable>
     */

    public function render(): int
    {
        $table = ($this->arguments['table'] ?? 'missing_table');

        $where = ($this->arguments['where'] ?? '' );

        $deleted = ($this->arguments['deleted'] ?? true);
        $groupby = ($this->arguments['groupby'] ?? false);

        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

            if (!$connection->getSchemaManager()->tablesExist([$table])) {
                $this->getLogger()->error('No table Error in CountRowsViewHelper', [
                    'line' => __LINE__,
                    'message' => 'Table "' . $table . '" does not exist',
                    'code' => 404
                ]);
                return 0;
            }

            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->count('*')->from($table);
            if( is_countable( $where) && count($where) > 0 && !empty($where) ) {
                foreach ($where as $field => $condition) {
                    if (isset($condition['isTime']) && (int)$condition['isTime'] == 1 || $condition['value'] == 'time()') {
                        if (isset($condition['diffDays'])) {
                            $condition['value'] =  time() + ((int)$condition['diffDays'] * 24 * 60 * 60);
                        } else {
                            $condition['value'] = time();
                        }
                    }
                    switch ($condition['condition']) {
                        case 'lt':
                            $queryBuilder->andWhere($queryBuilder->expr()->lt($field, $queryBuilder->createNamedParameter($condition['value'])));
                            break;
                        case 'gt':
                            $queryBuilder->andWhere($queryBuilder->expr()->gt($field, $queryBuilder->createNamedParameter($condition['value'])));
                            break;
                        case 'eq':
                            $queryBuilder->andWhere($queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($condition['value'])));
                            break;
                        case 'like':
                            $queryBuilder->andWhere($queryBuilder->expr()->like($field, $queryBuilder->createNamedParameter($condition['value'])));
                            break;
                        default:
                            throw new \InvalidArgumentException('Invalid condition: ' . $condition['condition'] . ' for field: ' . $field);
                    }
                }
            }

            if ($deleted) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq('deleted', 0));
            }

            if ($groupby) {
                $queryBuilder->groupBy($groupby);
            }
           return (int)$queryBuilder->executeQuery()->fetchOne();
        } catch (\Exception $e) {
            $this->getLogger()->error('Error in CountRowsViewHelper', [
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return 0;
        }
    }

    protected function getLogger(): Logger
    {
        if ($this->logger === null) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }
        return $this->logger;
    }
}
