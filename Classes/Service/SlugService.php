<?php

declare(strict_types=1);

namespace Wazum\Sluggi\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Wazum\Sluggi\Database\SlugUnlockedRestriction;

final class SlugService extends \TYPO3\CMS\Redirects\Service\SlugService
{
    /**
     * Prevent updating slugs of pages with slug_locked=1 AND its children
     */
    protected function resolveSubPages(int $id, int $languageUid): array
    {
        // First resolve all sub-pages in default language
        $queryBuilder = $this->getQueryBuilderForPages();
        // Patch start
        if ($languageUid === 0) {
            $queryBuilder
                ->getRestrictions()
                ->add(GeneralUtility::makeInstance(SlugUnlockedRestriction::class));
        }
        // Patch end
        $subPages = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        // if the language is not the default language, resolve the language related records.
        if ($languageUid > 0) {
            $queryBuilder = $this->getQueryBuilderForPages();
            // Patch start
            $queryBuilder
                ->getRestrictions()
                ->add(GeneralUtility::makeInstance(SlugUnlockedRestriction::class));
            // Patch end
            $subPages = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in('l10n_parent', $queryBuilder->createNamedParameter(array_column($subPages, 'uid'), Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageUid, Connection::PARAM_INT))
                )
                ->orderBy('uid', 'ASC')
                ->executeQuery()
                ->fetchAllAssociative();
        }
        $results = [];
        if (!empty($subPages)) {
            $subPages = $this->pageRepository->getPagesOverlay($subPages, $languageUid);
            foreach ($subPages as $subPage) {
                $results[] = $subPage;
                // resolveSubPages needs the page id of the default language
                $pageId = $languageUid === 0 ? (int)$subPage['uid'] : (int)$subPage['l10n_parent'];
                foreach ($this->resolveSubPages($pageId, $languageUid) as $page) {
                    $results[] = $page;
                }
            }
        }
        return $results;
    }
}
