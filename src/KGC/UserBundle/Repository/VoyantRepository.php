<?php

// src/KGC/UserBundle/Repository/VoyantRepository.php


namespace KGC\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * VoyantRepository.
 *
 * @category EntityRepository
 */
class VoyantRepository extends EntityRepository
{
    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return QuerBuilder
     */
    public function findAllQB($active = null, $activedate = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->addOrderBy('v.nom', 'ASC')
        ;
        if ($active === true) {
            if ($activedate === null) {
                $activedate = new \DateTime();
            }
            $qb = $this->addActiveCriteria($qb, $activedate);
        }

        return $qb;
    }

    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return array
     */
    public function findAll($active = null, $activedate = null)
    {
        $qb = $this->findAllQB($active, $activedate);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return array
     */
    public function findAllHavingTarificationQB($active = null, $activedate = null)
    {
        return $this->findAllQB($active, $activedate)->andWhere('v.codeTarification IS NOT NULL');
    }

    /**
     * @param bool      $active
     * @param \DateTime $activedate
     *
     * @return array
     */
    public function findAllWithTarification($isChat = false, $active = null, $activedate = null)
    {
        $qb = $this->findAllQB($active, $activedate)
            ->leftJoin('v.codeTarification', 'codeT')->addSelect('codeT')
            ->leftJoin('v.website', 'w');
        if ($isChat) {
            $qb
                ->andWhere('w.paymentGateway IS NOT NULL')
                ->orderBy('w.libelle')
                ->addOrderBy('v.nom');
        } else {
            $qb->andWhere('w.paymentGateway IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param \DateTime    $active_date
     *
     * @return QueryBuilder
     */
    private function addActiveCriteria($qb, \DateTime $active_date)
    {
        $qb->andWhere('v.enabled = 1 OR (v.enabled = 0 AND v.disabled_date > :date_rdv)')
           ->setParameter('date_rdv', $active_date);

        return $qb;
    }

    /**
     * Essaie de trouver le voyant d'après son nom avec une certaine marge d'erreur sur l'orthographe.
     *
     * @param $nom
     *
     * @return bool
     */
    public function getApproxi($nom)
    {
        $find = false;
        if (!empty($nom)) {
            $voyant_list = $this->findAll();
            $i = 0;
            while ($i < count($voyant_list) and !$find) {
                $similarity_pct = 0;
                $nom_test = strtoupper($nom);
                $nom_voyant = strtoupper($voyant_list[$i]->getNom());
                similar_text($nom_test, $nom_voyant, $similarity_pct);
                if (number_format($similarity_pct) > 90) {
                    $find = $voyant_list[$i];
                }
                ++$i;
            }
        }

        return $find;
    }

    /**
     * Get availables virtual psychics in function of phisycals psychics connected and availables.
     *
     * @param array               $psychic_availables To know available virtual psychics, we need to know available physic psychics
     * @param [OPTIONNAL] integer $website_id         If specified, restrict the results to this specific website
     *
     * @return array
     */
    public function getAvailableVirtualPsychics($psychic_availables, $website_id = null)
    {
        // If $psychic_availables is empty or even it's not an array, return empy result
        if (!(is_array($psychic_availables) && count($psychic_availables) > 0)) {
            return array();
        }

        $qb = $this->createQueryBuilder('v');

        if (is_numeric($website_id)) {
            $qb->join('v.website', 'w', 'WITH', 'w.id = :website_id')
               ->setParameter('website_id', $website_id);
        } else {
            $qb->join('v.website', 'w');
        }
        $qb->join('w.chatFormulas', 'chatFormula', 'WITH', 'chatFormula.desactivationDate IS NULL OR chatFormula.desactivationDate > CURRENT_DATE()')
            ->join('chatFormula.chatType', 'chatType')
            ->join('chatType.psychics', 'p', 'WITH', 'p.id IN (:psychics)')
            ->setParameter('psychics', array_map(
                function ($p) {
                    return $p->getId();
                },
                $psychic_availables
            ))
            ->leftJoin('v.utilisateur', 'u')
            ->where('u.id IS NOT NULL AND u.chatType = chatFormula.chatType AND u.actif = 1 AND u.isChatAvailable = 1')
            ->orWhere('u.id IS NULL AND p.sexe = v.sexe');

        return $qb->getQuery()->getResult();
    }
}
