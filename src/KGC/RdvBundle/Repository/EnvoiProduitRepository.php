<?php

// src/KGC/RdvBundle/Repository/EnvoiProduitRepository.php


namespace KGC\RdvBundle\Repository;

use Doctrine\ORM\EntityRepository;
use KGC\RdvBundle\Entity\EnvoiProduit;
use KGC\RdvBundle\Entity\RDV;

/**
 * EnvoiProduitRepository.
 */
class EnvoiProduitRepository extends EntityRepository
{
    public function getTableSuiviCount($state_filter = null, $begin = null, $end = null)
    {
        $qb = $this->createQueryBuilder('evp')
                    ->select('count(evp.id)')
                    ->innerJoin('evp.consultation', 'rdv')
                ;
        if (!empty($state_filter)) {
            $qb->andWhere($qb->expr()->in('evp.etat', $state_filter));
        }
        if ($begin instanceof \DateTime) {
            $qb->andWhere('rdv.dateConsultation > :begin')
               ->setParameter('begin', $begin);
        }
        if ($end instanceof \DateTime) {
            $qb->andWhere('rdv.dateConsultation < :end')
               ->setParameter('end', $end);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * requête tableau de suivi des produits.
     *
     * @param $classement
     */
    public function getTableSuivi($interval = null, $state_filter = null, $begin = null, $end = null)
    {
        $sub_sql = "
            SELECT envoiproduit.*,
                client.id,
                cli_nom, cli_prenom,
                rdv_id, rdv_date_consultation,
                voy_nom AS voyant, uti_username AS consultant,
                produit.label AS pro_nom,
                vpr_quantite_envoi, (vpr_montant/100) AS montant_produit,
                (tar_montant_total/100) AS montant_total,
                IFNULL(( SELECT SUM(enc_montant)/100 FROM encaissements WHERE enc_etat = 'STATE_DONE' AND enc_consultation = rdv_id ), 0) AS montant_encaisse
            FROM envoiproduit
                INNER JOIN consultation ON evp_consultation = rdv_id
                INNER JOIN tarification ON rdv_tarification = tar_id
                INNER JOIN client ON rdv_client = client.id
                INNER JOIN voyant ON rdv_voyant = voy_id
                INNER JOIN utilisateur ON rdv_consultant = uti_id
                INNER JOIN ventes_produits ON evp_venteproduit = vpr_id
                INNER JOIN historique_option AS produit ON vpr_produit = produit.id
            WHERE 1 
        ";
        if (!empty($state_filter)) {
            $sub_sql .= ' AND evp_etat IN ('.implode(',', array_map(function ($a) { return "'".$a."'";}, $state_filter)).')';
        }
        if ($begin instanceof \DateTime) {
            $sub_sql .= ' AND rdv_date_consultation > '.$begin->format("'Y-m-d'");
        }
        if ($end instanceof \DateTime) {
            $sub_sql .= ' AND rdv_date_consultation < '.$end->format("'Y-m-d'");
        }

        $sql = 'SELECT *, (montant_encaisse / montant_total * 100) AS pct_encaisse FROM ('.$sub_sql.') AS suivi_envoiproduit ORDER BY pct_encaisse DESC, rdv_date_consultation DESC';

        if ($interval !== null) {
            $sql .= ' LIMIT '.$interval['nb'].' OFFSET '.$interval['start'];
        }

        $query = $this->_em->getConnection()->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    public function findIgnitionAvailableQB(RDV $rdv)
    {
        $qb = $this->createQueryBuilder('evp')
                   ->innerJoin('evp.consultation', 'rdv')
                   ->where('evp.allumage = :allumage_en_attente')
                   ->setParameter('allumage_en_attente', EnvoiProduit::IGNITION_PLANNED)
                   ->andWhere('evp.etat = :envoi_fait')
                   ->setParameter('envoi_fait', EnvoiProduit::DONE)
                   ->andWhere('rdv.dateConsultation < :date_rdv')
                   ->setParameter('date_rdv', $rdv->getDateConsultation())
                   ->andWhere('rdv.client = :client')
                   ->setParameter('client', $rdv->getClient());

        return $qb;
    }
}
