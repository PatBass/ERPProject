<?php

namespace KGC\DashboardBundle\Controller;

use KGC\CommonBundle\Controller\CommonController;
use KGC\RdvBundle\Controller\ConsultationController;
use KGC\RdvBundle\Elastic\Paginator\ElasticPaginator;
use KGC\RdvBundle\Entity\RDV;
use KGC\RdvBundle\Form\RDVAjouterType;
use KGC\UserBundle\Controller\ElasticController;
use KGC\UserBundle\Elastic\Model\ProspectSearch;
use KGC\UserBundle\Form\ProspectEditType;
use KGC\UserBundle\Form\ProspectType;
use KGC\UserBundle\Handler\ProspectEditHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SmsController.
 */
class SmsController extends CommonController
{
    /**
     * Return the useful information to get repository.
     *
     * @return mixed
     */
    protected function getEntityRepository()
    {
        return 'KGCClientBundle:Sms';
    }

    /**
     * Méthode general.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN")
     */
    public function generalAction()
    {
        return $this->render('KGCDashboardBundle:Consultations:index.html.twig');
    }

    /**
     * Méthode add.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN")
     */
    public function addAction()
    {
        return $this->render('KGCDashboardBundle:Consultations:index.html.twig');
    }

    /**
     * Méthode model.
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response represents an HTTP response
     * @Secure(roles="ROLE_ADMIN")
     */
    public function modelAction()
    {
        $em = $this->getDoctrine()->getEntityManager();
        if ('chat' === $this->get('session')->get('dashboard')) {
            $tchat = 1;
        }else {
            $tchat = 0;
        }
        $campagnes = $em->getRepository('KGCClientBundle:CampagneSms')->findByTchat($tchat);

        return $this->render('KGCDashboardBundle:Config:campagne.list.html.twig', [
            'campagne' => $campagnes,
        ]);
    }

}
