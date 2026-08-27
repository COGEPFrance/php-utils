<?php

namespace Cogep\PhpUtils\Classes;

/**
 * Interface pour les commandes qui attendent explicitement un payload structuré avec un filtre et des données.
 *
 * Les classes implémentant cette interface doivent déclarer :
 *   - une propriété $filter typée avec une classe concrète (pas object ou array)
 *   - une propriété $data typée avec une classe concrète (pas object ou array)
 *
 * Contraintes de validation recommandées :
 *   #[Assert\NotNull]
 *   #[Assert\Valid]
 *   public readonly MyFilterDto $filter;
 *
 *   #[Assert\NotNull]
 *   #[Assert\Valid]
 *   public readonly MyDataDto $data;
 *
 * @template F of object
 * @template D of object
 */
interface FilterDataDtoInterface extends DTOInterface
{
    /**
     * @return F
     */
    public function getFilter(): object;

    /**
     * @return D
     */
    public function getData(): object;
}
