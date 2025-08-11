<?php
// src/Doctrine/SlotAvailableExtension.php
namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Slot;
use Doctrine\ORM\QueryBuilder;

/**
 * N'ajoute la condition "reserved = false" que pour la collection de Slot.
 * Ne touche pas aux requêtes item (pour éviter le "Item not found" lors de la désérialisation).
 */
final class SlotAvailableExtension implements QueryCollectionExtensionInterface
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param Operation|null $operation
     * @param array $context
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        // uniquement pour la ressource Slot
        if ($resourceClass !== Slot::class) {
            return;
        }

        // si le client fournit déjà explicitement un filtre 'reserved', ne l'écrase pas
        if (isset($context['filters']) && array_key_exists('reserved', $context['filters'])) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0] ?? null;
        if ($rootAlias === null) {
            return;
        }

        $param = $queryNameGenerator->generateParameterName('reserved');
        $queryBuilder
            ->andWhere(sprintf('%s.reserved = :%s', $rootAlias, $param))
            ->setParameter($param, false);
    }
}
