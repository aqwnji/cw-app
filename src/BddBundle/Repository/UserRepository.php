<?php

namespace BddBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    /**
     * @return \Doctrine\ORM\Query
     */
    public function getUserList()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->addSelect('count(r.id) as total_ratings')
            ->from('BddBundle:User', 'u')
            ->innerJoin('u.ratings', 'r', 'WITH', 'r.user = u')
            ->groupBy('r.user')
            ->orderBy('total_ratings', 'desc')
            ->getQuery();
    }

    /**
     * @param int $userId
     * @return \Doctrine\ORM\Query
     */
    public function getUserRankings(int $userId)
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('r, c, bc')
            ->from('BddBundle:RiddenCoaster', 'r')
            ->innerJoin('r.user', 'u')
            ->innerJoin('r.coaster', 'c')
            ->innerJoin('c.builtCoaster', 'bc')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery();
    }

    /**
     * Returns users that have recently up
     * @param int $sinceHours
     * @return mixed
     */
    public function getUsersWithRecentRatingOrTopUpdate($sinceHours = 1)
    {
        $date = new \DateTime('- '.$sinceHours.' hours');

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from('BddBundle:User', 'u')
            ->leftJoin('u.ratings', 'r')
            ->leftJoin('u.listes', 'l')
            ->where('r.updatedAt > :date')
            ->orWhere('l.updatedAt > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
