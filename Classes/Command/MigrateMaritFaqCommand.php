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
                "number of rows to be processed (default 1) - use -1 to procede all or 1 to test \n"
            )->addOption(
                'shadowClass',
                's',
                InputOption::VALUE_OPTIONAL,
                "add option shadow css class to content accordeon groups \n"
            )->addOption(
                'roundedClass',
                'o',
                InputOption::VALUE_OPTIONAL,
                "add option rounded css class to content accordeon groups \n"
            )->addOption(
                'colorClass',
                'c',
                InputOption::VALUE_OPTIONAL,
                "add name of css class to add buttons that defines the color scheme. default, btn-default, btn-primary, btn-secondary or custom\n"
            )->addOption(
                'borderClass',
                'b',
                InputOption::VALUE_OPTIONAL,
                "add css class border to panels \n"
            )  ;
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
        $config = [
            'shadowClass' => 'shadow',
            'colorClass' => 'default',
            'borderClass' => '',
            'roundedClass' => 'rounded' ,
        ];
        if ( $input->getOption('shadowClass') ) {
            $config['shadowClass'] = 'shadow' ;
            $io->writeln('Adding shadow class to accordeon groups' );
        }
        if ( $input->getOption('colorClass') ) {
            $config['colorClass'] = $input->getOption('colorClass') ;
            $io->writeln('Using color class ' . $config['colorClass'] . ' for buttons' );
        }
        if ( $input->getOption('borderClass') ) {
            $config['borderClass'] = 'border' ;
            $io->writeln('Adding class ' . $config['borderClass'] . 'to panels' );
        }
        if ( $input->getOption('roundedClass') ) {
            $config['roundedClass'] = 'rounded' ;
            $io->writeln('Adding  class ' . $config['roundedClass'] . ' to accordeon groups' );
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
                    $row = $this->convertFF( $input , $output , $flexFormService , $row , $config );
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
    private function convertFF( InputInterface $input , OutputInterface $output , FlexFormService $flexFormService , array $row , array $config ) : array
    {
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
            $newUid = $this->createTtcontent( $row , $row['pid'] , $content[0] , 0  , $config);
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
                $newUids[] = $this->createTtcontent( $row , $pid , $subAccordeon , 0  , $config);
            }
            if ( count($newUids) > 0 ) {
                $row['converted_content'] = "New UIDs: " . implode( ", " , $newUids ) ;
                $newUid = $this->createTtcontent( $row , $row['pid'] , [
                    'title' => $row['header'] . '',
                    'content_elements' => implode( "," , $newUids )
                ] , 1  , $config);
            }

        }

        if ( $newUids > 0 ) {
            $this->updateRelationRecords( $row , $newUid , $output);
        }

        return $row;
    }
    private function updateRelationRecords( array $row , int $newUid , OutputInterface $output ): void
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $rows = $qb->select('uid','pid','records')
            ->from('tt_content')
            ->where(
                $qb->expr()->eq('CType', $qb->createNamedParameter('shortcut' )),
                $qb->expr()->inSet('records', $qb->createNamedParameter('tt_content_' .$row['uid'] )),

            )->executeQuery();
        while ($updaterow = $rows->fetchAssociative()) {
            $updataFieldRecords = str_replace( 'tt_content_' . $row['uid'] , 'tt_content_' . $newUid ,  $updaterow['records']) ;

            $qb->update('tt_content')
                ->set('records', $updataFieldRecords )
                ->where(
                    $qb->expr()->eq('uid', $qb->createNamedParameter($updaterow['uid'], \PDO::PARAM_INT))
                )
                ->executeStatement();
            $output->writeln('Updated relation FROM ' . $updaterow['records'] . " => " . $updataFieldRecords . " In UID: ". $updaterow['uid'] . " on PID: " . $updaterow['pid'] );
        }
    }

    private function createTtcontent( array $row , $pid , $content_element , $hasSubAccordeons , $config) : int {
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
                'pi_flexform' => $this->getTFFfield($hasSubAccordeons  , $config),
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

    private function getTFFfield($hasSubAccordeons  , $config): string
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
                <field index="shadowClass">
                    <value index="vDEF">' . $config['shadowClass'] .'</value>
                </field>
                 <field index="borderClass">
                    <value index="vDEF">' . $config['borderClass'] .'</value>
                </field>
                 <field index="colorClass">
                    <value index="vDEF">' . $config['colorClass'] .'</value>
                </field>
                <field index="roundedClass">
                    <value index="vDEF">' . $config['roundedClass'] .'</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';
    }
}
