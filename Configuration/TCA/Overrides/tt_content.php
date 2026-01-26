<?php
defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


/**
 * Former "section_frame"
 * =====================================================================================================================
 */

// $GLOBALS['TCA']['tt_content']['columns']['frame_class']['config']['default'] = 100;

/* used in some instances, so also needed in content elements */
$GLOBALS['TCA']['tt_content']['columns']['tx_bootstrapcore_first_element_in_row'] =
    [
        'exclude' => 1,
        'label'   => 'First element in Row',
        'config'  => [
            'type' => 'check',
            'default' => 0,
        ],
    ];

$GLOBALS['TCA']['tt_content']['columns']['tx_bootstrapcore_last_element_in_row'] =
    [
        'exclude' => 1,
        'label'   => 'Last element in Row',
        'config'  => [
            'type' => 'check',
            'default' => 0,
        ],
    ];
