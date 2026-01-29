<?php
declare(strict_types=1);

namespace JVelletti\JvContentblocks\ViewHelpers;

use Doctrine\DBAL\ArrayParameterType;
use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

final class TtContentFromCsvViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('csv', 'string', 'Comma-separated tt_content uids', true);
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function render(): array
    {
        $csv = (string)$this->arguments['csv'];
        $uids = $this->parseCsvToUids($csv);

        if ($uids === []) {
            return [];
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        // IMPORTANT: ignore enableFields (hidden, fe_group, start/endtime, etc.)
        $qb->getRestrictions()->removeAll();
        // But still ignore deleted records
        $qb->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $rows = $qb->select(
            'uid',
            'header',
            'CType',
            'hidden',
            'starttime',
            'endtime',
            'fe_group'
        )
            ->from('tt_content')
            ->where(
                $qb->expr()->in(
                    'uid',
                    $qb->createNamedParameter($uids, ArrayParameterType::INTEGER)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        // keep the original CSV order
        $pos = array_flip($uids);
        usort($rows, static fn(array $a, array $b) =>
            ($pos[(int)$a['uid']] ?? PHP_INT_MAX) <=> ($pos[(int)$b['uid']] ?? PHP_INT_MAX)
        );

        return $rows;
    }


    /**
     * @return int[]
     */
    private function parseCsvToUids(string $csv): array
    {
        $parts = preg_split('/\s*,\s*/', trim($csv)) ?: [];
        $uids = [];

        foreach ($parts as $token) {
            $token = trim((string)$token);
            if ($token === '') {
                continue;
            }

            // Accept:
            // - "12"
            // - "tt_content_12"
            // - anything that ends with "_<digits>" or "<digits>"
            if (preg_match('/(?:^|[_])(\d+)$/', $token, $m)) {

                if (!str_starts_with($token, 'tt_content_') ) {
                //    continue;
                }

                $uid = (int)$m[1];
                if ($uid > 0) {
                    $uids[] = $uid;
                }
                continue;
            }


            // Fallback: first integer in token (very defensive)
            if (preg_match('/(\d+)/', $token, $m)) {
                $uid = (int)$m[1];
                if ($uid > 0) {
                    $uids[] = $uid;
                }
            }

        }

        return array_values(array_unique($uids));
    }

}
