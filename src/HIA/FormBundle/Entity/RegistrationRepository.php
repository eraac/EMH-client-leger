<?php

namespace HIA\FormBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * RegistrationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RegistrationRepository extends EntityRepository
{
    // Retourne tous les enregistrements qui correspondantes aux critéres (status, qui)
    public function getRegistrations($idUser, $idStatus, $who, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('r')
                    ->leftJoin('r.form', 'f')
                    ->leftJoin('r.userSubmit', 'us')
                    ->leftJoin('r.userValidate', 'uv')
                    ->leftJoin('f.readers', 'g')
                    ->leftJoin('g.users', 'u')
                    ->addSelect('f')
                    ->addSelect('us')
                    ->orderBy('r.registrationDate', 'DESC')
                                        
                    ->where('r.status IN (:idStatus)')
                    ->setParameters(array(                        
                        "idUser" => $idUser,
                        "idStatus" => $idStatus
                    ))

                    ->setFirstResult($offset)
                    ->setMaxResults($limit);
                
                    if ($who['submitByUser'] AND !$who['submitByOther']) {
                        $qb->andWhere('us.id = :idUser');
                    } else if (!$who['submitByUser'] AND $who['submitByOther']) {
                        $qb->andWhere('us.id != :idUser AND u.id = :idUser');
                    } else if ($who['validByUser'] AND !$who['validByOther']) {
                        $qb->andWhere('uv.id = :idUser');
                    } else if (!$who['validByUser'] AND $who['validByOther']) {
                        $qb->andWhere('uv.id != :idUser AND u.id = :idUser');
                    } else {
                        $qb->andWhere('u.id = :idUser OR us.id = :idUser');
                    }

        $results = new Paginator($qb, true);

        return $results->getQuery()->getArrayResult();
    }

    // Retourne tous les enregistrements accessible pour un utilisateur
    public function getAll($idUser, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('r')
                    ->leftJoin('r.form', 'f')
                    ->leftJoin('r.userSubmit', 'us')
                    ->leftJoin('f.readers', 'g')
                    ->leftJoin('g.users', 'u')
                    ->addSelect('f')
                    ->addSelect('us')
                    ->orderBy('r.registrationDate', 'DESC')

                    ->where('u.id = :idUser OR us.id = :idUser')

                    ->setParameter("idUser", $idUser)
                    ->setFirstResult($offset)
                    ->setMaxResults($limit);

        $results = new Paginator($qb, true);

        return $results->getQuery()->getResult();
    }

    // Retourne les derniers formulaires utilisés par l'utilisateur
    public function getLastFormUsed($idUser, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('r')
                    ->leftJoin('r.form', 'f')
                    ->addSelect('f')
                    ->leftJoin('f.tags', 't')
                    ->addSelect('t')
                    ->orderBy('r.registrationDate', 'DESC')
                    ->where('r.userSubmit = :idUser')
                    ->setParameter("idUser", $idUser)
                    ->groupBy("r.form")
                    ->setFirstResult($offset)
                    ->setMaxResults($limit);

        $results = new Paginator($qb, true);

        return $results->getQuery()->getResult();
    }

    // Retourne le nombre d'enregistrement non traités des autres utilisateurs
    public function countUnreadSubmitByOther($idUser)
    {
        $qb = $this->createQueryBuilder('r')
                    ->select('COUNT(r)')
                    ->leftJoin('r.form', 'f')
                    ->leftJoin('f.readers', 'g')
                    ->leftJoin('g.users', 'u')
					->where('r.userValidate is NULL AND u.id = :idUser AND r.userSubmit != :idUser')
                    ->setParameters(array(
                        'idUser'        => $idUser
                    ));

        return $qb->getQuery()->getSingleScalarResult();
    }

    // Retourne des enregistrements que vous avez le droit de lire non soumit par vous
    public function listUnreadSubmitByOther($idUser, $statusPending, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.form', 'f')
            ->leftJoin('r.userSubmit', 'us')
            ->addSelect('us')
            ->addSelect('f')
            ->leftJoin('f.readers', 'g')
            ->leftJoin('g.users', 'u')
            ->orderBy("r.registrationDate", 'ASC')
            ->where('r.status = :statusPending AND u.id = :idUser AND r.userSubmit != :idUser')
            ->setParameters(array(
                'statusPending' => $statusPending,
                'idUser'        => $idUser
            ))
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = new Paginator($qb, true);

        return $results->getQuery()->getResult();
    }

