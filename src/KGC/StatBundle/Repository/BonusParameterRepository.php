<?php
// src/KGC/StatBundle/REpository/BonusParameterRepository.php

namespace KGC\StatBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use KGC\StatBundle\Entity\BonusParameter;
use KGC\UserBundle\Entity\Utilisateur;

/**
 * Class BonusParameterRepository.
 */
class BonusParameterRepository extends EntityRepository
{
    private function addUserCriteria(QueryBuilder $qb, Utilisateur $user)
    {
        $qb->andWhere('u.id = :userid')
           ->setParameter('userid', $user->getId());
    }
    
    public function getPhonisteHebdoBonus(\DateTime $begin, \DateTime $end, Utilisateur $user = null)
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.user', 'u')
                   ->where('b.code = :bonus')
                   ->andWhere('b.date >= :debut AND b.date < :fin')
                   ->setParameters(['bonus' => BonusParameter::PHONISTE_HEBDO, 'debut' => $begin, 'fin' => $end])
                   ->orderBy('u.username');
        
        if(isset($user)){
            $this->addUserCriteria($qb, $user);
            
            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }
    
    public function getPhonisteChallengeBonus(\DateTime $begin, \DateTime $end, Utilisateur $user = null)
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.user', 'u')
                   ->where('b.code = :bonus')
                   ->andWhere('b.date >= :debut AND b.date < :fin')
                   ->setParameters(['bonus' => BonusParameter::PHONISTE_CHALLENGE, 'debut' => $begin, 'fin' => $end])
                   ->orderBy('u.username');
        
        if(isset($user)){
            $this->addUserCriteria($qb, $user);
            
            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }

    public function getPsychicHebdoBonus(\DateTime $begin, \DateTime $end, Utilisateur $user = null)
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.user', 'u')
                   ->where('b.code = :bonus')
                   ->andWhere('b.date >= :debut AND b.date < :fin')
                   ->setParameters(['bonus' => BonusParameter::PSYCHIC_HEBDO, 'debut' => $begin, 'fin' => $end])
                   ->orderBy('u.username');
        
        if(isset($user)){
            $this->addUserCriteria($qb, $user);
            
            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }
    
    public function getPhonistPenalty(\DateTime $begin, \DateTime $end, Utilisateur $user = null)
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.user', 'u')
                   ->where('b.code = :bonus')
                   ->andWhere('b.date >= :debut AND b.date < :fin')
                   ->setParameters(['bonus' => BonusParameter::PHONISTE_PENALTY, 'debut' => $begin, 'fin' => $end])
                   ->orderBy('u.username');
        
        if(isset($user)){
            $this->addUserCriteria($qb, $user);
            
            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }
    
    public function getPsychicPenalty(\DateTime $begin, \DateTime $end, Utilisateur $user = null)
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.user', 'u')
                   ->where('b.code = :bonus')
                   ->andWhere('b.date >= :debut AND b.date < :fin')
                   ->setParameters(['bonus' => BonusParameter::PSYCHIC_PENALTY, 'debut' => $begin, 'fin' => $end])
                   ->orderBy('u.username');
        
        if(isset($user)){
            $this->addUserCriteria($qb, $user);
            
            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }
    
    public function getBonus($code, \DateTime $begin = null, \DateTime $end = null, Utilisateur $user = null)
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.user', 'u')
                   ->where('b.code = :bonus')
                   ->setParameter('bonus', $code)
                   ->orderBy('b.objective')
                   ->addOrderBy('b.sec_objective');
        
        if(isset($begin) && isset($end)){   
            $qb->andWhere('b.date >= :debut AND b.date < :fin')
               ->setParameter('debut', $begin)
               ->setParameter('fin', $end);
        }
        
        if(isset($user)){
            $this->addUserCriteria($qb, $user);
        }
        
        if(isset($user) || $code == BonusParameter::PHONISTE_QUALITY || $code == BonusParameter::PHONISTE_QUANTITY){
            return $qb->getQuery()->getOneOrNullResult();
        }

        return $qb->getQuery()->getResult();
    }
}