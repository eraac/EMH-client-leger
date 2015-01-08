<?php

namespace HIA\FormBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * FieldRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FieldRepository extends EntityRepository
{
    public function getField($idForm, $label)
    {
        $qb = $this->createQueryBuilder('f')
                    ->leftJoin('f.forms', 'fo')
                    ->where("f.labelField = :label AND fo.id = :idForm")
                    ->setParameters(array(
                        'label' => $label,
                        'idForm' => $idForm
                    ));

        return $qb->getQuery()->getOneOrNullResult();
    }
}