    // Retourne des enregistrements traités soumi par vous
    public function listReadSubmitByUser($idUser, $statusPending, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.form', 'f')
            ->addSelect('f')
            ->where('r.status != :statusPending AND r.userSubmit = :idUser')
            ->orderBy("r.validationDate", 'DESC')
            ->setParameters(array(
                'statusPending' => $statusPending,
                'idUser'        => $idUser
            ))
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $results = new Paginator($qb, true);

        return $results->getQuery()->getResult();
    }

    // Retourne 1 ou plus si l'utilisateur à le droit de lire l'enregistrement
    public function canRead($idUser, $idRegistration)
    {
        $qb = $this->createQueryBuilder('r')
            ->select("COUNT(r)")
            ->leftJoin("r.form", 'f')
            ->leftJoin("f.readers", 'g')
            ->leftJoin("g.users", 'u')
            ->where("r.id = :idRegistration AND (r.userValidate = :idUser OR r.userSubmit = :idUser OR u.id = :idUser)")
            ->setParameters(array(
                "idUser" => $idUser,
                "idRegistration" => $idRegistration
            ));

        return $qb->getQuery()->getSingleScalarResult();
    }

    // Retourne le nombre d'enregistrement soumi par l'utilisateur
    public function countSubmitForm($idUser)
    {
        $qb = $this->createQueryBuilder('r')
                ->select("COUNT(r)")
                ->where("r.userSubmit = :idUser")
                ->setParameters(array(
                    "idUser" => $idUser
                ));

        return $qb->getQuery()->getSingleScalarResult();
    }

    // Retourne le nombre de soumission traitées par l'utilisateur
    public function countValidForm($idUser)
    {
        $qb = $this->createQueryBuilder('r')
                ->select("COUNT(r)")
                ->where("r.userValidate = :idUser")
                ->setParameters(array(
                    "idUser" => $idUser
                ));

        return $qb->getQuery()->getSingleScalarResult();
    }

    // Retourne le nombre d'enregistrement accessible par l'utilisateur
    public function countRegistrationUserCanAccess($idUser)
    {
        $qb = $this->createQueryBuilder('r')
                ->select('COUNT(r)')
                ->leftJoin('r.form', 'f')
                ->leftJoin('r.userSubmit', 'us')
                ->leftJoin('f.readers', 'g')
                ->leftJoin('g.users', 'u')

                ->where('u.id = :idUser OR us.id = :idUser')

                ->setParameter("idUser", $idUser);

        return $qb->getQuery()->getSingleScalarResult();
    }

    // Retourne le nombre d'enregistrement accessible par l'utilisateur répondant au critéres (status et qui)
    public function countRegistrationUserCanAccessAjax($idUser, $idStatus, $who)
    {
        $qb = $this->createQueryBuilder('r')
            ->select('COUNT(r)')
            ->leftJoin('r.form', 'f')
            ->leftJoin('r.userSubmit', 'us')
            ->leftJoin('r.userValidate', 'uv')
            ->leftJoin('f.readers', 'g')
            ->leftJoin('g.users', 'u')
            ->orderBy('r.registrationDate', 'ASC')

            ->where('r.status IN (:idStatus)')
            ->setParameters(array(
                "idUser" => $idUser,
                "idStatus" => $idStatus
            ));

        if ($who['submitByUser'] AND !$who['submitByOther']) {
            $qb->andWhere('us.id = :idUser');
        } else if (!$who['submitByUser'] AND $who['submitByOther']) {
            $qb->andWhere('us.id != :idUser AND u.id = :idUser');
        } else if ($who['validByUser'] AND !$who['validByOther']) {
            $qb->andWhere('uv.id = :idUser');
        } else if (!$who['validByUser'] AND $who['validByOther']) {
            $qb->andWhere('uv.id != :idUser AND u.id = :idUser');
        } else {
            $qb->andWhere('u.id = :idUser OR us.id = :idUser');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    // Retourne un enregistrement complet
    public function getCompleteRegistration($id)
    {
        $qb = $this->CreateQueryBuilder('r')
                    ->leftJoin('r.form', 'f')
                    ->leftJoin('r.userSubmit', 'us')
                    ->leftJoin('r.userValidate', 'uv')
                    ->leftJoin('r.registers', 're')
                    ->leftJoin('re.field', 'fi')
                    ->select('r')
                    ->addSelect('us')
                    ->addSelect('uv')
                    ->addSelect('re')
                    ->addSelect('f')
                    ->addSelect('fi')
                    ->where('r.id = :idRegistration')
                    ->setParameter('idRegistration', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }
}




