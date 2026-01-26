<?php

declare(strict_types=1);

namespace JVelletti\JvContentblocks\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Service\FlexFormService;

class MigrateMaritFaqCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Searches for tt_content rows with list_type=maritfaq_pi1')
            ->addOption(
                'rows',
                'r',
                InputOption::VALUE_OPTIONAL,
                "local path to a template folder, that should be updated, starting from project root. \n
                --path=/vendor/your-vendor/your-extension/Configuration/TypoScript/\n
                f.e.: --path=/vendor/jvelletti/jve-upgradewizard/Configuration/TypoScript/
                \n"
            ) ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $maxRows = 1 ;
        if ($input->getOption('rows') ) {
            $maxRows = (int)$input->getOption('rows') ;
            $io->writeln('Max Rows to be updated was set to '. $maxRows );
        } else {
            $io->warning('Argument rows not given, using default of '. $maxRows );
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $rows = $queryBuilder
            ->select('t.uid as uid', 't.pid as pid', 't.sys_language_uid as lng', 't.header as header'
                , 't.pi_flexform as ff', 't.space_before_class' , 't.space_after_class', 't.sorting' ,
                'p.title AS page_title')
            ->from('tt_content' , 't')
            ->leftJoin('t', 'pages', 'p', $queryBuilder->expr()->eq('t.pid', 'p.uid'))
            ->where(
                $queryBuilder->expr()->eq('t.hidden', 0),
                $queryBuilder->expr()->eq('t.list_type', $queryBuilder->createNamedParameter('maritfaq_pi1'))
            )
            ->orderBy('t.pid', 'ASC')
            ->setMaxResults($maxRows)
            ->executeQuery()

            ->fetchAllAssociative();

        $totalCount = count($rows);
        $io->success('Total number of rows: ' . $totalCount);

        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            if ($totalCount > 0) {
                $io->writeln('');
                $io->writeln(sprintf('%-8s %-8s %-4s %s', 'UID', 'PID', 'lng', 'Pagetitle - Header'));
                $io->writeln(str_repeat('-', 80));
                $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                foreach ($rows as $row) {
                    $row = $this->convertFF( $input , $output , $flexFormService , $row);
                    $io->writeln(sprintf(
                        '%-8s %-8s %-4s %s',
                        $row['uid'],
                        $row['pid'],
                        $row['lng'],
                        $row['page_title'] . ' - ' .
                        $row['header'] . " - FF: " . ($row['converted_content'] ?? '-')
                    ));

                    $io->writeln(str_repeat('-', 80));

                }
            }
        }

        return Command::SUCCESS;
    }
    private function convertFF( InputInterface $input , OutputInterface $output , FlexFormService $flexFormService , array $row) {
        $ff = $flexFormService->convertFlexFormContentToArray($row['ff']);

        // Content-Array extrahieren
        $content = [];
        if (isset($ff['settings']['groups']) && is_array($ff['settings']['groups'])) {
            foreach ($ff['settings']['groups'] as $key => $element) {
                if (isset($element['groupContent'])) {
                    $content[] = $element['groupContent'];
                }
            }
        }

        if ( count($content) == 0 || !isset( $content[0]['content_elements'] )) {
            return $row;
        }
        if ( count($content) == 1 ) {
            $elements = GeneralUtility::trimExplode( "," , $content[0]['content_elements']);
            if ( count($elements) < 1  ) {
                return $row;
            }
            $newUid = $this->createTtcontent( $row , $row['pid'] , $content[0] , 0 );
            $row['converted_content'] = "New UID: " . $newUid ;

        } else {
            $newUids=[];
            $pid = 0;
            foreach ($content as $subAccordeon) {
                $elements = GeneralUtility::trimExplode( "," , $subAccordeon['content_elements']);
                if ( count($elements) < 1  ) {
                    continue;
                }

                //  Find PID of first element to use as pid for new accordeon group
                if ( $pid == 0 ) {
                    $firstElementUid = (int)$elements[0];
                    $qbSub = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('tt_content');
                    $firstElementRow = $qbSub
                        ->select('pid')
                        ->from('tt_content')
                        ->where(
                            $qbSub->expr()->eq('uid', $qbSub->createNamedParameter($firstElementUid, \PDO::PARAM_INT))
                        )
                        ->executeQuery()
                        ->fetchAssociative();
                    if ( $firstElementRow && isset( $firstElementRow['pid'] ) ) {
                        $pid = (int)$firstElementRow['pid'];
                    } else {
                        $pid = $row['pid'];
                    }
                }
                $newUids[] = $this->createTtcontent( $row , $pid , $subAccordeon , 0 );
            }
            if ( count($newUids) > 0 ) {
                $row['converted_content'] = "New UIDs: " . implode( ", " , $newUids ) ;
                $newUid = $this->createTtcontent( $row , $row['pid'] , [
                    'title' => $row['header'] . '',
                    'content_elements' => implode( "," , $newUids )
                ] , 1 );
            }

        }

        return $row;
    }

    private function createTtcontent( array $row , $pid , $content_element , $hasSubAccordeons) : int {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
         $qb->insert('tt_content')
            ->values([
                'pid' => $pid,
                'sys_language_uid' => $row['lng'],
                'sorting' => $row['sorting'],
                'space_before_class' => $row['space_before_class'],
                'space_after_class' => $row['space_after_class'],
                'CType' => 'jvelletti_accordiongroup',
                'header' => ($content_element['title'] ?? ''),
                'pi_flexform' => $this->getTFFfield($hasSubAccordeons),
                'jvelletti_accordiongroup_accordions' => ($content_element['content_elements'] ?? ''),
            ])
            ->executeStatement();


        $newUid =  $qb->getConnection()->lastInsertId();
        if ( ! $newUid ) {
            return 0 ;
        }

        $qb->update('tt_content')->set('hidden', 1)->where(
            $qb->expr()->eq('uid', $qb->createNamedParameter($row['uid'], \PDO::PARAM_INT))
        )->executeStatement();

        return (int)$newUid ;
    }

    private function getTFFfield($hasSubAccordeons): string
    {
        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="hasSubAccordeon">
                    <value index="vDEF">' . $hasSubAccordeons. '</value>
                </field>
                <field index="defaultRendering">
                    <value index="vDEF">0</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
    }
}
