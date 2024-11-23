<?php

declare(strict_types=1);

namespace Wazum\Sluggi\Database;

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;

class SlugUnlockedRestriction implements QueryRestrictionInterface
{
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName === 'pages') {
                $constraints[] = $expressionBuilder->eq(
                    $tableAlias . '.' . 'slug_locked',
                    0
                );
            }
        }
        return $expressionBuilder->and(...$constraints);
    }
}